<?php
/**
 * Seed Master Tata Tertib SMK Negeri 1 Leuwimunding (56 jenis pelanggaran).
 *
 * Cara pakai (CLI):
 *   php sql/seed_tata_tertib.php            (ada konfirmasi interaktif)
 *   php sql/seed_tata_tertib.php --yes      (langsung eksekusi)
 *
 * Efek:
 *   - Menjamin kolom `komponen` ada di tabel jenis_pelanggaran.
 *   - Menghapus SEMUA pelanggaran_siswa, konsultasi_siswa, dan jenis_pelanggaran.
 *   - Mengisi 56 jenis pelanggaran sesuai tata tertib sekolah.
 *
 * Setelah import, jalankan `php sql/seed_demo_bk.php --yes`
 * untuk mengisi ulang data demo pelanggaran & konsultasi.
 */

require_once __DIR__ . '/../config/db.php';

if (!db_is_ready()) {
    fwrite(STDERR, 'Koneksi database tidak tersedia: ' . db_error() . PHP_EOL);
    exit(1);
}

if (!in_array('--yes', $argv, true)) {
    echo 'Script ini akan MENGHAPUS data pelanggaran_siswa, konsultasi_siswa, ';
    echo 'dan jenis_pelanggaran, lalu mengisi 56 item tata tertib.' . PHP_EOL;
    echo 'Lanjutkan? (y/N): ';
    $line = trim(fgets(STDIN));
    if (!in_array(strtolower($line), ['y', 'yes'], true)) {
        echo 'Dibatalkan.' . PHP_EOL;
        exit(0);
    }
}

global $mysqli;

// kode, uraian, komponen, kategori, poin, sanksi
$items = [
    // ============ 1. Kehadiran ============
    ['1.1.a', 'Siswa Terlambat Lebih dari pukul 07.00 (1-3 kali)', 'Kehadiran', 'Kedisiplinan', 2, 'Membersihkan lingkungan sekolah / teguran lisan'],
    ['1.1.b', 'Siswa Terlambat Lebih dari pukul 07.00 (4-7 kali dalam satu bulan)', 'Kehadiran', 'Kedisiplinan', 4, 'Peringatan tertulis/Surat Perjanjian'],
    ['1.1.c', 'Siswa Terlambat Lebih dari pukul 07.00 (lebih dari 7 kali dalam satu bulan)', 'Kehadiran', 'Kedisiplinan', 6, 'Pemanggilan orang tua'],
    ['1.2', 'Tidak Mengikuti Upacara Bendera/Apel Siang', 'Kehadiran', 'Kedisiplinan', 2, 'Membersihkan Lingkungan Sekolah'],
    ['1.3', 'Tidak Mengikuti Kegiatan Lain Di Sekolah', 'Kehadiran', 'Kedisiplinan', 2, 'Peringatan lisan'],
    ['1.4.a', '3 Hari tidak hadir tanpa keterangan dalam satu bulan', 'Kehadiran', 'Kedisiplinan', 4, 'Teguran lisan'],
    ['1.4.b', '5 Hari tidak hadir tanpa keterangan dalam satu bulan', 'Kehadiran', 'Kedisiplinan', 6, 'Surat panggilan orang tua'],

    // ============ 2. Kegiatan belajar mengajar ============
    ['2.1', 'Keluar ruangan tanpa izin pada saat kbm atau pergantian KBM', 'Kegiatan Belajar Mengajar', 'Kedisiplinan', 2, 'Peringatan lisan'],
    ['2.2', 'Siswa tidak memakai seragam praktik dan seragam olahraga sesuai jadwal KBM', 'Kegiatan Belajar Mengajar', 'Kedisiplinan', 2, 'Peringatan lisan'],
    ['2.3', 'Siswa mengoperasikan gadget pada jam kbm kecuali istirahat kbm sekolah (di butuhkan oleh guru mata pelajaran)', 'Kegiatan Belajar Mengajar', 'Kedisiplinan', 4, 'Peringatan lisan'],
    ['2.4', 'Siswa tidak mengerjakan (pr) atau tugas dari guru', 'Kegiatan Belajar Mengajar', 'Kedisiplinan', 5, 'Peringatan lisan'],
    ['2.5', 'Siswa hadir tapi tidak tatap muka di kelas', 'Kegiatan Belajar Mengajar', 'Kedisiplinan', 5, 'Surat panggilan orang tua'],

    // ============ 3. Pakaian seragam ============
    ['3.1', 'Memakai seragam sekolah yang tidak di jadwalkan', 'Pakaian Seragam', 'Tata Krama', 5, 'Membersihkan lingkungan sekolah'],
    ['3.2', 'Memakai pakaian ketat/rok mini/celana bagi siswa', 'Pakaian Seragam', 'Tata Krama', 4, 'Peringatan lisan'],
    ['3.3', 'Tidak memakai topi / atribut pada saat upacara bendera', 'Pakaian Seragam', 'Tata Krama', 3, 'Membersihkan lingkungan sekolah / Menempati barisan khusus'],
    ['3.4', 'Tidak memakai kaos kaki putih polos', 'Pakaian Seragam', 'Tata Krama', 2, 'Peringatan lisan'],
    ['3.5', 'Tidak memakai sepatu warna hitam (model warrior dan pantofel sesuai jadwal yang sudah ditentukan)', 'Pakaian Seragam', 'Tata Krama', 2, 'Selama di sekolah sepatu di lepas'],
    ['3.6', 'Tidak memakai dasi seragam yang ditentukan', 'Pakaian Seragam', 'Tata Krama', 2, 'Membersihkan lingkungan sekolah'],
    ['3.7', 'Tidak memasukan baju atasan kedalam rok atau celana kecuali baju muslim dan olah raga', 'Pakaian Seragam', 'Tata Krama', 3, 'Peringatan lisan'],
    ['3.8', 'Tidak memakai ikat pinggang/gesper warna hitam berlogo sekolah', 'Pakaian Seragam', 'Tata Krama', 2, 'Peringatan lisan'],
    ['3.9', 'Memakai rok/celana di pinggul bukan di pinggang', 'Pakaian Seragam', 'Tata Krama', 5, 'Peringatan lisan'],
    ['3.10', 'Memakai seragam yang tidak ditentukan oleh sekolah', 'Pakaian Seragam', 'Tata Krama', 5, 'Membersihkan lingkungan Sekolah'],

    // ============ 4. Makan dan minum ============
    ['4.1', 'Makan dan minum di dalam kelas / RPS pada saat KBM', 'Makan dan Minum', 'Tata Krama', 2, 'Membersihkan lingkungan Sekolah'],

    // ============ 5. Izin meninggalkan sekolah ============
    ['5.1', 'Membuat surat keterangan tidak benar / palsu', 'Izin Meninggalkan Sekolah', 'Kedisiplinan', 2, 'Surat peringatan 1'],
    ['5.2', 'Meninggalkan sekolah tanpa izin guru piket/wali kelas/guru yang mengajar', 'Izin Meninggalkan Sekolah', 'Kedisiplinan', 15, 'Dipanggil orang tua'],

    // ============ 6. Perkelahian ============
    ['6.1', 'Perkelahian Antar Siswa/Kelas/Kelompok Di Sekolah', 'Perkelahian', 'Kekerasan', 75, 'Surat peringatan 1/dipanggil orang tua (skorsing 10 hari)'],
    ['6.2', 'Perkelahian Antar Sekolah/Kelompok Luar Sekolah Yang Disebabkan Oleh Siswa/Siswi SMK Negeri 1 Leuwimunding', 'Perkelahian', 'Kekerasan', 75, 'Surat peringatan 2/dipanggil orang tua (skorsing 10 hari)'],
    ['6.3', 'Perkelahian Antar Sekolah/Kelompok Luar Sekolah Yang Penyebabnya Dari Pihak Luar Sekolah', 'Perkelahian', 'Kekerasan', 25, 'Surat peringatan 1/dipanggil orang tua'],
    ['6.4', 'Melawan Guru/Tata Usaha (TU) Secara Fisik', 'Perkelahian', 'Kekerasan', 75, 'Surat peringatan 2/dipanggil orang tua (skors 10 Hari)'],
    ['6.5', 'Membawa Senjata Tajam/Api Untuk Mengancam Orang Lain', 'Perkelahian', 'Kekerasan', 75, 'Surat peringatan 1/dipanggil orang tua'],
    ['6.6', 'Bertengkar Dengan Teman Sekelas Atau Lain Kelas', 'Perkelahian', 'Kekerasan', 10, 'Surat peringatan 1/dipanggil orang tua'],
    ['6.7', 'Melakukan Pemerasan Terhadap Siswa/Siswi SMK Atau Pihak Lain', 'Perkelahian', 'Kekerasan', 30, 'Surat peringatan 1/dipanggil orang tua'],

    // ============ 7. Praktik Kerja Lapangan (PKL) ============
    ['7.1', 'Melanggar Tata Tertib/Ketentuan Dunia Industri Pada Saat Prakerin Sehingga Dikeluarkan', 'Praktik Kerja Lapangan (PKL)', 'Kedisiplinan', 20, 'Surat peringatan 2/dipanggil orang tua (skors 10 Hari)'],
    ['7.2', 'Mencuri/Membuat Gaduh/Berkelahi/Berpacaran di Tempat Praktik Kerja Lapangan/PKL', 'Praktik Kerja Lapangan (PKL)', 'Kedisiplinan', 75, 'Surat peringatan 1/dipanggil orang tua'],
    ['7.3', 'Meninggalkan Tempat PKL Tanpa Izin Kepada Pihak Perusahaan Yang Bersangkutan Selama 3 Hari Berturut-Turut', 'Praktik Kerja Lapangan (PKL)', 'Kedisiplinan', 10, 'Surat peringatan 1/dipanggil orang tua'],

    // ============ 8. Kebersihan lingkungan ============
    ['8.1', 'Mencoret-coret/meja/tembok atau benda milik sekolah', 'Kebersihan Lingkungan', 'Lainnya', 20, 'Peringatan lisan'],
    ['8.2', 'Membuang sampah sembarangan di lingkungan sekolah', 'Kebersihan Lingkungan', 'Lainnya', 2, 'Peringatan lisan'],
    ['8.3', 'Tidak melaksanakan piket kebersihan sekolah', 'Kebersihan Lingkungan', 'Lainnya', 5, 'Peringatan lisan'],

    // ============ 9. Lain-lain ============
    ['9.1', 'Membuat gaduh didalam kelas/luar kelas', 'Lain-lain', 'Lainnya', 10, 'Peringatan lisan'],
    ['9.2', 'Mengisi daya gadget di sekolah kecuali pada saat istirahat', 'Lain-lain', 'Lainnya', 10, 'Peringatan lisan/dilepas'],
    ['9.3', 'Menggunakan perhiasan serta bermake-up berlebihan', 'Lain-lain', 'Lainnya', 5, 'Peringatan lisan/dilepas'],
    ['9.4', 'Siswa pria menggunakan gelang/anting/kalung', 'Lain-lain', 'Lainnya', 5, 'Peringatan lisan/dilepas'],
    ['9.5', 'Rambut di cat/warnai', 'Lain-lain', 'Lainnya', 5, 'Surat peringatan'],
    ['9.6', 'Rambut perempuan yang di urai', 'Lain-lain', 'Lainnya', 5, 'Peringatan lisan'],
    ['9.7', 'Membawa playing card/memainkannya dan sejenisnya', 'Lain-lain', 'Lainnya', 2, 'Peringatan lisan'],
    ['9.8', 'Membawa/menghisap rokok di lingkungan sekolah dan di luar sekolah menggunakan seragam sekolah', 'Lain-lain', 'Lainnya', 20, 'Surat peringatan 1/dipanggil orang tua'],
    ['9.9', 'Membawa buku, majalah, foto, kaset/cd, media, elektronik berisi pornografi', 'Lain-lain', 'Lainnya', 50, 'Surat peringatan 2/dipanggil orang tua'],
    ['9.10', 'Membawa dan menggunakan narkoba', 'Lain-lain', 'Narkoba', 100, 'Dikembalikan ke orang tua'],
    ['9.11', 'Membawa atau meminum minuman keras', 'Lain-lain', 'Narkoba', 100, 'Dikembalikan ke orang tua'],
    ['9.12.a', 'Rambut tidak sesuai dengan aturan sekolah 2x', 'Lain-lain', 'Lainnya', 2, 'Teguran lisan'],
    ['9.12.b', 'Rambut tidak sesuai dengan aturan sekolah 3x', 'Lain-lain', 'Lainnya', 2, 'Dirapihkan oleh pihak sekolah'],
    ['9.13', 'Bermain bola tidak pada tempat dan waktu istirahat atau praktek olahraga', 'Lain-lain', 'Lainnya', 2, 'Dikembalikan ke orang tua'],
    ['9.14', 'Siswa/i menikah secara dini/berkeluarga', 'Lain-lain', 'Lainnya', 100, 'Dikembalikan ke orang tua'],
    ['9.15', 'Siswa/i melakukan asusila kepada teman sekolah di sekolah atau di luar sekolah', 'Lain-lain', 'Lainnya', 100, 'Dikembalikan ke orang tua'],
    ['9.16', 'Siswa/siswi terlibat aksi perundungan/bullying di lingkungan sekolah dan pada saat KBM atau jam sekolah', 'Lain-lain', 'Kekerasan', 75, 'Surat peringatan 2/di skors 5 hari'],
    ['9.17', 'Siswa atau siswi membantu dan terlibat untuk mencelakai orang lain sehingga korban mengalami trauma fisik dan psikis yang berat', 'Lain-lain', 'Kekerasan', 100, 'Dikembalikan ke orang tua'],
];

// ============ PASTIKAN KOLOM KOMPONEN ============
$col = db_fetch(
    "SELECT COUNT(*) AS c FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'jenis_pelanggaran' AND COLUMN_NAME = 'komponen'",
    [],
    'row'
);
if (!(int) ($col['c'] ?? 0)) {
    if (!$mysqli->query('ALTER TABLE jenis_pelanggaran ADD COLUMN komponen VARCHAR(100) NULL AFTER kategori')) {
        fwrite(STDERR, 'Gagal menambah kolom komponen: ' . $mysqli->error . PHP_EOL);
        exit(1);
    }
    echo "Kolom komponen ditambahkan.\n";
}

// ============ BERSIHKAN DATA ============
$sebelum = [
    'jenis' => (int) (db_fetch('SELECT COUNT(*) AS c FROM jenis_pelanggaran', [], 'row')['c'] ?? 0),
    'pelanggaran' => (int) (db_fetch('SELECT COUNT(*) AS c FROM pelanggaran_siswa', [], 'row')['c'] ?? 0),
    'konsultasi' => (int) (db_fetch('SELECT COUNT(*) AS c FROM konsultasi_siswa', [], 'row')['c'] ?? 0),
];
echo "Menghapus data lama (jenis: {$sebelum['jenis']}, pelanggaran: {$sebelum['pelanggaran']}, konsultasi: {$sebelum['konsultasi']})...\n";
foreach (['DELETE FROM pelanggaran_siswa', 'DELETE FROM konsultasi_siswa', 'DELETE FROM jenis_pelanggaran'] as $sql) {
    $mysqli->query($sql);
}
foreach (['pelanggaran_siswa', 'konsultasi_siswa', 'jenis_pelanggaran'] as $tbl) {
    $mysqli->query("ALTER TABLE `$tbl` AUTO_INCREMENT = 1");
}

// ============ INSERT ============
$total = 0;
foreach ($items as $it) {
    [$kode, $nama, $komponen, $kategori, $poin, $sanksi] = $it;
    $ok = db_query(
        'INSERT INTO jenis_pelanggaran (kode, nama, komponen, kategori, bobot_poin, deskripsi, konsekuensi)
         VALUES (?, ?, ?, ?, ?, NULL, ?)',
        [$kode, $nama, $komponen, $kategori, $poin, $sanksi]
    );
    if (!$ok) {
        fwrite(STDERR, 'Gagal insert jenis ' . $kode . ' (' . $nama . '): ' . $mysqli->error . PHP_EOL);
        exit(1);
    }
    $total++;
}

// ============ RINGKASAN ============
echo "\n===== HASIL SEED TATA TERTIB =====\n";
echo 'Total jenis pelanggaran : ' . $total . "\n\n";
echo "Per komponen:\n";
foreach (db_fetch('SELECT komponen, COUNT(*) c FROM jenis_pelanggaran GROUP BY komponen ORDER BY MIN(id)') ?: [] as $r) {
    echo '  - ' . $r['komponen'] . ': ' . $r['c'] . "\n";
}
echo "\nContoh item:\n";
foreach (db_fetch('SELECT kode, nama, komponen, kategori, bobot_poin, konsekuensi FROM jenis_pelanggaran ORDER BY id LIMIT 5') ?: [] as $c) {
    echo '  - ' . $c['kode'] . ' | ' . $c['nama'] . ' | ' . $c['komponen'] . ' | ' . $c['kategori'] . ' | ' . $c['bobot_poin'] . " poin\n";
}
echo "\nSelesai. Jalankan `php sql/seed_demo_bk.php --yes` untuk mengisi ulang data demo.\n";
