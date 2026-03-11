<?php
// ============================================================
//  EduQueue – API: Mark Queue Status (Staff action)
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/queue.php';

requireLogin('staff');
verifyCsrf();

$queueId = (int)($_POST['queue_id'] ?? 0);
$status  = $_POST['status'] ?? '';

if (!$queueId || !$status) {
    header('Location: ' . BASE_URL . '/staff.php?error=missing_params');
    exit;
}

markQueueStatus($queueId, $status);
header('Location: ' . BASE_URL . '/staff.php');
exit;
