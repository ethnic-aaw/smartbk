<?php
/**
 * Seed Data Demo Bimbingan Konseling (Pelanggaran & Konsultasi) untuk Tahun Ajaran Berjalan.
 *
 * Cara pakai (CLI):
 *   php sql/seed_demo_bk.php            (ada konfirmasi interaktif)
 *   php sql/seed_demo_bk.php --yes      (langsung eksekusi)
 *
 * Efek:
 *   - Menghapus SEMUA data pelanggaran_siswa dan konsultasi_siswa.
 *   - Setiap kelas: 2 siswa acak diberi 1-2 catatan pelanggaran dan 1 catatan konsultasi.
 *   - Pelanggaran dicatat oleh Wali Kelas kelas tersebut; konsultasi oleh Guru BK.
 */

require_once __DIR__ . '/../config/db.php';

if (!db_is_ready()) {
    fwrite(STDERR, 'Koneksi database tidak tersedia: ' . db_error() . PHP_EOL);
    exit(1);
}

if (!in_array('--yes', $argv, true)) {
    echo 'Script ini akan MENGHAPUS semua data pelanggaran_siswa dan konsultasi_siswa ';
    echo 'lalu mengisi data demo BK (2 siswa/kelas).' . PHP_EOL;
    echo 'Lanjutkan? (y/N): ';
    $line = trim(fgets(STDIN));
    if (!in_array(strtolower($line), ['y', 'yes'], true)) {
        echo 'Dibatalkan.' . PHP_EOL;
        exit(0);
    }
}

global $mysqli;

$tahunAjaran = '2024/2025';

$lokasiList = [
    'Gerbang sekolah',
    'Kelas',
    'Kantin',
    'Lapangan upacara',
    'Mushola',
    'Halaman belakang',
    'Toilet sekolah',
    'Lingkungan sekolah',
];
$keteranganList = [
    'Datang terlambat masuk sekolah',
    'Terlambat masuk setelah jam istirahat',
    'Tidak mengikuti jam pelajaran',
    'Terlambat saat jam pertama',
    'Meninggalkan kelas tanpa izin',
];
$tindakanList = [
    'Peringatan lisan',
    'Peringatan tertulis',
    'Pembinaan BK',
    'Menulis surat pernyataan',
    'Panggilan orang tua',
];
$kategoriLokasi = [
    'Kedisiplinan' => ['Gerbang sekolah', 'Kelas', 'Lapangan upacara', 'Lingkungan sekolah'],
    'Tata Krama' => ['Kelas', 'Kantin', 'Lapangan upacara'],
    'Kekerasan' => ['Kelas', 'Halaman belakang', 'Lingkungan sekolah'],
    'Narkoba' => ['Mushola', 'Toilet sekolah', 'Halaman belakang', 'Kantin'],
    'Lainnya' => ['Kelas', 'Halaman belakang', 'Lingkungan sekolah', 'Kantin'],
];

$konsultasiTema = [
    'Kedisiplinan' => [
        'permasalahan' => 'Siswa sering datang terlambat dan kesulitan bangun pagi.',
        'tindak_lanjut' => 'Pemberian motivasi disiplin waktu, kerja sama dengan orang tua untuk mengatur jam tidur, monitoring kehadiran selama 2 minggu.',
    ],
    'Tata Krama' => [
        'permasalahan' => 'Siswa kedapatan melanggar tata tertib tata krama dan berpakaian.',
        'tindak_lanjut' => 'Pembinaan tata krama, penguatan pergaulan positif, koordinasi dengan wali kelas dan orang tua.',
    ],
    'Kekerasan' => [
        'permasalahan' => 'Siswa terlibat perkelahian karena masalah komunikasi dengan teman.',
        'tindak_lanjut' => 'Mediasi dengan teman yang berselisih, pelatihan pengelolaan emosi, pemantauan intensif oleh wali kelas.',
    ],
    'Narkoba' => [
        'permasalahan' => 'Siswa kedapatan membawa/menghisap rokok atau barang terlarang di lingkungan sekolah.',
        'tindak_lanjut' => 'Pembinaan bahaya zat adiktif, penguatan pergaulan positif, koordinasi dengan wali kelas dan orang tua.',
    ],
    'Lainnya' => [
        'permasalahan' => 'Siswa kurang disiplin dalam menaati aturan tata tertib sekolah.',
        'tindak_lanjut' => 'Konseling motivasi, identifikasi penyebab, rencana perbaikan yang disepakati bersama.',
    ],
];

function tanggal_acak_tahun_ajaran(string $dari = '2024-07-15', string $sampai = '2025-05-30'): string
{
    $start = strtotime($dari);
    $end = strtotime($sampai);
    $ts = mt_rand($start, $end);
    return date('Y-m-d', $ts);
}

function tanggal_setelah(string $base, int $minHari = 3, int $maxHari = 21): string
{
    $ts = strtotime($base . ' +' . mt_rand($minHari, $maxHari) . ' days');
    return date('Y-m-d', $ts);
}

// ============================== BERSIHKAN DATA ==============================
$sebelum = [
    'pelanggaran' => (int) (db_fetch('SELECT COUNT(*) AS c FROM pelanggaran_siswa', [], 'row')['c'] ?? 0),
    'konsultasi' => (int) (db_fetch('SELECT COUNT(*) AS c FROM konsultasi_siswa', [], 'row')['c'] ?? 0),
];
echo "Menghapus data lama (pelanggaran: {$sebelum['pelanggaran']}, konsultasi: {$sebelum['konsultasi']})...\n";
foreach (['DELETE FROM pelanggaran_siswa', 'DELETE FROM konsultasi_siswa'] as $sql) {
    $mysqli->query($sql);
}
foreach (['pelanggaran_siswa', 'konsultasi_siswa'] as $tbl) {
    $mysqli->query("ALTER TABLE `$tbl` AUTO_INCREMENT = 1");
}

// ============================== AMBIL REFERENSI ==============================
$kelasList = db_fetch(
    'SELECT k.id, k.nama_kelas, k.tingkat, u.id AS wali_id, u.nama AS wali_nama
     FROM kelas k
     LEFT JOIN users u ON u.kelas_id = k.id AND u.role = ?
     WHERE k.tahun_ajaran = ?
     ORDER BY k.id ASC',
    ['Wali Kelas', $tahunAjaran]
);
if (!$kelasList) {
    fwrite(STDERR, 'Tidak ada kelas untuk tahun ajaran ' . $tahunAjaran . PHP_EOL);
    exit(1);
}

$jenisList = db_fetch('SELECT id, nama, kategori, bobot_poin FROM jenis_pelanggaran ORDER BY id');
if (!$jenisList || count($jenisList) === 0) {
    fwrite(STDERR, 'Master jenis pelanggaran kosong. Import terlebih dahulu.' . PHP_EOL);
    exit(1);
}

$guruBk = db_fetch("SELECT id FROM users WHERE role = 'Guru BK' AND status = 'Aktif' ORDER BY id LIMIT 1", [], 'row');
$konselorId = $guruBk ? (int) $guruBk['id'] : null;
if (!$konselorId) {
    echo "PERHATIAN: tidak ada akun Guru BK, konsultasi akan dibuat tanpa konselor.\n";
}

// ============================== GENERATE DATA ==============================
$totalPelanggaran = 0;
$totalKonsultasi = 0;
$kelasTanpaData = 0;

foreach ($kelasList as $kelas) {
    $siswaKelas = db_fetch(
        'SELECT id, nama, jenis_kelamin FROM siswa WHERE kelas_id = ? AND status = ?',
        [$kelas['id'], 'Aktif']
    ) ?: [];

    if (count($siswaKelas) < 2) {
        $kelasTanpaData++;
        continue;
    }

    $keys = array_rand($siswaKelas, 2);
    if (!is_array($keys)) {
        $keys = [$keys];
    }

    foreach ($keys as $idx) {
        $siswa = $siswaKelas[$idx];
        $jumlahPelanggaran = mt_rand(1, 2);
        $tanggalTerakhir = null;
        $jenisIds = [];
        $kategoriPertama = null;

        for ($i = 0; $i < $jumlahPelanggaran; $i++) {
            $jenis = $jenisList[array_rand($jenisList)];
            $jenisId = (int) $jenis['id'];
            if (in_array($jenisId, $jenisIds, true)) {
                continue;
            }
            $jenisIds[] = $jenisId;
            if ($kategoriPertama === null) {
                $kategoriPertama = $jenis['kategori'];
            }

            $tanggal = tanggal_acak_tahun_ajaran();
            $lokasiPool = $kategoriLokasi[$jenis['kategori']] ?? $lokasiList;
            $lokasi = $lokasiPool[array_rand($lokasiPool)];
            $keterangan = $keteranganList[array_rand($keteranganList)];
            $tindakan = $tindakanList[array_rand($tindakanList)];
            $pelaporId = $kelas['wali_id'] ? (int) $kelas['wali_id'] : null;

            $ok = db_query(
                'INSERT INTO pelanggaran_siswa (siswa_id, jenis_pelanggaran_id, tanggal, lokasi, keterangan, tindakan, pelapor_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$siswa['id'], $jenisId, $tanggal, $lokasi, $keterangan, $tindakan, $pelaporId]
            );
            if (!$ok) {
                fwrite(STDERR, 'Gagal insert pelanggaran siswa #' . $siswa['id'] . ': ' . $mysqli->error . PHP_EOL);
                exit(1);
            }
            $totalPelanggaran++;
            if ($tanggalTerakhir === null || $tanggal > $tanggalTerakhir) {
                $tanggalTerakhir = $tanggal;
            }
        }

        if ($tanggalTerakhir) {
            $tema = $konsultasiTema[$kategoriPertama ?? 'Lainnya'] ?? $konsultasiTema['Lainnya'];
            $tanggalKonsultasi = tanggal_setelah($tanggalTerakhir);

            $ok = db_query(
                'INSERT INTO konsultasi_siswa (siswa_id, tanggal, permasalahan, tindak_lanjut, konselor_id)
                 VALUES (?, ?, ?, ?, ?)',
                [$siswa['id'], $tanggalKonsultasi, $tema['permasalahan'], $tema['tindak_lanjut'], $konselorId]
            );
            if (!$ok) {
                fwrite(STDERR, 'Gagal insert konsultasi siswa #' . $siswa['id'] . ': ' . $mysqli->error . PHP_EOL);
                exit(1);
            }
            $totalKonsultasi++;
        }
    }
}

// ============================== RINGKASAN ==============================
echo "\n===== HASIL SEED DEMO BK ($tahunAjaran) =====\n";
echo 'Total kelas        : ' . count($kelasList) . "\n";
echo 'Kelas tanpa data   : ' . $kelasTanpaData . "\n";
echo 'Total pelanggaran  : ' . $totalPelanggaran . "\n";
echo 'Total konsultasi   : ' . $totalKonsultasi . "\n";

echo "\nContoh pelanggaran:\n";
foreach (db_fetch(
    'SELECT s.nama, k.nama_kelas, j.nama AS jenis, p.tanggal, p.lokasi, u.nama AS pelapor
     FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     JOIN kelas k ON k.id = s.kelas_id
     JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
     LEFT JOIN users u ON u.id = p.pelapor_id
     ORDER BY p.id LIMIT 5'
) ?: [] as $c) {
    echo '  - ' . $c['tanggal'] . ' | ' . $c['nama'] . ' (' . $c['nama_kelas'] . ') | ' . $c['jenis'] . ' | ' . $c['lokasi'] . ' | pelapor: ' . $c['pelapor'] . "\n";
}

echo "\nContoh konsultasi:\n";
foreach (db_fetch(
    'SELECT s.nama, k.nama_kelas, c.tanggal, LEFT(c.permasalahan, 60) AS permasalahan, u.nama AS konselor
     FROM konsultasi_siswa c
     JOIN siswa s ON s.id = c.siswa_id
     JOIN kelas k ON k.id = s.kelas_id
     LEFT JOIN users u ON u.id = c.konselor_id
     ORDER BY c.id LIMIT 5'
) ?: [] as $c) {
    echo '  - ' . $c['tanggal'] . ' | ' . $c['nama'] . ' (' . $c['nama_kelas'] . ') | ' . $c['permasalahan'] . "… | konselor: " . $c['konselor'] . "\n";
}

echo "\nSelesai.\n";
