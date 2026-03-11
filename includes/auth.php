<?php
// ============================================================
//  EduQueue – Authentication Helpers
// ============================================================
require_once __DIR__ . '/db.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false,
            'httponly' => true,
            'samesite' => 'Lax',  // Changed from Strict to Lax to fix redirect issues
        ]);
        session_start();
    }
}

function loginUser(string $email, string $password): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    // Start session first before regenerating
    startSecureSession();

    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);

    // Save all user data into session
    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];

    // Force session write before redirect
    session_write_close();

    return ['success' => true, 'role' => $user['role']];
}

function isLoggedIn(): bool {
    startSecureSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

function requireLogin(string $role = ''): void {
    startSecureSession();

    // Not logged in — redirect to login
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_role'])) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }

    // No role restriction
    if ($role === '') return;

    $sessionRole = $_SESSION['user_role'] ?? '';

    // Admin can access everything
    if ($sessionRole === 'admin') return;

    // Exact role match
    if ($sessionRole === $role) return;

    // Deny
    http_response_code(403);
    die('Access denied. Your role (' . htmlspecialchars($sessionRole) . ') cannot access this page.');
}

function currentUser(): array {
    startSecureSession();
    return [
        'id'    => $_SESSION['user_id']    ?? null,
        'role'  => $_SESSION['user_role']  ?? null,
        'name'  => $_SESSION['user_name']  ?? null,
        'email' => $_SESSION['user_email'] ?? null,
    ];
}

function logoutUser(): void {
    startSecureSession();
    $_SESSION = [];
    session_destroy();
}