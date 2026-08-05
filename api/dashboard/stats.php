<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed', 405);
}

require_auth();

$tahunAjaran = $_SESSION['tahun_ajaran'] ?? '';

$totalSiswa = (int) (db_fetch(
    'SELECT COUNT(*) AS c FROM siswa s
     JOIN kelas k ON k.id = s.kelas_id
     WHERE s.status = ? AND k.tahun_ajaran = ?',
    ['Aktif', $tahunAjaran], 'row')['c'] ?? 0);

$totalKelas = (int) (db_fetch('SELECT COUNT(*) AS c FROM kelas WHERE tahun_ajaran = ?', [$tahunAjaran], 'row')['c'] ?? 0);

$pelanggaranBulan = (int) (db_fetch(
    'SELECT COUNT(*) AS c FROM pelanggaran_siswa
     WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())',
    [],
    'row'
)['c'] ?? 0);

$siswaBermasalah = (int) (db_fetch(
    'SELECT COUNT(*) AS c FROM (
        SELECT s.id
        FROM pelanggaran_siswa p
        JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
        JOIN siswa s ON s.id = p.siswa_id
        JOIN kelas k ON k.id = s.kelas_id
        WHERE s.status = ? AND k.tahun_ajaran = ?
        GROUP BY s.id
        HAVING SUM(j.bobot_poin) > 75
    ) t',
    ['Aktif', $tahunAjaran],
    'row'
)['c'] ?? 0);

$topSiswa = db_fetch(
    'SELECT s.id, s.nama, s.foto, s.nipd, k.nama_kelas,
            SUM(j.bobot_poin) AS total_poin,
            MAX(p.tanggal) AS tanggal_terakhir
     FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     JOIN kelas k ON k.id = s.kelas_id
     JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
     WHERE k.tahun_ajaran = ?
     GROUP BY s.id
     ORDER BY total_poin DESC, s.nama ASC
     LIMIT 10',
    [$tahunAjaran]
);

$chartRows = db_fetch(
    'SELECT MONTH(tanggal) AS m, YEAR(tanggal) AS y, COUNT(*) AS c
     FROM pelanggaran_siswa
     WHERE tanggal >= DATE_SUB(DATE_FORMAT(CURDATE(), "%Y-%m-01"), INTERVAL 5 MONTH)
     GROUP BY DATE_FORMAT(tanggal, "%Y-%m")
     ORDER BY tanggal ASC'
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
$start = new DateTime(date('Y-m-01'));
for ($i = 5; $i >= 0; $i--) {
    $k = $start->format('Y') . '-' . (int) $start->format('m');
    $chart['labels'][] = $mon[(string) (int) $start->format('m')];
    $chart['data'][] = $map[$k] ?? 0;
    $start->modify('-1 month');
}
$chart['labels'] = array_reverse($chart['labels']);
$chart['data'] = array_reverse($chart['data']);

api_success([
    'summary' => [
        'total_siswa' => $totalSiswa,
        'total_kelas' => $totalKelas,
        'pelanggaran_bulan' => $pelanggaranBulan,
        'siswa_bermasalah' => $siswaBermasalah
    ],
    'top_siswa' => $topSiswa ?: [],
    'chart' => $chart
]);
