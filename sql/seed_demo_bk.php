<?php
/**
 * Seed Data Demo — Pencatatan Pelanggaran Siswa
 * SMK Negeri 1 Leuwimunding
 *
 * Data: 56 jenis pelanggaran (PLG-001 s.d. PLG-056)
 *
 * Cara pakai (CLI):
 *   php sql/seed_demo_bk.php            (ada konfirmasi interaktif)
 *   php sql/seed_demo_bk.php --yes      (langsung eksekusi)
 *
 * Efek:
 *   - Menghapus SEMUA data pelanggaran_siswa dan konsultasi_siswa.
 *   - Setiap kelas: 2 siswa acak diberi 1-5 catatan pelanggaran + 1 konsultasi.
 *   - Pelanggaran dicatat oleh Wali Kelas; konsultasi oleh Guru BK.
 */

require_once __DIR__ . '/../config/db.php';

if (!db_is_ready()) {
    fwrite(STDERR, 'Koneksi database tidak tersedia: ' . db_error() . PHP_EOL);
    exit(1);
}

if (!in_array('--yes', $argv, true)) {
    echo 'Script ini akan MENGHAPUS semua data pelanggaran_siswa dan konsultasi_siswa ' . PHP_EOL;
    echo 'lalu mengisi data demo berdasarkan 56 jenis pelanggaran SMK N 1 Leuwimunding.' . PHP_EOL;
    echo 'Lanjutkan? (y/N): ';
    $line = trim(fgets(STDIN));
    if (!in_array(strtolower($line), ['y', 'yes'], true)) {
        echo 'Dibatalkan.' . PHP_EOL;
        exit(0);
    }
}

global $mysqli;

$tahunAjaran = '2024/2025';

// ============================== LOKASI PER KOMPONEN ==============================
// Disesuaikan dengan konteks SMK N 1 Leuwimunding
$lokasiPerKomponen = [
    'Kehadiran'                   => ['Gerbang sekolah', 'Halaman upacara', 'Pintu masuk kelas'],
    'Kegiatan Belajar Mengajar'   => ['Ruang kelas', 'Laboratorium', 'Bengkel_praktik', 'Ruang guru'],
    'Pakaian Seragam'             => ['Gerbang sekolah', 'Halaman sekolah', 'Ruang kelas'],
    'Makan dan Minum'             => ['Ruang kelas', 'Ruang_praktek'],
    'Izin Meninggalkan Sekolah'   => ['Gerbang sekolah', 'Ruang kelas', 'Ruang piket'],
    'Perkelahian'                 => ['Halaman sekolah', 'Lingkungan sekolah', 'Kantin', 'Area parkir'],
    'Praktik Kerja Lapangan'      => ['Tempat PKL/Prakerin', 'Lokasi mitra industri'],
    'Kebersihan Lingkungan'       => ['Halaman sekolah', 'Kelas', 'Toilet', 'Lingkungan sekolah'],
    'Lain-lain'                   => ['Ruang kelas', 'Kantin', 'Halaman sekolah', 'Lingkungan sekolah'],
];

// ============================== KETERANGAN PER KOMPONEN ==============================
$keteranganPerKomponen = [
    'Kehadiran'                   => [
        'Terlambat masuk gerbang sekolah',
        'Terlambat setelah bel masuk',
        'Tidak hadir saat upacara bendera',
        'Tidak mengikuti apel siang',
        'Bolos jam pelajaran',
    ],
    'Kegiatan Belajar Mengajar'   => [
        'Keluar kelas tanpa izin saat KBM',
        'Tidak memakai seragam praktik',
        'Main HP saat jam pelajaran',
        'Tidak mengerjakan PR/tugas guru',
        'Hadir tapi tidak tatap muka di kelas',
    ],
    'Pakaian Seragam'             => [
        'Memakai seragam tidak sesuai jadwal',
        'Memakai pakaian ketat/rok mini',
        'Tidak memakai topi saat upacara',
        'Tidak memakai kaos kaki putih polos',
        'Baju atasan tidak dimasukkan',
    ],
    'Makan dan Minum'             => [
        'Makan di dalam kelas saat KBM',
        'Minum di ruang praktik saat pelajaran',
    ],
    'Izin Meninggalkan Sekolah'   => [
        'Membuat surat izin palsu',
        'Meninggalkan sekolah tanpa izin guru piket',
    ],
    'Perkelahian'                 => [
        'Berkelahi dengan teman sekelas',
        'Perkelahian antar kelas',
        'Bertengkar dengan siswa lain',
        'Melawan guru secara fisik',
    ],
    'Praktik Kerja Lapangan'      => [
        'Melanggar tata tertib di tempat prakerin',
        'Berkelahi di lokasi PKL',
        'Meninggalkan tempat PKL tanpa izin',
    ],
    'Kebersihan Lingkungan'       => [
        'Mencoret-coret meja/tembok kelas',
        'Membuang sampah sembarangan',
        'Tidak melaksanakan piket kebersihan',
    ],
    'Lain-lain'                   => [
        'Membuat gaduh di dalam kelas',
        'Mengisi daya HP saat jam pelajaran',
        'Membawa rokok di area sekolah',
        'Rambut tidak sesuai aturan sekolah',
        'Membawa mainan/playing card',
    ],
];

// ============================== TINDAKAN YANG DICatat ==============================
$tindakanList = [
    'Peringatan lisan',
    'Peringatan tertulis',
    'Pembinaan oleh Guru BK',
    'Menulis surat pernyataan',
    'Panggilan orang tua',
    'Surat peringatan ke wali murid',
];

// ============================== KONSULTASI ==============================
$konsultasiTema = [
    'Kehadiran' => [
        'permasalahan' => 'Siswa sering datang terlambat dan kesulitan bangun pagi. Sudah 4 kali terlambat dalam bulan ini.',
        'tindak_lanjut' => 'Pemberian motivasi disiplin waktu, kerja sama dengan orang tua untuk mengatur jam tidur, monitoring kehadiran selama 2 minggu.',
    ],
    'Kegiatan Belajar Mengajar' => [
        'permasalahan' => 'Siswa tidak fokus belajar, sering menggunakan gadget saat jam pelajaran dan tidak mengerjakan tugas.',
        'tindak_lanjut' => 'Pembinaan fokus belajar, koordinasi dengan guru mata pelajaran, pengawasan gadget saat KBM.',
    ],
    'Pakaian Seragam' => [
        'permasalahan' => 'Siswa beberapa kali kedapatan memakai seragam tidak sesuai jadwal dan pakaian tidak rapi.',
        'tindak_lanjut' => 'Pembinaan kedisiplinan berpakaian, penguatan aturan tata tertib, koordinasi dengan orang tua.',
    ],
    'Perkelahian' => [
        'permasalahan' => 'Siswa terlibat perkelahian dengan teman karena masalah komunikasi.',
        'tindak_lanjut' => 'Mediasi dengan teman yang berselisih, pelatihan pengelolaan emosi, pemantauan intensif oleh wali kelas.',
    ],
    'Praktik Kerja Lapangan' => [
        'permasalahan' => 'Siswa melanggar tata tertib di tempat prakerin dan hampir dikeluarkan oleh mitra industri.',
        'tindak_lanjut' => 'Pembinaan disiplin di tempat kerja, koordinasi dengan pembimbing prakerin dan orang tua.',
    ],
    'Lain-lain' => [
        'permasalahan' => 'Siswa kurang disiplin dalam menaati aturan tata tertib sekolah secara umum.',
        'tindak_lanjut' => 'Konseling motivasi, identifikasi penyebab, rencana perbaikan yang disepakati bersama.',
    ],
];

// ============================== FUNGSI HELPER ==============================
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

$jenisList = db_fetch('SELECT id, kode, nama, komponen, kategori, bobot_poin FROM jenis_pelanggaran ORDER BY id');
if (!$jenisList || count($jenisList) === 0) {
    fwrite(STDERR, 'Master jenis pelanggaran kosong. Import terlebih dahulu: mysql -u root smart_bk < sql/master_pelanggaran_smk_leuwimunding.sql' . PHP_EOL);
    exit(1);
}

echo 'Jenis pelanggaran tersedia: ' . count($jenisList) . " jenis\n";

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

    if (count($siswaKelas) === 0) {
        $kelasTanpaData++;
        continue;
    }

    // Acak max 2 siswa per kelas (atau semua jika kurang dari 2)
    $ambil = min(2, count($siswaKelas));
    $keys = array_rand($siswaKelas, $ambil);
    if (!is_array($keys)) {
        $keys = [$keys];
    }

    foreach ($keys as $idx) {
        $siswa = $siswaKelas[$idx];
        // Setiap siswa dapat 1-5 pelanggaran (variasi lebih realistis)
        $jumlahPelanggaran = mt_rand(1, 5);
        $tanggalTerakhir = null;
        $jenisIds = [];
        $kategoriPertama = null;

        for ($i = 0; $i < $jumlahPelanggaran; $i++) {
            $jenis = $jenisList[array_rand($jenisList)];
            $jenisId = (int) $jenis['id'];

            // Hindari duplikat jenis pelanggaran yang sama
            if (in_array($jenisId, $jenisIds, true)) {
                continue;
            }
            $jenisIds[] = $jenisId;

            if ($kategoriPertama === null) {
                $kategoriPertama = $jenis['kategori'];
            }

            $komponen = $jenis['komponen'] ?: 'Lain-lain';
            $tanggal = tanggal_acak_tahun_ajaran();

            // Lokasi berdasarkan komponen
            $lokasiPool = $lokasiPerKomponen[$komponen] ?? $lokasiPerKomponen['Lain-lain'];
            $lokasi = $lokasiPool[array_rand($lokasiPool)];

            // Keterangan berdasarkan komponen
            $ketPool = $keteranganPerKomponen[$komponen] ?? $keteranganPerKomponen['Lain-lain'];
            $keterangan = $ketPool[array_rand($ketPool)];

            // Tindakan berdasarkan poin
            $poin = (int) $jenis['bobot_poin'];
            if ($poin >= 75) {
                $tindakan = 'Panggilan orang tua / skorsing';
            } elseif ($poin >= 30) {
                $tindakan = 'Surat peringatan / panggilan orang tua';
            } else {
                $tindakan = $tindakanList[array_rand($tindakanList)];
            }

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

        // Buat konsultasi berdasarkan pelanggaran pertama
        if ($tanggalTerakhir) {
            $temaKey = $kategoriPertama ?? 'Lain-lain';
            // Map kategori ke tema konsultasi
            $temaMap = [
                'Kedisiplinan' => 'Kehadiran',
                'Tata Krama'   => 'Pakaian Seragam',
                'Kekerasan'    => 'Perkelahian',
                'Narkoba'      => 'Lain-lain',
                'Lainnya'      => 'Lain-lain',
            ];
            $temaKey = $temaMap[$temaKey] ?? 'Lain-lain';
            $tema = $konsultasiTema[$temaKey] ?? $konsultasiTema['Lain-lain'];
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
echo "\n===== HASIL SEED DEMO — SMK N 1 LEUWIMUNDING ($tahunAjaran) =====\n";
echo 'Total kelas        : ' . count($kelasList) . "\n";
echo 'Kelas tanpa data   : ' . $kelasTanpaData . "\n";
echo 'Total pelanggaran  : ' . $totalPelanggaran . "\n";
echo 'Total konsultasi   : ' . $totalKonsultasi . "\n";

// Rekap per komponen
$rekapKomponen = db_fetch(
    'SELECT j.komponen, COUNT(*) AS jumlah, SUM(j.bobot_poin) AS total_poin
     FROM pelanggaran_siswa p
     JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
     GROUP BY j.komponen ORDER BY jumlah DESC'
);
if ($rekapKomponen) {
    echo "\nRekap per komponen:\n";
    foreach ($rekapKomponen as $r) {
        echo '  ' . str_pad($r['komponen'], 35) . ' : ' . str_pad($r['jumlah'], 3) . ' kasus  (poin: ' . $r['total_poin'] . ")\n";
    }
}

// Rekap per kategori
$rekapKategori = db_fetch(
    'SELECT j.kategori, COUNT(*) AS jumlah
     FROM pelanggaran_siswa p
     JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
     GROUP BY j.kategori ORDER BY jumlah DESC'
);
if ($rekapKategori) {
    echo "\nRekap per kategori:\n";
    foreach ($rekapKategori as $r) {
        echo '  ' . str_pad($r['kategori'], 20) . ' : ' . $r['jumlah'] . " kasus\n";
    }
}

echo "\nContoh pelanggaran:\n";
foreach (db_fetch(
    'SELECT s.nama, k.nama_kelas, j.kode, j.nama AS jenis, j.komponen, j.bobot_poin, p.tanggal, p.lokasi, u.nama AS pelapor
     FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     JOIN kelas k ON k.id = s.kelas_id
     JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
     LEFT JOIN users u ON u.id = p.pelapor_id
     ORDER BY p.id LIMIT 8'
) ?: [] as $c) {
    echo '  - [' . $c['kode'] . '] ' . $c['tanggal'] . ' | ' . $c['nama'] . ' (' . $c['nama_kelas'] . ') | '
        . $c['jenis'] . ' [' . $c['komponen'] . '] (' . $c['bobot_poin'] . ' poin) | ' . $c['lokasi'] . " | pelapor: " . $c['pelapor'] . "\n";
}

echo "\nContoh konsultasi:\n";
foreach (db_fetch(
    'SELECT s.nama, k.nama_kelas, c.tanggal, LEFT(c.permasalahan, 70) AS permasalahan, u.nama AS konselor
     FROM konsultasi_siswa c
     JOIN siswa s ON s.id = c.siswa_id
     JOIN kelas k ON k.id = s.kelas_id
     LEFT JOIN users u ON u.id = c.konselor_id
     ORDER BY c.id LIMIT 5'
) ?: [] as $c) {
    echo '  - ' . $c['tanggal'] . ' | ' . $c['nama'] . ' (' . $c['nama_kelas'] . ') | ' . $c['permasalahan'] . "... | konselor: " . $c['konselor'] . "\n";
}

echo "\nSelesai.\n";
