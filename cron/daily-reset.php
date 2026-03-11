<?php
// ============================================================
//  EduQueue – Daily Reset Cron Job
//  Schedule: Run at 23:59 daily
//
//  Linux crontab:   59 23 * * * php /var/www/html/eduqueue/cron/daily-reset.php
//  Windows Task:    Action > php C:\xampp\htdocs\eduqueue\cron\daily-reset.php
//                   Trigger > Daily at 11:59 PM
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = getDB();
$log = [];

// 1. Expire all 'waiting' in today's session as 'missed'
$today = $pdo->query("SELECT id FROM queue_sessions WHERE session_date=CURDATE() AND is_active=1")->fetchColumn();
if ($today) {
    $n = $pdo->prepare("UPDATE queues SET status='missed', completed_at=NOW() WHERE session_id=? AND status='waiting'");
    $n->execute([$today]);
    $log[] = "Marked {$n->rowCount()} waiting as missed.";

    // Close session
    $pdo->prepare('UPDATE queue_sessions SET is_active=0 WHERE id=?')->execute([$today]);
    $log[] = "Closed session ID $today.";
}

// 2. Create tomorrow's session
$tomorrow  = date('Y-m-d', strtotime('+1 day'));
$existing  = $pdo->prepare('SELECT id FROM queue_sessions WHERE session_date=?');
$existing->execute([$tomorrow]);
if (!$existing->fetch()) {
    $token = bin2hex(random_bytes(16));
    $pdo->prepare('INSERT INTO queue_sessions (session_token, session_date, is_active, expires_at) VALUES (?,?,0,?)')
        ->execute([$token, $tomorrow, $tomorrow . ' 23:59:59']);

    $newId = (int)$pdo->lastInsertId();
    $log[] = "Created session for $tomorrow: $token";

    // Reset counters
    $depts = $pdo->query('SELECT id FROM departments')->fetchAll();
    foreach ($depts as $d) {
        $pdo->prepare('INSERT INTO queue_counters (department_id, session_id, last_issued, current_serving) VALUES (?,?,0,0)
                       ON DUPLICATE KEY UPDATE session_id=?, last_issued=0, current_serving=0')
            ->execute([$d['id'], $newId, $newId]);
    }
    $log[] = 'Reset ' . count($depts) . ' department counters.';
}

// 3. Clean old rate limit entries (keep 7 days)
$pdo->query("DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
$log[] = 'Cleaned rate limit table.';

// Log output
$logMsg = '[' . date('Y-m-d H:i:s') . "] Daily reset complete:\n" . implode("\n", $log) . "\n";
echo $logMsg;
file_put_contents(__DIR__ . '/reset.log', $logMsg, FILE_APPEND);
