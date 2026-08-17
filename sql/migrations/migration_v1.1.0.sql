-- ============================================================
-- Smart BK - Migration v1.1.0
-- Fitur: Role-Based Access (Wali Kelas) + Import Siswa CSV
-- ============================================================
-- Jalankan file ini HANYA jika database smart_bk sudah ada
-- (bukan fresh install dari sql/smart_bk.sql)
-- ============================================================

USE smart_bk;

-- 1. Assign users.kelas_id untuk Wali Kelas berdasarkan relasi wali_kelas_id
--    (setiap Wali Kelas otomatis di-assign ke kelas yang ia ampu)
UPDATE users u
JOIN kelas k ON k.wali_kelas_id = u.id
SET u.kelas_id = k.id
WHERE u.role = 'Wali Kelas'
  AND u.kelas_id IS NULL;

-- 2. Perbaiki relasi wali_kelas_id di tabel kelas
--    (jika sebelumnya menunjuk ke user dengan role bukan Wali Kelas)
UPDATE kelas k
JOIN users u ON u.id = k.wali_kelas_id
SET k.wali_kelas_id = NULL
WHERE u.role NOT IN ('Wali Kelas');

-- 3. Pastikan users.kelas_id dan kelas.wali_kelas_id sinkron
--    (opsional: assign wali kelas ke kelasnya berdasarkan data yang ada)
UPDATE kelas k
JOIN users u ON u.kelas_id = k.id
SET k.wali_kelas_id = u.id
WHERE u.role = 'Wali Kelas'
  AND u.kelas_id IS NOT NULL;

-- 4. Verifikasi hasil
-- SELECT u.id, u.nama, u.role, u.kelas_id, k.nama_kelas
-- FROM users u
-- LEFT JOIN kelas k ON k.id = u.kelas_id
-- WHERE u.role = 'Wali Kelas';
