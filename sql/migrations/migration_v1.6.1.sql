-- ============================================================
-- Smart BK - Migration v1.6.1
-- Fitur: Google OAuth + approval user
-- Tambah kolom hilang di users
-- ============================================================

USE smart_bk;

SET @col_email = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'smart_bk' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email'
);
SET @sql = IF(@col_email = 0,
  'ALTER TABLE users ADD COLUMN email VARCHAR(150) NULL UNIQUE AFTER username',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_google_id = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'smart_bk' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'google_id'
);
SET @sql = IF(@col_google_id = 0,
  'ALTER TABLE users ADD COLUMN google_id VARCHAR(150) NULL AFTER email',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_approval = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'smart_bk' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'approval_status'
);
SET @sql = IF(@col_approval = 0,
  'ALTER TABLE users ADD COLUMN approval_status ENUM("pending","approved","rejected") NOT NULL DEFAULT "approved" AFTER status',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_approved_by = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'smart_bk' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'approved_by'
);
SET @sql = IF(@col_approved_by = 0,
  'ALTER TABLE users ADD COLUMN approved_by INT NULL AFTER approval_status',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_approved_at = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'smart_bk' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'approved_at'
);
SET @sql = IF(@col_approved_at = 0,
  'ALTER TABLE users ADD COLUMN approved_at DATETIME NULL AFTER approved_by',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_email_verified = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'smart_bk' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verified_at'
);
SET @sql = IF(@col_email_verified = 0,
  'ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL AFTER approved_at',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE users MODIFY role ENUM('Admin','Guru BK','Wali Kelas','Guru','Siswa') NOT NULL;

SET @col_siswa_id = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'smart_bk' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'siswa_id'
);
SET @sql = IF(@col_siswa_id = 0,
  'ALTER TABLE users ADD COLUMN siswa_id INT NULL AFTER kelas_id',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
