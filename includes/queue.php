<?php
// ============================================================
//  EduQueue – Core Queue Logic
// ============================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/sms.php';

// ── GET ACTIVE SESSION ────────────────────────────────────────
function getActiveSession(string $token): ?array {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT * FROM queue_sessions
         WHERE session_token = ? AND session_date = CURDATE()
           AND is_active = 1 AND expires_at > NOW()'
    );
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}

function getOrCreateTodaySession(): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM queue_sessions WHERE session_date = CURDATE() AND is_active = 1');
    $stmt->execute();
    $session = $stmt->fetch();
    if ($session) return $session;

    // Create a new one
    $token     = bin2hex(random_bytes(16));
    $expiresAt = date('Y-m-d') . ' 23:59:59';
    $pdo->prepare(
        'INSERT INTO queue_sessions (session_token, session_date, is_active, expires_at) VALUES (?, CURDATE(), 1, ?)'
    )->execute([$token, $expiresAt]);

    $id = (int)$pdo->lastInsertId();

    // Ensure counter rows exist for all departments
    $depts = $pdo->query('SELECT id FROM departments')->fetchAll();
    foreach ($depts as $d) {
        $pdo->prepare(
            'INSERT IGNORE INTO queue_counters (department_id, session_id, last_issued, current_serving) VALUES (?, ?, 0, 0)'
        )->execute([$d['id'], $id]);
    }

    return getOrCreateTodaySession();
}

// ── GENERATE QUEUE NUMBER ─────────────────────────────────────
function generateQueueNumber(int $sessionId, int $studentId, int $deptId, string $purpose, string $phone = ''): array {
    $pdo = getDB();

    try {
        $pdo->beginTransaction();

        // 1. Check student doesn't already have an active number
        $stmt = $pdo->prepare(
            "SELECT queue_number FROM queues
             WHERE student_id = ? AND session_id = ? AND status IN ('waiting','serving')"
        );
        $stmt->execute([$studentId, $sessionId]);
        if ($row = $stmt->fetch()) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'You already have an active queue number: ' . $row['queue_number']];
        }

        // 2. Lock counter row and increment
        $stmt = $pdo->prepare('SELECT last_issued FROM queue_counters WHERE department_id = ? FOR UPDATE');
        $stmt->execute([$deptId]);
        $counter = $stmt->fetch();
        if (!$counter) {
            $pdo->rollBack();
            return ['success' => false, 'message' => 'Department not found.'];
        }

        $nextSeq = $counter['last_issued'] + 1;
        $pdo->prepare('UPDATE queue_counters SET last_issued = ? WHERE department_id = ?')
            ->execute([$nextSeq, $deptId]);

        // 3. Build queue number  e.g. R-007
        $dept = $pdo->prepare('SELECT prefix FROM departments WHERE id = ?');
        $dept->execute([$deptId]);
        $prefix      = $dept->fetchColumn();
        $queueNumber = $prefix . '-' . str_pad($nextSeq, 3, '0', STR_PAD_LEFT);

        // 4. Insert queue record
        $pdo->prepare(
            'INSERT INTO queues (session_id, student_id, department_id, queue_number, sequence, purpose, sms_phone)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        )->execute([$sessionId, $studentId, $deptId, $queueNumber, $nextSeq, $purpose, $phone ?: null]);

        $queueId = (int)$pdo->lastInsertId();
        $pdo->commit();

        return [
            'success'      => true,
            'queue_id'     => $queueId,
            'queue_number' => $queueNumber,
            'sequence'     => $nextSeq,
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Could not generate queue number. Please try again.'];
    }
}

// ── QUEUE STATUS (for AJAX polling) ──────────────────────────
function getQueueStatus(int $queueId): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT q.*, d.name AS dept_name, d.avg_service_minutes, qc.current_serving
         FROM queues q
         JOIN departments d  ON q.department_id  = d.id
         JOIN queue_counters qc ON qc.department_id = q.department_id
         WHERE q.id = ?'
    );
    $stmt->execute([$queueId]);
    $q = $stmt->fetch();
    if (!$q) return ['error' => 'Not found'];

    $ahead   = max(0, $q['sequence'] - $q['current_serving'] - 1);
    $estWait = $ahead * $q['avg_service_minutes'];

    return [
        'queue_number'    => $q['queue_number'],
        'status'          => $q['status'],
        'current_serving' => $q['prefix'] ?? ($q['queue_number'][0] . '-' . str_pad($q['current_serving'], 3, '0', STR_PAD_LEFT)),
        'ahead'           => $ahead,
        'est_wait'        => $estWait,
        'dept_name'       => $q['dept_name'],
        'notified'        => (bool)$q['notified_sms'],
    ];
}

// ── CALL NEXT ─────────────────────────────────────────────────
function callNext(int $deptId, int $staffId): array {
    $pdo = getDB();

    // Mark current 'serving' as done if staff forgot to close
    $pdo->prepare(
        "UPDATE queues SET status='done', completed_at=NOW()
         WHERE department_id=? AND status='serving'"
    )->execute([$deptId]);

    // Increment current_serving counter
    $pdo->prepare('UPDATE queue_counters SET current_serving = current_serving + 1 WHERE department_id = ?')
        ->execute([$deptId]);

    $stmt = $pdo->prepare('SELECT current_serving FROM queue_counters WHERE department_id = ?');
    $stmt->execute([$deptId]);
    $seq = (int)$stmt->fetchColumn();

    // Find the queue entry
    $stmt = $pdo->prepare("SELECT * FROM queues WHERE department_id=? AND sequence=? AND status='waiting'");
    $stmt->execute([$deptId, $seq]);
    $entry = $stmt->fetch();

    if (!$entry) {
        // No one at this sequence — could be skipped or nobody
        return ['success' => true, 'queue_number' => null, 'message' => 'No student at this position.'];
    }

    // Mark as serving
    $pdo->prepare("UPDATE queues SET status='serving', called_at=NOW(), served_at=NOW(), call_count=call_count+1 WHERE id=?")
        ->execute([$entry['id']]);

    // Check if anyone 3 ahead should be notified
    $notifySeq = $seq + SMS_NOTIFY_THRESHOLD;
    $notifyStmt = $pdo->prepare("SELECT id FROM queues WHERE department_id=? AND sequence=? AND status='waiting' AND notified_sms=0");
    $notifyStmt->execute([$deptId, $notifySeq]);
    if ($notify = $notifyStmt->fetch()) {
        notifyStudentSoon($notify['id']);
    }

    return [
        'success'      => true,
        'queue_number' => $entry['queue_number'],
        'queue_id'     => $entry['id'],
        'student_id'   => $entry['student_id'],
    ];
}

// ── MARK STATUS ───────────────────────────────────────────────
function markQueueStatus(int $queueId, string $status): bool {
    $allowed = ['done', 'skipped', 'missed'];
    if (!in_array($status, $allowed)) return false;

    $pdo = getDB();

    $extra = '';
    if ($status === 'done') {
        $extra = ', completed_at = NOW()';
        // Update rolling avg service time
        $pdo->prepare(
            'UPDATE departments d
             JOIN queues q ON q.department_id = d.id
             SET d.avg_service_minutes = GREATEST(1, ROUND(
                 (d.avg_service_minutes * 0.8) +
                 (TIMESTAMPDIFF(MINUTE, q.served_at, NOW()) * 0.2)
             ))
             WHERE q.id = ?'
        )->execute([$queueId]);
    }

    $pdo->prepare("UPDATE queues SET status = ? $extra WHERE id = ?")
        ->execute([$status, $queueId]);

    return true;
}

// ── LIVE BOARD DATA ───────────────────────────────────────────
function getLiveBoardData(): array {
    $pdo  = getDB();
    $depts = $pdo->query('SELECT d.*, qc.current_serving, qc.last_issued FROM departments d LEFT JOIN queue_counters qc ON qc.department_id = d.id WHERE d.is_open = 1')->fetchAll();

    $board = [];
    foreach ($depts as $d) {
        $serving = $d['prefix'] . '-' . str_pad($d['current_serving'] ?? 0, 3, '0', STR_PAD_LEFT);

        $nextStmt = $pdo->prepare(
            "SELECT queue_number, u.full_name, q.est_wait FROM queues q
             JOIN users u ON q.student_id = u.id
             WHERE q.department_id = ? AND q.status = 'waiting'
             ORDER BY q.sequence ASC LIMIT 5"
        );

        // Compute est_wait inline
        $nextStmt = $pdo->prepare(
            "SELECT q.queue_number, u.full_name,
                    (q.sequence - qc.current_serving - 1) * d.avg_service_minutes AS est_wait
             FROM queues q
             JOIN users u ON q.student_id = u.id
             JOIN departments d ON q.department_id = d.id
             JOIN queue_counters qc ON qc.department_id = q.department_id
             WHERE q.department_id = ? AND q.status = 'waiting'
             ORDER BY q.sequence ASC LIMIT 5"
        );
        $nextStmt->execute([$d['id']]);
        $next = $nextStmt->fetchAll();

        $board[] = [
            'id'              => $d['id'],
            'name'            => $d['name'],
            'prefix'          => $d['prefix'],
            'current_serving' => $serving,
            'waiting_count'   => $d['last_issued'] - ($d['current_serving'] ?? 0),
            'next'            => $next,
        ];
    }
    return $board;
}
