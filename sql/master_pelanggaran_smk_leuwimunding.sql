-- ============================================================
-- MASTER JENIS PELANGGARAN — SMK NEGERI 1 LEUWIMUNDING
-- Kabupaten Majalengka
-- ============================================================
-- Jalankan: mysql -u root smart_bk < sql/master_pelanggaran_smk_leuwimunding.sql
-- CATATAN: Script ini akan MENGHAPUS semua data jenis_pelanggaran
--          yang ada, lalu menggantinya dengan data lengkap ini.
-- ============================================================

USE smart_bk;

-- Hapus data lama
DELETE FROM jenis_pelanggaran;
ALTER TABLE jenis_pelanggaran AUTO_INCREMENT = 1;

-- ============================================================
-- 1. KEHADIRAN
-- ============================================================
INSERT INTO jenis_pelanggaran (kode, nama, komponen, kategori, bobot_poin, deskripsi, konsekuensi) VALUES
('PLG-001', 'Terlambat (1-3 kali)',                         'Kehadiran', 'Kedisiplinan', 2,  'Siswa terlambat lebih dari pukul 07.00 (1-3 kali)',                             'Membersihkan lingkungan sekolah / teguran lisan'),
('PLG-002', 'Terlambat (4-7 kali/sebulan)',                 'Kehadiran', 'Kedisiplinan', 4,  'Siswa terlambat lebih dari pukul 07.00 (4-7 kali dalam satu bulan)',             'Peringatan tertulis / Surat Perjanjian'),
('PLG-003', 'Terlambat (>7 kali/sebulan)',                  'Kehadiran', 'Kedisiplinan', 6,  'Siswa terlambat lebih dari pukul 07.00 (lebih dari 7 kali dalam satu bulan)',    'Pemanggilan orang tua'),
('PLG-004', 'Tidak Mengikuti Upacara Bendera/Apel Siang',  'Kehadiran', 'Kedisiplinan', 2,  'Siswa tidak mengikuti upacara bendera atau apel siang',                         'Membersihkan lingkungan sekolah'),
('PLG-005', 'Tidak Mengikuti Kegiatan Lain Di Sekolah',    'Kehadiran', 'Kedisiplinan', 2,  'Siswa tidak mengikuti kegiatan lain di sekolah',                                'Peringatan lisan'),
('PLG-006', 'Tidak Hadir 3 Hari Tanpa Keterangan',         'Kehadiran', 'Kedisiplinan', 4,  '3 hari tidak hadir tanpa keterangan dalam satu bulan',                          'Teguran lisan'),
('PLG-007', 'Tidak Hadir 5 Hari Tanpa Keterangan',         'Kehadiran', 'Kedisiplinan', 6,  '5 hari tidak hadir tanpa keterangan dalam satu bulan',                          'Surat panggilan orang tua'),

-- ============================================================
-- 2. KEGIATAN BELAJAR MENGAJAR
-- ============================================================
('PLG-008', 'Keluar Ruangan Tanpa Izin Saat KBM',           'Kegiatan Belajar Mengajar', 'Kedisiplinan', 2,  'Keluar ruangan tanpa izin pada saat KBM atau pergantian KBM',                   'Peringatan lisan'),
('PLG-009', 'Tidak Memakai Seragam Praktik/Olahraga',      'Kegiatan Belajar Mengajar', 'Tata Krama',   2,  'Siswa tidak memakai seragam praktik dan seragam olahraga sesuai jadwal KBM',     'Peringatan lisan'),
('PLG-010', 'Mengoperasikan Gadget Saat KBM',               'Kegiatan Belajar Mengajar', 'Kedisiplinan', 4,  'Siswa mengoperasikan gadget pada jam KBM kecuali istirahat/kebutuhan guru',     'Peringatan lisan'),
('PLG-011', 'Tidak Mengerjakan PR/Tugas Guru',             'Kegiatan Belajar Mengajar', 'Kedisiplinan', 5,  'Siswa tidak mengerjakan PR atau tugas dari guru',                              'Peringatan lisan'),
('PLG-012', 'Hadir Tidak Tatap Muka Di Kelas',             'Kegiatan Belajar Mengajar', 'Kedisiplinan', 5,  'Siswa hadir tapi tidak tatap muka di kelas',                                   'Surat panggilan orang tua'),

-- ============================================================
-- 3. PAKAIAN SERAGAM
-- ============================================================
('PLG-013', 'Memakai Seragam Tidak Di Jadwalkan',           'Pakaian Seragam', 'Tata Krama', 5,  'Memakai seragam sekolah yang tidak dijadwalkan',                                'Membersihkan lingkungan sekolah'),
('PLG-014', 'Memakai Pakaian Ketat/Rok Mini/Celana',       'Pakaian Seragam', 'Tata Krama', 4,  'Memakai pakaian ketat / rok mini / celana bagi siswa',                          'Peringatan lisan'),
('PLG-015', 'Tidak Memakai Topi/Atribut Saat Upacara',     'Pakaian Seragam', 'Tata Krama', 3,  'Tidak memakai topi / atribut pada saat upacara bendera',                        'Membersihkan lingkungan sekolah / menempati barisan khusus'),
('PLG-016', 'Tidak Memakai Kaos Kaki Putih Polos',         'Pakaian Seragam', 'Tata Krama', 2,  'Tidak memakai kaos kaki putih polos',                                           'Peringatan lisan'),
('PLG-017', 'Tidak Memakai Sepatu Hitam Sesuai Jadwal',    'Pakaian Seragam', 'Tata Krama', 2,  'Tidak memakai sepatu warna hitam (model warrior dan pantofel sesuai jadwal)',    'Selama di sekolah sepatu dilepas'),
('PLG-018', 'Tidak Memakai Dasi Seragam',                  'Pakaian Seragam', 'Tata Krama', 2,  'Tidak memakai dasi seragam yang ditentukan',                                    'Membersihkan lingkungan sekolah'),
('PLG-019', 'Baju Atasan Tidak Dimasukkan',                'Pakaian Seragam', 'Tata Krama', 3,  'Tidak memasukkan baju atasan ke dalam rok/celana (kecuali baju muslim & olahraga)', 'Peringatan lisan'),
('PLG-020', 'Tidak Memakai Ikat Pinggang/Gesper',          'Pakaian Seragam', 'Tata Krama', 2,  'Tidak memakai ikat pinggang/gesper warna hitam berlogo sekolah',                'Peringatan lisan'),
('PLG-021', 'Memakai Rok/Celana Di Pinggul',               'Pakaian Seragam', 'Tata Krama', 5,  'Memakai rok/celana di pinggul bukan di pinggang',                               'Peringatan lisan'),
('PLG-022', 'Memakai Seragam Tidak Ditentukan Sekolah',    'Pakaian Seragam', 'Tata Krama', 5,  'Memakai seragam yang tidak ditentukan oleh sekolah',                            'Membersihkan lingkungan sekolah'),

-- ============================================================
-- 4. MAKAN DAN MINUM
-- ============================================================
('PLG-023', 'Makan/Minum Di Dalam Kelas Saat KBM',          'Makan dan Minum', 'Tata Krama', 2,  'Makan dan minum di dalam kelas / RPS pada saat KBM',                            'Membersihkan lingkungan sekolah'),

-- ============================================================
-- 5. IZIN MENINGGALKAN SEKOLAH
-- ============================================================
('PLG-024', 'Membuat Surat Keterangan Palsu',               'Izin Meninggalkan Sekolah', 'Kedisiplinan', 2,  'Membuat surat keterangan tidak benar / palsu',                                 'Surat peringatan 1'),
('PLG-025', 'Meninggalkan Sekolah Tanpa Izin',             'Izin Meninggalkan Sekolah', 'Kedisiplinan', 15, 'Meninggalkan sekolah tanpa izin guru piket / wali kelas / guru yang mengajar',  'Dipanggil orang tua'),

-- ============================================================
-- 6. PERKELAHIAN
-- ============================================================
('PLG-026', 'Perkelahian Antar Siswa/Kelas/Kelompok Di Sekolah',                                          'Perkelahian', 'Kekerasan', 75, 'Perkelahian antar siswa/kelas/kelompok di sekolah',                                                     'Surat peringatan 1 / dipanggil orang tua (skorsing 10 hari)'),
('PLG-027', 'Perkelahian Antar Sekolah Yang Disebabkan Oleh Siswa SMK N 1 Leuwimunding',                   'Perkelahian', 'Kekerasan', 75, 'Perkelahian antar sekolah/kelompok luar sekolah yang disebabkan oleh siswa SMK N 1 Leuwimunding',       'Surat peringatan 2 / dipanggil orang tua (skorsing 10 hari)'),
('PLG-028', 'Perkelahian Antar Sekolah (Penyebab Dari Pihak Luar)',                                       'Perkelahian', 'Kekerasan', 25, 'Perkelahian antar sekolah/kelompok luar sekolah yang penyebabnya dari pihak luar sekolah',              'Surat peringatan 1 / dipanggil orang tua'),
('PLG-029', 'Melawan Guru/TU Secara Fisik',                                                               'Perkelahian', 'Kekerasan', 75, 'Melawan guru / tata usaha (TU) secara fisik',                                                          'Surat peringatan 2 / dipanggil orang tua (skors 10 hari)'),
('PLG-030', 'Membawa Senjata Tajam/Api Untuk Mengancam',                                                  'Perkelahian', 'Kekerasan', 75, 'Membawa senjata tajam/api untuk mengancam orang lain',                                                 'Surat peringatan 1 / dipanggil orang tua'),
('PLG-031', 'Bertengkar Dengan Teman Sekelas/Lain Kelas',                                                'Perkelahian', 'Kekerasan', 10, 'Bertengkar dengan teman sekelas atau lain kelas',                                                      'Surat peringatan 1 / dipanggil orang tua'),
('PLG-032', 'Melakukan Pemerasan Terhadap Siswa/Pihak Lain',                                              'Perkelahian', 'Kekerasan', 30, 'Melakukan pemerasan terhadap siswa/siswi SMK atau pihak lain',                                         'Surat peringatan 1 / dipanggil orang tua'),

-- ============================================================
-- 7. PRAKTIK KERJA LAPANGAN (PKL)
-- ============================================================
('PLG-033', 'Melanggar Tata Tertib Dunia Industri Saat Prakerin (Dikeluarkan)',   'Praktik Kerja Lapangan', 'Kedisiplinan', 20, 'Melanggar tata tertib/ketentuan dunia industri pada saat prakerin sehingga dikeluarkan',                   'Surat peringatan 2 / dipanggil orang tua (skors 10 hari)'),
('PLG-034', 'Mencuri/Gaduh/Berkelahi/Berpacaran Di Tempat PKL',                   'Praktik Kerja Lapangan', 'Kekerasan',   75, 'Mencuri / membuat gaduh / berkelahi / berpacaran di tempat praktik kerja lapangan/PKL',                   'Surat peringatan 1 / dipanggil orang tua'),
('PLG-035', 'Meninggalkan Tempat PKL Tanpa Izin 3 Hari Berturut-Turut',           'Praktik Kerja Lapangan', 'Kedisiplinan', 10, 'Meninggalkan tempat PKL tanpa izin kepada pihak perusahaan yang bersangkutan selama 3 hari berturut-turut', 'Surat peringatan 1 / dipanggil orang tua'),

-- ============================================================
-- 8. KEBERSIHAN LINGKUNGAN
-- ============================================================
('PLG-036', 'Mencoret-coret Meja/Tembok/Benda Sekolah',    'Kebersihan Lingkungan', 'Lainnya', 20, 'Mencoret-coret meja/tembok atau benda milik sekolah',   'Peringatan lisan'),
('PLG-037', 'Membuang Sampah Sembarangan',                 'Kebersihan Lingkungan', 'Lainnya', 2,  'Membuang sampah sembarangan di lingkungan sekolah',      'Peringatan lisan'),
('PLG-038', 'Tidak Melaksanakan Piket Kebersihan',         'Kebersihan Lingkungan', 'Lainnya', 5,  'Tidak melaksanakan piket kebersihan sekolah',            'Peringatan lisan'),

-- ============================================================
-- 9. LAIN-LAIN
-- ============================================================
('PLG-039', 'Membuat Gaduh Di Dalam/Luar Kelas',           'Lain-lain', 'Tata Krama', 10, 'Membuat gaduh di dalam kelas / luar kelas',                                              'Peringatan lisan'),
('PLG-040', 'Mengisi Daya Gadget Saat KBM',                'Lain-lain', 'Kedisiplinan', 10, 'Mengisi daya gadget di sekolah kecuali pada saat istirahat',                              'Peringatan lisan / dilepas'),
('PLG-041', 'Menggunakan Perhiasan/Bermake-up Berlebihan', 'Lain-lain', 'Tata Krama', 5,  'Menggunakan perhiasan serta bermake-up berlebihan',                                        'Peringatan lisan / dilepas'),
('PLG-042', 'Siswa Pria Menggunakan Gelang/Anting/Kalung', 'Lain-lain', 'Tata Krama', 5,  'Siswa pria menggunakan gelang/anting/kalung',                                               'Peringatan lisan / dilepas'),
('PLG-043', 'Rambut Dicat/Diwarnai',                       'Lain-lain', 'Tata Krama', 5,  'Rambut di cat / warnai',                                                                     'Surat peringatan'),
('PLG-044', 'Rambut Perempuan Diurai',                     'Lain-lain', 'Tata Krama', 5,  'Rambut perempuan yang diurai',                                                               'Peringatan lisan'),
('PLG-045', 'Membawa Playing Card/Memainkannya',           'Lain-lain', 'Lainnya', 2,  'Membawa playing card / memainkannya dan sejenisnya',                                         'Peringatan lisan'),
('PLG-046', 'Membawa/Menghisap Rokok Di Sekolah/Dengan Seragam', 'Lain-lain', 'Narkoba', 20, 'Membawa/menghisap rokok di lingkungan sekolah dan di luar sekolah menggunakan seragam sekolah', 'Surat peringatan 1 / dipanggil orang tua'),
('PLG-047', 'Membawa Media Pornografi',                    'Lain-lain', 'Narkoba', 50, 'Membawa buku, majalah, foto, kaset/CD, media, elektronik berisi pornografi',               'Surat peringatan 2 / dipanggil orang tua'),
('PLG-048', 'Membawa/Menggunakan Narkoba',                 'Lain-lain', 'Narkoba', 100, 'Membawa dan menggunakan narkoba',                                                           'Dikembalikan ke orang tua'),
('PLG-049', 'Membawa/Meminum Minuman Keras',               'Lain-lain', 'Narkoba', 100, 'Membawa atau meminum minuman keras',                                                        'Dikembalikan ke orang tua'),
('PLG-050', 'Rambut Tidak Sesuai Aturan (2x)',             'Lain-lain', 'Tata Krama', 2,  'Rambut tidak sesuai dengan aturan sekolah 2x',                                               'Teguran lisan'),
('PLG-051', 'Rambut Tidak Sesuai Aturan (3x)',             'Lain-lain', 'Tata Krama', 2,  'Rambut tidak sesuai dengan aturan sekolah 3x',                                               'Dirapihkan oleh pihak sekolah'),
('PLG-052', 'Bermain Bola Diluar Waktu/Waktu Istirahat',   'Lain-lain', 'Lainnya', 2,  'Bermain bola tidak pada tempat dan waktu istirahat atau praktek olahraga',                   'Dikembalikan ke orang tua'),
('PLG-053', 'Menikah Dini/Berkeluarga',                    'Lain-lain', 'Lainnya', 100, 'Siswa/i menikah secara dini / berkeluarga',                                                  'Dikembalikan ke orang tua'),
('PLG-054', 'Melakukan Asusila Kepada Teman',              'Lain-lain', 'Kekerasan', 100, 'Siswa/i melakukan asusila kepada teman sekolah di sekolah atau di luar sekolah',              'Dikembalikan ke orang tua'),
('PLG-055', 'Terlibat Perundungan/Bullying',               'Lain-lain', 'Kekerasan', 75, 'Siswa/siswi terlibat aksi perundungan/bullying di lingkungan sekolah dan pada saat KBM/jam sekolah', 'Surat peringatan 2 / di skors 5 hari'),
('PLG-056', 'Membantu/Terbabit Mencelakai Orang Lain',     'Lain-lain', 'Kekerasan', 100, 'Siswa/siswi membantu dan terlibat untuk mencelakai orang lain sehingga korban mengalami trauma fisik dan psikis yang berat', 'Dikembalikan ke orang tua');


-- ============================================================
-- FASE / TAHAPAN PELANGGARAN
-- ============================================================
DELETE FROM fase_pelanggaran;
ALTER TABLE fase_pelanggaran AUTO_INCREMENT = 1;

INSERT INTO fase_pelanggaran (kategori, min_skor, max_skor, tindak_lanjut, administrasi) VALUES
('Pelanggaran Ringan',  1,  15, 'Peringatan ke 1 (Petugas Ketertiban / Wali Kelas)',                          NULL),
('Pelanggaran Ringan',  16, 29, 'Peringatan ke 2 (Petugas Ketertiban / Wali Kelas)',                          'Surat Peringatan ke 1'),
('Pelanggaran Sedang',  30, 45, 'Panggilan Orang Tua Ke 1 / Home Visit (Wali Kelas)',                         NULL),
('Pelanggaran Sedang',  46, 50, 'Panggilan Orang Tua Ke 2 / Home Visit (Wali Kelas / Guru BK)',               NULL),
('Pelanggaran Sedang',  51, 74, 'Panggilan Orang Tua Ke 3 / Home Visit (Koordinator BK)',                     'Surat Peringatan Ke 2'),
('Pelanggaran Berat',   75, 85, 'Skorsing 1 (Wakasek Kesiswaan)',                                            NULL),
('Pelanggaran Berat',   86, 99, 'Skorsing 2 (Wakasek Kesiswaan - Konferensi Kasus)',                          'Surat Peringatan Ke 3'),
('Pelanggaran Berat',   100, NULL, 'Dikembalikan ke orang tua (Kepala Sekolah)',                              NULL);


-- ============================================================
-- RINGKASAN
-- ============================================================
SELECT '===== MASTER PELANGGARAN SMK N 1 LEUWIMUNDING =====' AS info;
SELECT kategori, COUNT(*) AS jumlah, MIN(bobot_poin) AS poin_min, MAX(bobot_poin) AS poin_max
FROM jenis_pelanggaran
GROUP BY kategori
ORDER BY poin_min ASC;

SELECT komponen, COUNT(*) AS jumlah
FROM jenis_pelanggaran
GROUP BY komponen
ORDER BY komponen;

SELECT '===== FASE PELANGGARAN =====' AS info;
SELECT * FROM fase_pelanggaran ORDER BY min_skor;
