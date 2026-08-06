<?php
/**
 * Seed Data Realistis Tahun Ajaran 2024/2025 (Struktur SMK).
 *
 * Cara pakai (CLI):
 *   php sql/seed_2024_2025.php            (ada konfirmasi interaktif)
 *   php sql/seed_2024_2025.php --yes      (langsung eksekusi)
 *
 * Efek:
 *   - Menghapus data demo (pelanggaran_siswa, konsultasi_siswa, buku_tamu,
 *     siswa, kelas, log_generate, user role Wali Kelas).
 *   - Membuat 48 kelas (X/XI/XII) jurusan RPL, TKJ, TO, AKL, KU.
 *   - Membuat 48 akun Wali Kelas dan menempelkannya ke tiap kelas.
 *   - Generate ±1.600 siswa (32-36 per kelas) dengan identitas acak.
 *   - Admin, Guru BK, dan master pelanggaran TIDAK diubah.
 */

require_once __DIR__ . '/../config/db.php';

if (!db_is_ready()) {
    fwrite(STDERR, 'Koneksi database tidak tersedia: ' . db_error() . PHP_EOL);
    exit(1);
}

if (!in_array('--yes', $argv, true)) {
    echo 'Script ini akan MENGHAPUS data demo (siswa/kelas/user wali kelas) ';
    echo 'lalu mengisi struktur SMK 2024/2025.' . PHP_EOL;
    echo 'Lanjutkan? (y/N): ';
    $line = trim(fgets(STDIN));
    if (!in_array(strtolower($line), ['y', 'yes'], true)) {
        echo 'Dibatalkan.' . PHP_EOL;
        exit(0);
    }
}

global $mysqli;

$namaL = [
    'Rizki', 'Andi', 'Budi', 'Dimas', 'Eko', 'Fajar', 'Galih', 'Hendra', 'Iqbal',
    'Joko', 'Kevin', 'Lukman', 'Miftah', 'Naufal', 'Oka', 'Rahmat', 'Satria',
    'Teguh', 'Umar', 'Vino', 'Wahyu', 'Yoga', 'Zainal', 'Arif', 'Bagas', 'Candra',
    'Deni', 'Fikri', 'Hafiz', 'Ilham', 'Kresna', 'Rian', 'Surya', 'Taufik',
];
$namaP = [
    'Alya', 'Bella', 'Citra', 'Dewi', 'Eka', 'Fitri', 'Gita', 'Hana', 'Intan',
    'Jihan', 'Kartika', 'Laila', 'Maya', 'Nadia', 'Olivia', 'Putri', 'Ratna',
    'Sari', 'Tiara', 'Umi', 'Vina', 'Wulan', 'Yulia', 'Zahra', 'Aisyah', 'Dinda',
    'Farah', 'Ira', 'Ningsih', 'Rina', 'Salsabila', 'Nabila', 'Aulia', 'Melani',
];
$namaB = [
    'Santoso', 'Wijaya', 'Pratama', 'Saputra', 'Hidayat', 'Nugroho', 'Ramadhan',
    'Kusuma', 'Setiawan', 'Permata', 'Anggraini', 'Maharani', 'Firdaus', 'Utami',
    'Rahayu', 'Suryani', 'Handayani', 'Lestari', 'Purnama', 'Ardiansyah', 'Maulana',
    'Firmansyah', 'Kurniawan', 'Susanti', 'Agustina', 'Melati', 'Cahyani', 'Puspita',
    'Hartono', 'Siregar', 'Nasution', 'Sinaga', 'Ginting', 'Sihombing', 'Hasibuan',
    'Manurung', 'Simanjuntak', 'Wibowo', 'Pamungkas', 'Nirmala',
];
$kota = [
    'Jakarta', 'Bandung', 'Surabaya', 'Medan', 'Semarang', 'Makassar', 'Palembang',
    'Tangerang', 'Depok', 'Bekasi', 'Yogyakarta', 'Solo', 'Malang', 'Denpasar',
    'Padang', 'Pekanbaru', 'Banjarmasin', 'Pontianak', 'Balikpapan', 'Manado',
    'Cirebon', 'Tegal', 'Purwokerto', 'Sukabumi', 'Bogor',
];
$jalan = [
    'Merdeka', 'Sudirman', 'Gatot Subroto', 'Ahmad Yani', 'Diponegoro', 'Gajah Mada',
    'Pahlawan', 'Kemerdekaan', 'Raya Serang', 'Pemuda', 'Thamrin', 'Juanda',
    'Cendrawasih', 'Kenanga', 'Melati', 'Mawar', 'Flamboyan', 'Anggrek',
];

function nama_acak(bool $laki = true): string
{
    global $namaL, $namaP, $namaB;
    $depan = $laki ? $namaL[array_rand($namaL)] : $namaP[array_rand($namaP)];
    return $depan . ' ' . $namaB[array_rand($namaB)];
}

function tanggal_acak(int $tahunAwal, int $tahunAkhir): string
{
    $y = mt_rand($tahunAwal, $tahunAkhir);
    $m = mt_rand(1, 12);
    $d = mt_rand(1, 28);
    return sprintf('%04d-%02d-%02d', $y, $m, $d);
}

function hp_acak(): string
{
    return '08' . str_pad((string) mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
}

function alamat_acak(string $kota): string
{
    global $jalan;
    return 'Jl. ' . $jalan[array_rand($jalan)] . ' No. ' . mt_rand(1, 200) . ', ' . $kota;
}

function kota_acak(): string
{
    global $kota;
    return $kota[array_rand($kota)];
}

// ============================== STRUKTUR ==============================
$struktur = [
    'X'   => ['RPL' => 4, 'TKJ' => 4, 'TO' => 4, 'AKL' => 3, 'KU' => 2],
    'XI'  => ['RPL' => 4, 'TKJ' => 4, 'TO' => 4, 'AKL' => 3, 'KU' => 2],
    'XII' => ['RPL' => 2, 'TKJ' => 2, 'TO' => 5, 'AKL' => 3, 'KU' => 2],
];
$tahunAjaran = '2024/2025';
$passwordHash = '$2y$10$hw8zMzkNmQRGcfhNlFM5m.4y8j.l0fb0QLcYwpUc4i0e/0oB76trC'; // admin123

// ============================== BERSIHKAN DATA DEMO ==============================
echo "Menghapus data demo...\n";
foreach ([
    'DELETE FROM pelanggaran_siswa',
    'DELETE FROM konsultasi_siswa',
    'DELETE FROM buku_tamu',
    'DELETE FROM siswa',
    'DELETE FROM kelas',
    "DELETE FROM users WHERE role = 'Wali Kelas'",
    'DELETE FROM log_generate',
] as $sql) {
    $mysqli->query($sql);
}
foreach (['siswa', 'kelas', 'users', 'log_generate'] as $tbl) {
    $mysqli->query("ALTER TABLE `$tbl` AUTO_INCREMENT = 1");
}

// ============================== BUAT KELAS ==============================
$kelasList = []; // [id, nama_kelas, tingkat]
$totalKelas = 0;
foreach ($struktur as $tingkat => $jurusan) {
    foreach ($jurusan as $kode => $jumlah) {
        for ($i = 1; $i <= $jumlah; $i++) {
            $namaKelas = "$tingkat $kode $i";
            $ok = db_query(
                'INSERT INTO kelas (nama_kelas, tingkat, wali_kelas_id, tahun_ajaran) VALUES (?, ?, NULL, ?)',
                [$namaKelas, $tingkat, $tahunAjaran]
            );
            if (!$ok) {
                fwrite(STDERR, 'Gagal membuat kelas ' . $namaKelas . ': ' . $mysqli->error . PHP_EOL);
                exit(1);
            }
            $kelasList[] = ['id' => db_last_id(), 'nama_kelas' => $namaKelas, 'tingkat' => $tingkat];
            $totalKelas++;
        }
    }
}
echo "Kelas dibuat: $totalKelas\n";

// ============================== BUAT WALI KELAS ==============================
$idx = 0;
foreach ($kelasList as &$kelas) {
    $idx++;
    $laki = $idx % 2 === 1;
    $namaWali = ($laki ? 'Bpk. ' : 'Ibu. ') . nama_acak($laki);
    $username = 'walikelas_' . strtolower(str_replace(' ', '_', $kelas['nama_kelas'])) . '@belajar.id';
    $ok = db_query(
        'INSERT INTO users (nama, username, password_hash, role, kelas_id, status) VALUES (?, ?, ?, ?, ?, ?)',
        [$namaWali, $username, $passwordHash, 'Wali Kelas', $kelas['id'], 'Aktif']
    );
    if (!$ok) {
        fwrite(STDERR, 'Gagal membuat wali kelas ' . $username . ': ' . $mysqli->error . PHP_EOL);
        exit(1);
    }
    $waliId = db_last_id();
    db_query('UPDATE kelas SET wali_kelas_id = ? WHERE id = ?', [$waliId, $kelas['id']]);
    $kelas['wali'] = $username;
}
unset($kelas);
echo 'Wali kelas dibuat: ' . count($kelasList) . "\n";

// ============================== GENERATE SISWA ==============================
$tahunLahir = ['X' => [2008, 2009], 'XI' => [2007, 2008], 'XII' => [2006, 2007]];
$noUrut = 1;
$totalSiswa = 0;
$perTingkat = ['X' => 0, 'XI' => 0, 'XII' => 0];

foreach ($kelasList as $kelas) {
    $jumlah = mt_rand(32, 36);
    for ($i = 0; $i < $jumlah; $i++) {
        $laki = mt_rand(0, 1) === 1;
        $nama = nama_acak($laki);
        $jk = $laki ? 'L' : 'P';
        $nipd = '2024' . str_pad((string) $noUrut, 4, '0', STR_PAD_LEFT);
        $tempat = kota_acak();
        $lahir = tanggal_acak($tahunLahir[$kelas['tingkat']][0], $tahunLahir[$kelas['tingkat']][1]);
        $namaOrtu = ($laki ? 'Bpk. ' : 'Ibu. ') . nama_acak($laki);
        $noHp = hp_acak();
        $alamat = alamat_acak($tempat);

        $ok = db_query(
            'INSERT INTO siswa (nipd, nama, jenis_kelamin, kelas_id, tempat_lahir, tanggal_lahir, nama_orang_tua, no_hp_orang_tua, alamat, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$nipd, $nama, $jk, $kelas['id'], $tempat, $lahir, $namaOrtu, $noHp, $alamat, 'Aktif']
        );
        if (!$ok) {
            fwrite(STDERR, 'Gagal insert siswa ' . $nipd . ': ' . $mysqli->error . PHP_EOL);
            exit(1);
        }
        $noUrut++;
        $totalSiswa++;
        $perTingkat[$kelas['tingkat']]++;
    }
}

// ============================== RINGKASAN ==============================
echo "\n===== HASIL SEED 2024/2025 =====\n";
echo 'Total kelas : ' . $totalKelas . "\n";
foreach ($struktur as $tingkat => $jurusan) {
    $baris = [];
    foreach ($jurusan as $kode => $jumlah) {
        $baris[] = "$kode:$jumlah";
    }
    echo "  $tingkat  : " . implode(', ', $baris) . "  (kelas)\n";
}
echo 'Total siswa : ' . $totalSiswa . "\n";
echo '  X  : ' . $perTingkat['X'] . " siswa\n";
echo '  XI : ' . $perTingkat['XI'] . " siswa\n";
echo '  XII: ' . $perTingkat['XII'] . " siswa\n";
echo 'Wali kelas  : ' . count($kelasList) . " akun (password default: admin123)\n";
echo "\nContoh siswa:\n";
$contoh = db_fetch(
    'SELECT s.nipd, s.nama, s.jenis_kelamin, s.tanggal_lahir, k.nama_kelas, k.tahun_ajaran
     FROM siswa s JOIN kelas k ON k.id = s.kelas_id
     ORDER BY s.id LIMIT 3'
);
foreach (($contoh ?: []) as $c) {
    echo '  - ' . $c['nipd'] . ' | ' . $c['nama'] . ' (' . $c['jenis_kelamin'] . ') | ' . $c['nama_kelas'] . ' | ' . $c['tanggal_lahir'] . "\n";
}
echo "\nSelesai. Login admin di tahun ajaran 2024/2025 untuk melihat data.\n";
