<?php
// ============================================================
//  EduQueue – Admin: Export Queue Log as CSV
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

requireLogin('admin');
$pdo = getDB();

$sessionId = (int)($_GET['session_id'] ?? 0);
$dateStr   = date('Y-m-d');

if ($sessionId) {
    $stmt = $pdo->prepare(
        "SELECT q.queue_number, u.full_name, u.student_id, u.email,
                d.name AS department, q.purpose, q.status,
                q.joined_at, q.called_at, q.completed_at
         FROM queues q
         JOIN users u ON q.student_id = u.id
         JOIN departments d ON q.department_id = d.id
         WHERE q.session_id = ?
         ORDER BY d.name, q.sequence"
    );
    $stmt->execute([$sessionId]);
} else {
    $stmt = $pdo->query(
        "SELECT q.queue_number, u.full_name, u.student_id, u.email,
                d.name AS department, q.purpose, q.status,
                q.joined_at, q.called_at, q.completed_at
         FROM queues q
         JOIN users u ON q.student_id = u.id
         JOIN departments d ON q.department_id = d.id
         ORDER BY q.joined_at DESC LIMIT 1000"
    );
}

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="eduqueue-' . $dateStr . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Queue #','Full Name','Student ID','Email','Department','Purpose','Status','Joined','Called','Completed']);

while ($row = $stmt->fetch()) {
    fputcsv($out, [
        $row['queue_number'], $row['full_name'], $row['student_id'], $row['email'],
        $row['department'], $row['purpose'], $row['status'],
        $row['joined_at'], $row['called_at'], $row['completed_at'],
    ]);
}
fclose($out);
