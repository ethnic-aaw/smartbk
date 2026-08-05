-- ============================================================
-- Smart BK - Migration v1.3.0
-- Fitur: Konsultasi Siswa (Guru BK & Admin)
-- ============================================================
-- Jalankan file ini jika database smart_bk sudah ada
-- (bukan fresh install dari sql/smart_bk.sql)
-- ============================================================

USE smart_bk;

CREATE TABLE IF NOT EXISTS konsultasi_siswa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  siswa_id INT NOT NULL,
  tanggal DATE NOT NULL,
  permasalahan TEXT NULL,
  tindak_lanjut TEXT NULL,
  konselor_id INT NULL,
  lampiran_file VARCHAR(255) NULL,
  lampiran_original VARCHAR(255) NULL,
  lampiran_type VARCHAR(50) NULL,
  lampiran_size INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_siswa_tanggal (siswa_id, tanggal)
);
