-- ============================================================
-- Smart BK - Migration v1.6.2
-- Fitur: email/google mapping di siswa
-- ============================================================

USE smart_bk;

SET @col_email = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'smart_bk' AND TABLE_NAME = 'siswa' AND COLUMN_NAME = 'email'
);
SET @sql = IF(@col_email = 0,
  'ALTER TABLE siswa ADD COLUMN email VARCHAR(150) NULL UNIQUE AFTER nipd',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_google_id = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'smart_bk' AND TABLE_NAME = 'siswa' AND COLUMN_NAME = 'google_id'
);
SET @sql = IF(@col_google_id = 0,
  'ALTER TABLE siswa ADD COLUMN google_id VARCHAR(100) NULL UNIQUE AFTER email',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
