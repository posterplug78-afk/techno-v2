<?php
// ============================================================
//  EduQueue – Admin Actions Handler
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/queue.php';
require_once __DIR__ . '/../includes/db.php';

requireLogin('admin');
verifyCsrf();

$action = $_POST['action'] ?? '';
$pdo    = getDB();

switch ($action) {

    case 'regenerate_qr':
        // Invalidate current session token (keep data) and create fresh token + QR
        $session = getOrCreateTodaySession();
        $newToken = bin2hex(random_bytes(16));
        $pdo->prepare('UPDATE queue_sessions SET session_token=? WHERE id=?')
            ->execute([$newToken, $session['id']]);
        header('Location: ' . BASE_URL . '/admin/index.php?msg=qr_regenerated');
        break;

    case 'toggle_dept':
        $deptId = (int)($_POST['dept_id'] ?? 0);
        $pdo->prepare('UPDATE departments SET is_open = NOT is_open WHERE id=?')->execute([$deptId]);
        header('Location: ' . BASE_URL . '/admin/index.php');
        break;

    case 'end_of_day':
        // 1. Mark all remaining 'waiting' as 'missed'
        $session = getOrCreateTodaySession();
        $pdo->prepare("UPDATE queues SET status='missed', completed_at=NOW() WHERE session_id=? AND status='waiting'")
            ->execute([$session['id']]);

        // 2. Close current session
        $pdo->prepare('UPDATE queue_sessions SET is_active=0 WHERE id=?')
            ->execute([$session['id']]);

        // 3. Generate new session for tomorrow
        $newToken = bin2hex(random_bytes(16));
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $pdo->prepare('INSERT INTO queue_sessions (session_token, session_date, is_active, expires_at) VALUES (?,?,0,?)')
            ->execute([$newToken, $tomorrow, $tomorrow . ' 23:59:59']);

        $newSessionId = (int)$pdo->lastInsertId();

        // 4. Reset counters for tomorrow
        $depts = $pdo->query('SELECT id FROM departments')->fetchAll();
        foreach ($depts as $d) {
            $pdo->prepare('INSERT INTO queue_counters (department_id, session_id, last_issued, current_serving) VALUES (?,?,0,0)
                           ON DUPLICATE KEY UPDATE session_id=?, last_issued=0, current_serving=0')
                ->execute([$d['id'], $newSessionId, $newSessionId]);
        }

        header('Location: ' . BASE_URL . '/admin/index.php?msg=reset_done');
        break;

    default:
        header('Location: ' . BASE_URL . '/admin/index.php');
}
exit;
