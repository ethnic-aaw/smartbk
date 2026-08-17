-- ============================================================
-- Smart BK - Migration: Bukti/Barang Bukti Pelanggaran
-- Menambah kolom lampiran bukti pada pelanggaran_siswa.
-- Idempotent: aman dijalankan berulang kali.
-- ============================================================
-- Jalankan jika database smart_bk sudah ada (bukan fresh install)
--   docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" smart_bk' < sql/migration_bukti_pelanggaran.sql
-- ============================================================

USE smart_bk;

SET @tbl = 'pelanggaran_siswa';

-- Kolom bukti_file
SET @col = 'bukti_file';
SET @cnt = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'smart_bk' AND TABLE_NAME = @tbl AND COLUMN_NAME = @col);
SET @sql = IF(@cnt = 0, CONCAT('ALTER TABLE ', @tbl, ' ADD COLUMN ', @col, ' VARCHAR(255) NULL AFTER pelapor_id'), 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- @col bukti_original
SET @col = 'bukti_original';
SET @cnt = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'smart_bk' AND TABLE_NAME = @tbl AND COLUMN_NAME = @col);
SET @sql = IF(@cnt = 0, CONCAT('ALTER TABLE ', @tbl, ' ADD COLUMN ', @col, ' VARCHAR(255) NULL AFTER bukti_file'), 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- @col bukti_type
SET @col = 'bukti_type';
SET @cnt = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'smart_bk' AND TABLE_NAME = @tbl AND COLUMN_NAME = @col);
SET @sql = IF(@cnt = 0, CONCAT('ALTER TABLE ', @tbl, ' ADD COLUMN ', @col, ' VARCHAR(50) NULL AFTER bukti_original'), 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- @col bukti_size
SET @col = 'bukti_size';
SET @cnt = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'smart_bk' AND TABLE_NAME = @tbl AND COLUMN_NAME = @col);
SET @sql = IF(@cnt = 0, CONCAT('ALTER TABLE ', @tbl, ' ADD COLUMN ', @col, ' INT NULL AFTER bukti_type'), 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;