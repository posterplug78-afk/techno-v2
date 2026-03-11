<?php
// ============================================================
//  EduQueue – Security Helpers
// ============================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../config.php';

// ── CSRF ─────────────────────────────────────────────────────
function csrfToken(): string {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

function verifyCsrf(): void {
    startSecureSession();
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('CSRF token mismatch. Please go back and try again.');
    }
}

// ── SANITIZATION ─────────────────────────────────────────────
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateSchoolEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL)
        && str_ends_with(strtolower($email), strtolower(SCHOOL_DOMAIN));
}

// ── RATE LIMITING ─────────────────────────────────────────────
function checkRateLimit(string $ip, string $action = 'submit'): bool {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM rate_limits
         WHERE ip = ? AND action = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)'
    );
    $stmt->execute([$ip, $action, RATE_LIMIT_MINUTES]);
    return (int)$stmt->fetchColumn() < RATE_LIMIT_ATTEMPTS;
}

function recordRateLimit(string $ip, string $action = 'submit'): void {
    $pdo = getDB();
    $pdo->prepare('INSERT INTO rate_limits (ip, action) VALUES (?, ?)')->execute([$ip, $action]);
}

function getClientIP(): string {
    return $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['HTTP_CLIENT_IP']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '0.0.0.0';
}

// ── reCAPTCHA v3 ─────────────────────────────────────────────
function verifyCaptcha(string $token): bool {
    if (empty(RECAPTCHA_SECRET_KEY) || RECAPTCHA_SECRET_KEY === 'YOUR_RECAPTCHA_SECRET_KEY') {
        return true; // Skip during local dev if not configured
    }
    $url  = 'https://www.google.com/recaptcha/api/siteverify';
    $data = http_build_query(['secret' => RECAPTCHA_SECRET_KEY, 'response' => $token]);
    $opts = ['http' => ['method' => 'POST', 'header' => 'Content-type: application/x-www-form-urlencoded', 'content' => $data]];
    $result = json_decode(file_get_contents($url, false, stream_context_create($opts)), true);
    return ($result['success'] ?? false) && ($result['score'] ?? 0) >= RECAPTCHA_MIN_SCORE;
}
