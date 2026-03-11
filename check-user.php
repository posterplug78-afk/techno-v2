<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';

$pdo  = getDB();
$stmt = $pdo->prepare('SELECT id, email, full_name, role, is_active, password_hash FROM users WHERE email = ?');
$stmt->execute(['admin@school.edu']);
$user = $stmt->fetch();

if (!$user) {
    echo "❌ User not found in database. Did you import schema.sql?";
} else {
    echo "<pre>";
    echo "✅ User found!\n";
    echo "ID:        " . $user['id']        . "\n";
    echo "Email:     " . $user['email']     . "\n";
    echo "Name:      " . $user['full_name'] . "\n";
    echo "Role:      " . $user['role']      . "\n";
    echo "Active:    " . $user['is_active'] . "\n";
    echo "Hash:      " . $user['password_hash'] . "\n\n";

    // Test password
    $testPassword = 'password';
    $verify = password_verify($testPassword, $user['password_hash']);
    echo "Password 'password' matches: " . ($verify ? '✅ YES' : '❌ NO') . "\n";
    echo "</pre>";
}