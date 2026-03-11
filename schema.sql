-- ============================================================
--  EduQueue – QR-Based Student Inquiry Queue Management System
--  Database Schema  (MySQL 8.x)
-- ============================================================

CREATE DATABASE IF NOT EXISTS eduqueue CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eduqueue;

-- ── USERS ────────────────────────────────────────────────────
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    email         VARCHAR(100) NOT NULL UNIQUE,
    full_name     VARCHAR(150) NOT NULL,
    student_id    VARCHAR(20)  DEFAULT NULL,
    role          ENUM('student','staff','admin') NOT NULL DEFAULT 'student',
    password_hash VARCHAR(255) NOT NULL,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── DEPARTMENTS ──────────────────────────────────────────────
CREATE TABLE departments (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    name                 VARCHAR(100) NOT NULL,
    prefix               CHAR(1)      NOT NULL UNIQUE,   -- R, C, A, G
    assigned_staff_id    INT          DEFAULT NULL,
    avg_service_minutes  INT          NOT NULL DEFAULT 3,
    is_open              TINYINT(1)   NOT NULL DEFAULT 1,
    FOREIGN KEY (assigned_staff_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ── QUEUE SESSIONS (daily QR) ────────────────────────────────
CREATE TABLE queue_sessions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    session_token  VARCHAR(64)  NOT NULL UNIQUE,
    session_date   DATE         NOT NULL UNIQUE,
    qr_image_path  VARCHAR(255) DEFAULT NULL,
    is_active      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at     DATETIME     NOT NULL
);

-- ── QUEUE COUNTERS (one row per dept, reset daily) ───────────
CREATE TABLE queue_counters (
    department_id    INT NOT NULL PRIMARY KEY,
    session_id       INT NOT NULL,
    last_issued      INT NOT NULL DEFAULT 0,
    current_serving  INT NOT NULL DEFAULT 0,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (session_id)    REFERENCES queue_sessions(id)
);

-- ── QUEUES (main) ────────────────────────────────────────────
CREATE TABLE queues (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    session_id     INT          NOT NULL,
    student_id     INT          NOT NULL,
    department_id  INT          NOT NULL,
    queue_number   VARCHAR(10)  NOT NULL,
    sequence       INT          NOT NULL,
    purpose        TEXT         NOT NULL,
    status         ENUM('waiting','serving','done','skipped','missed') NOT NULL DEFAULT 'waiting',
    call_count     INT          NOT NULL DEFAULT 0,
    joined_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    called_at      DATETIME     DEFAULT NULL,
    served_at      DATETIME     DEFAULT NULL,
    completed_at   DATETIME     DEFAULT NULL,
    notified_sms   TINYINT(1)   NOT NULL DEFAULT 0,
    sms_phone      VARCHAR(15)  DEFAULT NULL,
    FOREIGN KEY (session_id)    REFERENCES queue_sessions(id),
    FOREIGN KEY (student_id)    REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- ── SMS LOGS ─────────────────────────────────────────────────
CREATE TABLE sms_logs (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    queue_id  INT          NOT NULL,
    phone     VARCHAR(15)  NOT NULL,
    message   TEXT         NOT NULL,
    status    ENUM('sent','failed','pending') NOT NULL DEFAULT 'pending',
    sent_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (queue_id) REFERENCES queues(id)
);

-- ── RATE LIMITS ──────────────────────────────────────────────
CREATE TABLE rate_limits (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ip          VARCHAR(45) NOT NULL,
    action      VARCHAR(50) NOT NULL DEFAULT 'submit',
    created_at  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_action (ip, action, created_at)
);

-- ── SEED DATA ────────────────────────────────────────────────
INSERT INTO departments (name, prefix, avg_service_minutes, is_open) VALUES
  ('Registrar',  'R', 4, 1),
  ('Cashier',    'C', 3, 1),
  ('Admissions', 'A', 5, 1),
  ('Guidance',   'G', 6, 1);

-- Default admin account  (password: Admin@1234)
INSERT INTO users (email, full_name, role, password_hash) VALUES
  ('admin@school.edu', 'System Admin', 'admin',
   '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.'); -- bcrypt of "password" as placeholder

-- ─────────────────────────────────────────────────────────────
-- Change the admin password after first login!
-- ─────────────────────────────────────────────────────────────
