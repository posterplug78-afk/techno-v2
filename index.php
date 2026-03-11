<?php
// ============================================================
//  EduQueue – QR Landing Page
//  URL: school.edu/eduqueue/?session=TOKEN
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/queue.php';

startSecureSession();

$token   = trim($_GET['session'] ?? '');
$session = $token ? getActiveSession($token) : null;

// If no token, redirect to today's session creator (admin use) or show error
if (!$token) {
    // Redirect to login which will pick up no session
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

if (!$session) {
    // Invalid or expired QR code
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invalid QR – <?= SCHOOL_NAME ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body style="background:var(--navy);display:flex;align-items:center;justify-content:center;min-height:100vh;">
<div style="background:#fff;border-radius:16px;padding:2.5rem;max-width:380px;text-align:center;box-shadow:0 16px 48px rgba(0,0,0,.25)">
  <div style="font-size:3rem;margin-bottom:1rem">❌</div>
  <h2 style="color:var(--navy);margin-bottom:.5rem">Invalid QR Code</h2>
  <p style="color:var(--gray-500);font-size:.9rem">This QR code has expired or is no longer valid.<br>Please scan today's QR code posted at the office.</p>
</div>
</body></html>
    <?php
    exit;
}

// Session valid — store token and redirect
$_SESSION['qr_session_token'] = $token;
$_SESSION['qr_session_id']    = $session['id'];

if (isLoggedIn() && $_SESSION['user_role'] === 'student') {
    header('Location: ' . BASE_URL . '/queue.php');
} else {
    header('Location: ' . BASE_URL . '/login.php?session=' . urlencode($token));
}
exit;
