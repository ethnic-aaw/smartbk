-- ============================================================
-- Smart BK - Migration v1.2.0
-- Fitur: Buku Tamu (Admin & Guru BK)
-- ============================================================
-- Jalankan file ini jika database smart_bk sudah ada
-- (bukan fresh install dari sql/smart_bk.sql)
-- ============================================================

USE smart_bk;

CREATE TABLE IF NOT EXISTS buku_tamu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  nama_tamu VARCHAR(150) NOT NULL,
  keperluan TEXT NULL,
  tindak_lanjut TEXT NULL,
  pencatat_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
