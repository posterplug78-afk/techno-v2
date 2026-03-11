<?php
// ============================================================
//  EduQueue – Configuration
//  On Railway: set these as environment variables in the dashboard.
//  Locally: values fall back to the defaults shown after ?:
// ============================================================

// ── DATABASE ─────────────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3307');
define('DB_NAME', getenv('DB_NAME') ?: 'eduqueue');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// ── APP ──────────────────────────────────────────────────────
define('BASE_URL',      rtrim(getenv('BASE_URL') ?: 'http://localhost/techno', '/'));
define('SCHOOL_NAME',   getenv('SCHOOL_NAME')   ?: 'Your School Name');
define('SCHOOL_DOMAIN', getenv('SCHOOL_DOMAIN') ?: '@school.edu');

// ── SMS (Semaphore) ───────────────────────────────────────────
define('SEMAPHORE_API_KEY',     getenv('SEMAPHORE_API_KEY')     ?: '');
define('SEMAPHORE_SENDER_NAME', getenv('SEMAPHORE_SENDER_NAME') ?: 'SCHOOLQ');

// ── reCAPTCHA v3 ─────────────────────────────────────────────
define('RECAPTCHA_SITE_KEY',   getenv('RECAPTCHA_SITE_KEY')   ?: '');
define('RECAPTCHA_SECRET_KEY', getenv('RECAPTCHA_SECRET_KEY') ?: '');
define('RECAPTCHA_MIN_SCORE',  0.5);

// ── QUEUE SETTINGS ───────────────────────────────────────────
define('SMS_NOTIFY_THRESHOLD', (int)(getenv('SMS_NOTIFY_THRESHOLD') ?: 3));
define('MAX_CALL_ATTEMPTS',    (int)(getenv('MAX_CALL_ATTEMPTS')    ?: 3));

// ── RATE LIMITING ─────────────────────────────────────────────
define('RATE_LIMIT_ATTEMPTS', (int)(getenv('RATE_LIMIT_ATTEMPTS') ?: 3));
define('RATE_LIMIT_MINUTES',  (int)(getenv('RATE_LIMIT_MINUTES')  ?: 10));

// ── TIMEZONE ─────────────────────────────────────────────────
date_default_timezone_set('Asia/Manila');