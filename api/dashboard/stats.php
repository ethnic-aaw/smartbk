<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed', 405);
}

require_auth();

$tahunAjaran = $_SESSION['tahun_ajaran'] ?? '';
$scopeKelasId = current_kelas_scope();

$totalSiswa = (int) (db_fetch(
    'SELECT COUNT(*) AS c FROM siswa s
     JOIN kelas k ON k.id = s.kelas_id
     WHERE s.status = ? AND k.tahun_ajaran = ?' . ($scopeKelasId ? ' AND s.kelas_id = ?' : ''),
    $scopeKelasId ? ['Aktif', $tahunAjaran, $scopeKelasId] : ['Aktif', $tahunAjaran],
    'row')['c'] ?? 0);

$totalKelas = (int) (db_fetch('SELECT COUNT(*) AS c FROM kelas WHERE tahun_ajaran = ?', [$tahunAjaran], 'row')['c'] ?? 0);

$pelanggaranTahun = (int) (db_fetch(
    'SELECT COUNT(*) AS c FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     JOIN kelas k ON k.id = s.kelas_id
     WHERE k.tahun_ajaran = ?' . ($scopeKelasId ? ' AND s.kelas_id = ?' : ''),
    $scopeKelasId ? [$tahunAjaran, $scopeKelasId] : [$tahunAjaran],
    'row'
)['c'] ?? 0);

$siswaBermasalah = (int) (db_fetch(
    'SELECT COUNT(*) AS c FROM (
        SELECT s.id
        FROM pelanggaran_siswa p
        JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
        JOIN siswa s ON s.id = p.siswa_id
        JOIN kelas k ON k.id = s.kelas_id
        WHERE s.status = ? AND k.tahun_ajaran = ?' . ($scopeKelasId ? ' AND s.kelas_id = ?' : '') . '
        GROUP BY s.id
        HAVING SUM(j.bobot_poin) > 75
    ) t',
    $scopeKelasId ? ['Aktif', $tahunAjaran, $scopeKelasId] : ['Aktif', $tahunAjaran],
    'row'
)['c'] ?? 0);

$periode = trim($_GET['periode'] ?? 'tahun');
if (!in_array($periode, ['harian', 'minggu', 'bulan', 'tahun'], true)) {
    $periode = 'tahun';
}
$periodeSql = '';
switch ($periode) {
    case 'harian':
        $periodeSql = ' AND p.tanggal = CURDATE()';
        break;
    case 'minggu':
        $periodeSql = ' AND p.tanggal >= CURDATE() - INTERVAL 6 DAY';
        break;
    case 'bulan':
        $periodeSql = ' AND MONTH(p.tanggal) = MONTH(CURDATE()) AND YEAR(p.tanggal) = YEAR(CURDATE())';
        break;
    case 'tahun':
    default:
        $periodeSql = '';
        break;
}

$topSiswa = db_fetch(
    'SELECT s.id, s.nama, s.foto, s.nipd, k.nama_kelas,
            SUM(j.bobot_poin) AS total_poin,
            MAX(p.tanggal) AS tanggal_terakhir
     FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     JOIN kelas k ON k.id = s.kelas_id
     JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
     WHERE k.tahun_ajaran = ?' . ($scopeKelasId ? ' AND s.kelas_id = ?' : '') . $periodeSql . '
     GROUP BY s.id
     ORDER BY total_poin DESC, s.nama ASC
     LIMIT 10',
    $scopeKelasId ? [$tahunAjaran, $scopeKelasId] : [$tahunAjaran]
);

// Periode grafik: 6 bulan terakhir dari tahun ajaran terpilih (berakhir Juni)
$tahunAkhirAjaran = (int) substr($tahunAjaran, 5, 4);
$akhirPeriode = new DateTime($tahunAkhirAjaran . '-06-30');
$mulaiPeriode = clone $akhirPeriode;
$mulaiPeriode->modify('-5 months');
$mulaiPeriode->modify('first day of this month');
$mulaiPeriode->setTime(0, 0);

$chartRows = db_fetch(
    'SELECT MONTH(p.tanggal) AS m, YEAR(p.tanggal) AS y, COUNT(*) AS c
     FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     WHERE p.tanggal BETWEEN ? AND ?' . ($scopeKelasId ? ' AND s.kelas_id = ?' : '') . '
     GROUP BY YEAR(p.tanggal), MONTH(p.tanggal)
     ORDER BY y, m',
    $scopeKelasId ? [$mulaiPeriode->format('Y-m-d'), $akhirPeriode->format('Y-m-d'), $scopeKelasId] : [$mulaiPeriode->format('Y-m-d'), $akhirPeriode->format('Y-m-d')]
);

$mon = [
    '1' => 'Jan', '2' => 'Feb', '3' => 'Mar', '4' => 'Apr', '5' => 'Mei', '6' => 'Jun',
    '7' => 'Jul', '8' => 'Ags', '9' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des',
];

$map = [];
foreach (($chartRows ?: []) as $r) {
    $map[$r['y'] . '-' . $r['m']] = (int) $r['c'];
}

$chart = ['labels' => [], 'data' => []];
$cursor = clone $mulaiPeriode;
for ($i = 0; $i < 6; $i++) {
    $k = $cursor->format('Y') . '-' . (int) $cursor->format('m');
    $chart['labels'][] = $mon[(string) (int) $cursor->format('m')];
    $chart['data'][] = $map[$k] ?? 0;
    $cursor->modify('+1 month');
}

$ringkasanKomponen = db_fetch(
    "SELECT COALESCE(j.komponen, 'Lain-lain') AS komponen, COUNT(*) AS jumlah,
            COALESCE(SUM(j.bobot_poin), 0) AS total_poin
     FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     JOIN kelas k ON k.id = s.kelas_id
     JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
     WHERE k.tahun_ajaran = ?" . ($scopeKelasId ? ' AND s.kelas_id = ?' : '') . '
     GROUP BY COALESCE(j.komponen, "Lain-lain")
     ORDER BY jumlah DESC, komponen ASC',
    $scopeKelasId ? [$tahunAjaran, $scopeKelasId] : [$tahunAjaran]
);

api_success([
    'summary' => [
        'total_siswa' => $totalSiswa,
        'total_kelas' => $totalKelas,
        'pelanggaran_tahun' => $pelanggaranTahun,
        'siswa_bermasalah' => $siswaBermasalah
    ],
    'top_siswa' => $topSiswa ?: [],
    'ringkasan_komponen' => $ringkasanKomponen ?: [],
    'chart' => $chart
]);
