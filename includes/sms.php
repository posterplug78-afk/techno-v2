<?php
// ============================================================
//  EduQueue – SMS Notifications (Semaphore API)
// ============================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../config.php';

function sendSMS(string $phone, string $message, int $queueId = 0): bool {
    // Clean phone number to 11-digit PH format
    $phone = preg_replace('/\D/', '', $phone);
    if (strlen($phone) === 10) $phone = '0' . $phone;
    if (strlen($phone) !== 11)  return false;

    $payload = [
        'apikey'     => SEMAPHORE_API_KEY,
        'number'     => $phone,
        'message'    => $message,
        'sendername' => SEMAPHORE_SENDER_NAME,
    ];

    $ch = curl_init('https://api.semaphore.co/api/v4/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $success = ($httpCode === 200);

    // Log to DB
    if ($queueId > 0) {
        $pdo = getDB();
        $pdo->prepare('INSERT INTO sms_logs (queue_id, phone, message, status) VALUES (?, ?, ?, ?)')
            ->execute([$queueId, $phone, $message, $success ? 'sent' : 'failed']);
    }

    return $success;
}

function notifyStudentSoon(int $queueId): void {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT q.sms_phone, q.queue_number, q.notified_sms, d.name AS dept_name
         FROM queues q JOIN departments d ON q.department_id = d.id
         WHERE q.id = ?'
    );
    $stmt->execute([$queueId]);
    $row = $stmt->fetch();

    if (!$row || $row['notified_sms'] || empty($row['sms_phone'])) return;

    $msg = 'Your queue number ' . $row['queue_number'] .
           ' will be called soon at the ' . $row['dept_name'] .
           '. Please proceed to the window. - ' . SCHOOL_NAME;

    if (sendSMS($row['sms_phone'], $msg, $queueId)) {
        $pdo->prepare('UPDATE queues SET notified_sms = 1 WHERE id = ?')->execute([$queueId]);
    }
}
