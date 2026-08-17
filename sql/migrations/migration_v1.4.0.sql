-- ============================================================
-- Smart BK - Migration v1.4.0
-- Fitur: Komponen jenis pelanggaran + Fase/Tahapan Pelanggaran
-- ============================================================
-- Jalankan file ini jika database smart_bk sudah ada
-- (bukan fresh install dari sql/smart_bk.sql)
-- ============================================================

USE smart_bk;

-- Kolom komponen pada master pelanggaran
SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = 'smart_bk' AND TABLE_NAME = 'jenis_pelanggaran' AND COLUMN_NAME = 'komponen'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE jenis_pelanggaran ADD COLUMN komponen VARCHAR(100) NULL AFTER kategori',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Tabel fase/tahapan pelanggaran
CREATE TABLE IF NOT EXISTS fase_pelanggaran (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kategori VARCHAR(50) NOT NULL,
  min_skor INT NOT NULL,
  max_skor INT NULL,
  tindak_lanjut VARCHAR(255) NULL,
  administrasi VARCHAR(255) NULL
);

DELETE FROM fase_pelanggaran;

INSERT INTO fase_pelanggaran (kategori, min_skor, max_skor, tindak_lanjut, administrasi) VALUES
('Pelanggaran Ringan', 1, 15, 'Peringatan ke 1 (Petugas Ketertiban / Wali Kelas)', NULL),
('Pelanggaran Ringan', 16, 29, 'Peringatan ke 2 (Petugas Ketertiban / Wali Kelas)', 'Surat Peringatan ke 1'),
('Pelanggaran Sedang', 30, 45, 'Panggilan Orang Tua Ke 1 / Home Visit (Wali Kelas)', NULL),
('Pelanggaran Sedang', 46, 50, 'Panggilan Orang Tua Ke 2 / Home Visit (Wali Kelas / Guru BK)', NULL),
('Pelanggaran Sedang', 51, 74, 'Panggilan Orang Tua Ke 3 / Home Visit (Koordinator BK)', 'Surat Peringatan Ke 2'),
('Pelanggaran Berat', 75, 85, 'Skorsing 1 (Wakasek Kesiswaan)', NULL),
('Pelanggaran Berat', 86, 99, 'Skorsing 2 (Wakasek Kesiswaan - Konferensi Kasus)', 'Surat Peringatan Ke 3'),
('Pelanggaran Berat', 100, NULL, 'Dikembalikan ke orang tua (Kepala Sekolah)', NULL);
