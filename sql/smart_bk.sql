CREATE DATABASE IF NOT EXISTS smart_bk;
USE smart_bk;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  username VARCHAR(100) NOT NULL UNIQUE,
  google_id VARCHAR(150) NULL UNIQUE,
  email VARCHAR(150) NULL UNIQUE,
  email_verified_at DATETIME NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('Admin','Guru BK','Wali Kelas','Guru','Siswa') NOT NULL,
  kelas_id INT NULL,
  siswa_id INT NULL,
  status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Aktif',
  approval_status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  approved_by INT NULL,
  approved_at DATETIME NULL,
  registration_token VARCHAR(64) NULL UNIQUE,
  last_login_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_siswa_id (siswa_id)
);

CREATE TABLE IF NOT EXISTS kelas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_kelas VARCHAR(100) NOT NULL,
  tingkat VARCHAR(20) NOT NULL,
  wali_kelas_id INT NULL,
  tahun_ajaran VARCHAR(20) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS siswa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nipd VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(150) NULL UNIQUE,
  google_id VARCHAR(100) NULL UNIQUE,
  nama VARCHAR(100) NOT NULL,
  jenis_kelamin ENUM('L','P') NOT NULL,
  kelas_id INT NULL,
  tempat_lahir VARCHAR(100) NULL,
  tanggal_lahir DATE NULL,
  nama_orang_tua VARCHAR(100) NULL,
  no_hp_orang_tua VARCHAR(20) NULL,
  nama_ayah VARCHAR(100) NULL,
  no_hp_ayah VARCHAR(20) NULL,
  pekerjaan_ayah VARCHAR(100) NULL,
  nama_ibu VARCHAR(100) NULL,
  no_hp_ibu VARCHAR(20) NULL,
  pekerjaan_ibu VARCHAR(100) NULL,
  nama_wali VARCHAR(100) NULL,
  alamat_orang_tua TEXT NULL,
  foto VARCHAR(255) NULL,
  alamat TEXT NULL,
  status ENUM('Aktif','Tidak Aktif','Pindah','Lulus') NOT NULL DEFAULT 'Aktif',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS jenis_pelanggaran (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(20) NOT NULL UNIQUE,
  nama VARCHAR(150) NOT NULL,
  komponen VARCHAR(100) NULL,
  kategori ENUM('Kedisiplinan','Tata Krama','Kekerasan','Narkoba','Lainnya') NOT NULL,
  bobot_poin INT NOT NULL,
  deskripsi TEXT NULL,
  konsekuensi TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pelanggaran_siswa (
  id INT AUTO_INCREMENT PRIMARY KEY,
  siswa_id INT NOT NULL,
  jenis_pelanggaran_id INT NOT NULL,
  tanggal DATE NOT NULL,
  lokasi TEXT NULL,
  keterangan TEXT NULL,
  tindakan TEXT NULL,
  pelapor_id INT NULL,
  bukti_file VARCHAR(255) NULL,
  bukti_original VARCHAR(255) NULL,
  bukti_type VARCHAR(50) NULL,
  bukti_size INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS buku_tamu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tanggal DATE NOT NULL,
  nama_tamu VARCHAR(150) NOT NULL,
  keperluan TEXT NULL,
  tindak_lanjut TEXT NULL,
  pencatat_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

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

CREATE TABLE IF NOT EXISTS fase_pelanggaran (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kategori VARCHAR(50) NOT NULL,
  min_skor INT NOT NULL,
  max_skor INT NULL,
  tindak_lanjut VARCHAR(255) NULL,
  administrasi VARCHAR(255) NULL
);

INSERT INTO fase_pelanggaran (kategori, min_skor, max_skor, tindak_lanjut, administrasi) VALUES
('Pelanggaran Ringan', 1, 15, 'Peringatan ke 1 (Petugas Ketertiban / Wali Kelas)', NULL),
('Pelanggaran Ringan', 16, 29, 'Peringatan ke 2 (Petugas Ketertiban / Wali Kelas)', 'Surat Peringatan ke 1'),
('Pelanggaran Sedang', 30, 45, 'Panggilan Orang Tua Ke 1 / Home Visit (Wali Kelas)', NULL),
('Pelanggaran Sedang', 46, 50, 'Panggilan Orang Tua Ke 2 / Home Visit (Wali Kelas / Guru BK)', NULL),
('Pelanggaran Sedang', 51, 74, 'Panggilan Orang Tua Ke 3 / Home Visit (Koordinator BK)', 'Surat Peringatan Ke 2'),
('Pelanggaran Berat', 75, 85, 'Skorsing 1 (Wakasek Kesiswaan)', NULL),
('Pelanggaran Berat', 86, 99, 'Skorsing 2 (Wakasek Kesiswaan - Konferensi Kasus)', 'Surat Peringatan Ke 3'),
('Pelanggaran Berat', 100, NULL, 'Dikembalikan ke orang tua (Kepala Sekolah)', NULL);

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

INSERT INTO users (nama, username, password_hash, role, kelas_id, status, approval_status) VALUES
('Admin Smart BK', 'admin', '$2y$10$hw8zMzkNmQRGcfhNlFM5m.4y8j.l0fb0QLcYwpUc4i0e/0oB76trC', 'Admin', NULL, 'Aktif', 'approved'),
('Hana Fitri', 'hana@belajar.id', '$2y$10$hw8zMzkNmQRGcfhNlFM5m.4y8j.l0fb0QLcYwpUc4i0e/0oB76trC', 'Guru BK', NULL, 'Aktif', 'approved'),
('Rina Lestari', 'rina@belajar.id', '$2y$10$hw8zMzkNmQRGcfhNlFM5m.4y8j.l0fb0QLcYwpUc4i0e/0oB76trC', 'Wali Kelas', 1, 'Aktif', 'approved');

INSERT INTO kelas (nama_kelas, tingkat, wali_kelas_id, tahun_ajaran) VALUES
('X IPA 1', 'X', 3, '2024/2025'),
('X IPS 2', 'X', 3, '2024/2025');

INSERT INTO siswa (nipd, nama, jenis_kelamin, kelas_id, tempat_lahir, tanggal_lahir, nama_orang_tua, no_hp_orang_tua, alamat, status) VALUES
('2024001', 'Rizki Putra', 'L', 1, 'Bandung', '2008-04-02', 'Budi Santoso', '081234567890', 'Jl. Merdeka No. 10', 'Aktif'),
('2024002', 'Alya Nabila', 'P', 2, 'Jakarta', '2008-07-14', 'Dewi Lestari', '082345678901', 'Jl. Sudirman No. 22', 'Aktif');

INSERT INTO jenis_pelanggaran (kode, nama, kategori, bobot_poin, deskripsi, konsekuensi) VALUES
('PLG-001', 'Terlambat', 'Kedisiplinan', 10, 'Datang terlambat ke sekolah', 'Peringatan tertulis'),
('PLG-002', 'Merokok', 'Tata Krama', 25, 'Merokok di area sekolah', 'Pembinaan dan orang tua dipanggil'),
('PLG-003', 'Membolos', 'Kedisiplinan', 20, 'Meninggalkan jam pelajaran tanpa izin', 'Peringatan dan pembinaan'),
('PLG-004', 'Berkelahi', 'Kekerasan', 40, 'Terlibat perkelahian antar siswa', 'Panggilan orang tua dan skorsing');

INSERT INTO pelanggaran_siswa (siswa_id, jenis_pelanggaran_id, tanggal, lokasi, keterangan, tindakan, pelapor_id) VALUES
(1, 1, '2026-02-10', 'Gerbang sekolah', 'Terlambat masuk', 'Peringatan lisan', 1),
(1, 1, '2026-03-14', 'Gerbang sekolah', 'Terlambat 2x', 'Peringatan tertulis', 1),
(1, 2, '2026-04-05', 'Kantin', 'Merokok di kantin', 'Pembinaan BK', 1),
(1, 2, '2026-05-20', 'Mushola', 'Merokok di area mushola', 'Orang tua dipanggil', 1),
(1, 3, '2026-06-11', 'Lingkungan sekolah', 'Membolos jam pelajaran', 'Peringatan', 1),
(2, 1, '2026-02-22', 'Gerbang sekolah', 'Terlambat masuk', 'Peringatan lisan', 1),
(2, 4, '2026-07-08', 'Lapangan', 'Berkelahi dengan teman', 'Panggilan orang tua', 1);

-- ============================================================
-- Tabel user_approvals: Log approval/rejection
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
-- Indexes & Foreign Keys (setelah semua tabel dibuat)
-- ============================================================
CREATE INDEX idx_users_google_id ON users(google_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_approval_status ON users(approval_status);
CREATE INDEX idx_siswa_email ON siswa(email);
CREATE INDEX idx_siswa_google_id ON siswa(google_id);
