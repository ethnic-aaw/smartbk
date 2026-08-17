-- Migration v1.6.0: Google OAuth & User Approval System
-- Run this after smart_bk.sql is imported

USE smart_bk;

-- ============================================================
-- 1. Tabel users: Tambah kolom OAuth & Approval
-- ============================================================
ALTER TABLE users
    ADD COLUMN google_id VARCHAR(100) NULL UNIQUE AFTER password_hash,
    ADD COLUMN email VARCHAR(150) NULL UNIQUE AFTER google_id,
    ADD COLUMN email_verified_at TIMESTAMP NULL AFTER email,
    ADD COLUMN approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER status,
    ADD COLUMN approved_by INT NULL AFTER approval_status,
    ADD COLUMN approved_at TIMESTAMP NULL AFTER approved_by,
    ADD COLUMN registration_token VARCHAR(64) NULL UNIQUE AFTER approved_at,
    ADD COLUMN last_login_at TIMESTAMP NULL AFTER registration_token,
    ADD COLUMN siswa_id INT NULL AFTER last_login_at,
    ADD FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    ADD FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE SET NULL;

-- Update existing users (admin, guru bk, wali kelas) to approved
UPDATE users SET approval_status = 'approved', email_verified_at = NOW() WHERE role IN ('Admin', 'Guru BK', 'Wali Kelas');

-- ============================================================
-- 2. Tabel siswa: Tambah kolom email & google_id untuk mapping
-- ============================================================
ALTER TABLE siswa
    ADD COLUMN email VARCHAR(150) NULL UNIQUE AFTER nipd,
    ADD COLUMN google_id VARCHAR(100) NULL UNIQUE AFTER email;

-- ============================================================
-- 3. Tabel user_approvals: Log approval/rejection
-- ============================================================
CREATE TABLE IF NOT EXISTS user_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    approver_id INT NULL,
    action ENUM('approved','rejected') NOT NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_status (user_id, action)
);

-- ============================================================
-- 4. Index tambahan untuk performa
-- ============================================================
CREATE INDEX idx_users_google_id ON users(google_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_approval_status ON users(approval_status);
CREATE INDEX idx_users_siswa_id ON users(siswa_id);
CREATE INDEX idx_siswa_email ON siswa(email);
CREATE INDEX idx_siswa_google_id ON siswa(google_id);