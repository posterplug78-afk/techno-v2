<?php
// ============================================================
//  EduQueue – API: Call Next Number (Staff action)
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/queue.php';

requireLogin('staff');
verifyCsrf();

$deptId = (int)($_POST['department_id'] ?? 0);
if (!$deptId) {
    header('Location: ' . BASE_URL . '/staff.php?error=missing_dept');
    exit;
}

$result = callNext($deptId, currentUser()['id']);

// Redirect back to staff dashboard
header('Location: ' . BASE_URL . '/staff.php?called=' . urlencode($result['queue_number'] ?? 'none'));
exit;
