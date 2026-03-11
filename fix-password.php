<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

$newPassword = 'Admin@1234';
$hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

$pdo = getDB();
$pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?')
    ->execute([$hash, 'admin@school.edu']);

echo "✅ Password updated successfully!<br>";
echo "Email: admin@school.edu<br>";
echo "Password: " . $newPassword . "<br>";
echo "<br><strong>Delete this file immediately after logging in!</strong>";