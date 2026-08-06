-- Migration: tambah tabel log_generate untuk fitur Generate Tahun Ajaran (Undo/Rollback)
-- Jalankan di database smart_bk yang sudah ada (upgrade dari v1.0).

CREATE TABLE IF NOT EXISTS log_generate (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tahun_ajaran_lama VARCHAR(20) NOT NULL,
  tahun_ajaran_baru VARCHAR(20) NOT NULL,
  snapshot_json LONGTEXT NOT NULL,
  status ENUM('Aktif','Dibatalkan') NOT NULL DEFAULT 'Aktif',
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tahun_lama (tahun_ajaran_lama, status)
);
