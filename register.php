<?php
// ============================================================
//  EduQueue – Student Registration
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/db.php';

startSecureSession();
if (isLoggedIn()) { header('Location: ' . BASE_URL . '/queue.php'); exit; }

$error   = '';
$success = '';
$session_token = $_GET['session'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email      = sanitize($_POST['email']      ?? '');
    $full_name  = sanitize($_POST['full_name']  ?? '');
    $student_id = sanitize($_POST['student_id'] ?? '');
    $password   = $_POST['password']   ?? '';
    $confirm    = $_POST['confirm']    ?? '';

    if (!validateSchoolEmail($email)) {
        $error = 'Only ' . SCHOOL_DOMAIN . ' email addresses are allowed.';
    } elseif (strlen($full_name) < 3) {
        $error = 'Please enter your full name.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('INSERT INTO users (email, full_name, student_id, role, password_hash) VALUES (?,?,?,?,?)')
                ->execute([$email, $full_name, $student_id, 'student', $hash]);
            $success = 'Account created! You can now log in.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register – <?= SCHOOL_NAME ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <h1>Edu<span>Queue</span></h1>
      <p>Create your student account</p>
    </div>

    <?php if ($error):   ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?>
      <a href="<?= BASE_URL ?>/login.php<?= $session_token ? '?session='.$session_token : '' ?>">Log in →</a>
    </div><?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST">
      <?= csrfField() ?>
      <div class="form-group">
        <label>Full Name</label>
        <input class="form-control" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required placeholder="Juan Dela Cruz">
      </div>
      <div class="form-group">
        <label>Student ID</label>
        <input class="form-control" name="student_id" value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>" placeholder="20-12345">
      </div>
      <div class="form-group">
        <label>School Email</label>
        <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required placeholder="yourid<?= SCHOOL_DOMAIN ?>">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input class="form-control" type="password" name="password" required placeholder="Min. 8 characters">
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input class="form-control" type="password" name="confirm" required placeholder="Repeat password">
      </div>
      <button type="submit" class="btn btn-gold btn-full mt-2">Create Account →</button>
    </form>
    <?php endif; ?>

    <p class="text-center text-muted mt-2">
      Already have an account? <a href="<?= BASE_URL ?>/login.php<?= $session_token ? '?session='.$session_token : '' ?>" style="color:var(--teal)">Log in</a>
    </p>
  </div>
</div>
</body>
</html>
