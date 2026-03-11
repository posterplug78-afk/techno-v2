<?php
// ============================================================
//  EduQueue – Login Page
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';

startSecureSession();

// Already logged in — redirect based on role
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_role'])) {
    $role = $_SESSION['user_role'];
    if ($role === 'admin') { header('Location: ' . BASE_URL . '/admin/index.php'); exit; }
    if ($role === 'staff') { header('Location: ' . BASE_URL . '/staff.php');       exit; }
    header('Location: ' . BASE_URL . '/queue.php'); exit;
}

$error         = '';
$session_token = $_GET['session'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email    = sanitize($_POST['email']    ?? '');
    $password = $_POST['password']          ?? '';

    $result = loginUser($email, $password);

    if ($result['success']) {
        // Re-open session to read the role that was saved
        session_start();

        $role = $_SESSION['user_role'] ?? '';

        if ($session_token) {
            $_SESSION['qr_session_token'] = $session_token;
        }

        session_write_close();

        if ($role === 'admin') { header('Location: ' . BASE_URL . '/admin/index.php'); exit; }
        if ($role === 'staff') { header('Location: ' . BASE_URL . '/staff.php');       exit; }
        header('Location: ' . BASE_URL . '/queue.php'); exit;
    }

    $error = $result['message'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login – <?= SCHOOL_NAME ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <h1>Edu<span>Queue</span></h1>
      <p><?= htmlspecialchars(SCHOOL_NAME) ?> &nbsp;·&nbsp; Inquiry Queue System</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <?= csrfField() ?>
      <?php if ($session_token): ?>
      <input type="hidden" name="session" value="<?= htmlspecialchars($session_token) ?>">
      <?php endif; ?>

      <div class="form-group">
        <label for="email">School Email</label>
        <input class="form-control" type="email" id="email" name="email"
               placeholder="yourid<?= SCHOOL_DOMAIN ?>"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password"
               placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn btn-primary btn-full mt-2">Log In →</button>
    </form>

    <p class="text-center text-muted mt-2">
      Don't have an account?
      <a href="<?= BASE_URL ?>/register.php<?= $session_token ? '?session='.urlencode($session_token) : '' ?>"
         style="color:var(--teal)">Register here</a>
    </p>
  </div>
</div>
</body>
</html>