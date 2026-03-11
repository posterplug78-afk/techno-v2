<?php
// ============================================================
//  EduQueue – API: Queue Status (AJAX polling)
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/queue.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$queueId = (int)($_GET['queue_id'] ?? 0);
if (!$queueId) {
    echo json_encode(['error' => 'Missing queue_id']);
    exit;
}

// Make sure this queue belongs to the requesting user (security)
$pdo  = getDB();
$stmt = $pdo->prepare('SELECT student_id FROM queues WHERE id = ?');
$stmt->execute([$queueId]);
$row  = $stmt->fetch();
$user = currentUser();

if (!$row || ($row['student_id'] != $user['id'] && !in_array($user['role'], ['staff','admin']))) {
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

echo json_encode(getQueueStatus($queueId));
