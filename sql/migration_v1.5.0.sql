-- ============================================================
-- Smart BK - Migration v1.5.0
-- Fitur: Tab biodata siswa + biodata orang tua (kolom orang tua)
-- ============================================================
-- Jalankan file ini jika database smart_bk sudah ada
-- (bukan fresh install dari sql/smart_bk.sql)
-- ============================================================

USE smart_bk;

SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'smart_bk' AND TABLE_NAME = 'siswa' AND COLUMN_NAME = 'nama_ayah'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE siswa
    ADD COLUMN nama_ayah VARCHAR(100) NULL AFTER no_hp_orang_tua,
    ADD COLUMN no_hp_ayah VARCHAR(20) NULL AFTER nama_ayah,
    ADD COLUMN pekerjaan_ayah VARCHAR(100) NULL AFTER no_hp_ayah,
    ADD COLUMN nama_ibu VARCHAR(100) NULL AFTER pekerjaan_ayah,
    ADD COLUMN no_hp_ibu VARCHAR(20) NULL AFTER nama_ibu,
    ADD COLUMN pekerjaan_ibu VARCHAR(100) NULL AFTER no_hp_ibu,
    ADD COLUMN nama_wali VARCHAR(100) NULL AFTER pekerjaan_ibu,
    ADD COLUMN alamat_orang_tua TEXT NULL AFTER nama_wali',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
