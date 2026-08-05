<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';
$tahunAjaran = current_tahun_ajaran();

// Filter data untuk Wali Kelas
$userKelasId = null;
if (is_wali_kelas()) {
    $userKelasId = get_user_kelas_id();
}

$totalSiswa = (int) (db_fetch(
    'SELECT COUNT(*) AS c FROM siswa s
     JOIN kelas k ON k.id = s.kelas_id
     WHERE s.status = ? AND k.tahun_ajaran = ?'
    , ['Aktif', $tahunAjaran], 'row')['c'] ?? 0);

if ($userKelasId) {
    $totalSiswa = (int) (db_fetch(
        'SELECT COUNT(*) AS c FROM siswa s
         JOIN kelas k ON k.id = s.kelas_id
         WHERE s.status = ? AND k.tahun_ajaran = ? AND s.kelas_id = ?'
        , ['Aktif', $tahunAjaran, $userKelasId], 'row')['c'] ?? 0);
}
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

if ($userKelasId) {
    $siswaBermasalah = (int) (db_fetch(
        'SELECT COUNT(*) AS c FROM (
            SELECT s.id
            FROM pelanggaran_siswa p
            JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
            JOIN siswa s ON s.id = p.siswa_id
            JOIN kelas k ON k.id = s.kelas_id
            WHERE s.status = ? AND k.tahun_ajaran = ? AND s.kelas_id = ?
            GROUP BY s.id
            HAVING SUM(j.bobot_poin) > 75
        ) t',
        ['Aktif', $tahunAjaran, $userKelasId],
        'row'
    )['c'] ?? 0);
}

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
$topSiswa = $topSiswa ?: [];

if ($userKelasId) {
    $topSiswa = db_fetch(
        'SELECT s.id, s.nama, s.foto, s.nipd, k.nama_kelas,
                SUM(j.bobot_poin) AS total_poin,
                MAX(p.tanggal) AS tanggal_terakhir
         FROM pelanggaran_siswa p
         JOIN siswa s ON s.id = p.siswa_id
         JOIN kelas k ON k.id = s.kelas_id
         JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
         WHERE k.tahun_ajaran = ? AND s.kelas_id = ?
         GROUP BY s.id
         ORDER BY total_poin DESC, s.nama ASC
         LIMIT 10',
        [$tahunAjaran, $userKelasId]
    );
    $topSiswa = $topSiswa ?: [];
}

$chart = ['labels' => [], 'data' => []];
$mon = [
    '1' => 'Jan', '2' => 'Feb', '3' => 'Mar', '4' => 'Apr', '5' => 'Mei', '6' => 'Jun',
    '7' => 'Jul', '8' => 'Ags', '9' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des',
];

if ($userKelasId) {
    $chartRows = db_fetch(
        'SELECT MONTH(p.tanggal) AS m, YEAR(p.tanggal) AS y, COUNT(*) AS c
         FROM pelanggaran_siswa p
         JOIN siswa s ON s.id = p.siswa_id
         WHERE s.kelas_id = ? AND p.tanggal >= DATE_SUB(DATE_FORMAT(CURDATE(), "%Y-%m-01"), INTERVAL 5 MONTH)
         GROUP BY DATE_FORMAT(p.tanggal, "%Y-%m")
         ORDER BY p.tanggal ASC',
        [$userKelasId]
    );
} else {
    $chartRows = db_fetch(
        'SELECT MONTH(tanggal) AS m, YEAR(tanggal) AS y, COUNT(*) AS c
         FROM pelanggaran_siswa
         WHERE tanggal >= DATE_SUB(DATE_FORMAT(CURDATE(), "%Y-%m-01"), INTERVAL 5 MONTH)
         GROUP BY DATE_FORMAT(tanggal, "%Y-%m")
         ORDER BY tanggal ASC'
    );
}
$map = [];
foreach (($chartRows ?: []) as $r) {
    $map[$r['y'] . '-' . $r['m']] = (int) $r['c'];
}
$start = new DateTime(date('Y-m-01'));
for ($i = 5; $i >= 0; $i--) {
    $k = $start->format('Y') . '-' . (int) $start->format('m');
    $chart['labels'][] = $mon[(string) (int) $start->format('m')];
    $chart['data'][] = $map[$k] ?? 0;
    $start->modify('-1 month');
}
$chart['labels'] = array_reverse($chart['labels']);
$chart['data'] = array_reverse($chart['data']);

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <h3>Ringkasan hari ini</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/tambah.php" class="primary-btn">+ Catat Pelanggaran</a>
</div>

<div class="grid grid-4">
    <div class="card stat-card">
        <div class="label">Total Siswa</div>
        <div class="value"><?= $totalSiswa ?></div>
        <div class="sub">Siswa aktif seluruh angkatan</div>
    </div>
    <div class="card stat-card">
        <div class="label">Total Kelas</div>
        <div class="value"><?= $totalKelas ?></div>
        <div class="sub">Rombongan belajar terdaftar</div>
    </div>
    <div class="card stat-card">
        <div class="label">Total Pelanggaran</div>
        <div class="value"><?= $pelanggaranBulan ?></div>
        <div class="sub">Kejadian bulan <?= $mon[(string) (int) date('n')] ?></div>
    </div>
    <div class="card stat-card">
        <div class="label">Siswa Bermasalah</div>
        <div class="value"><?= $siswaBermasalah ?></div>
        <div class="sub">Poin di atas threshold kritis (>75)</div>
    </div>
</div>

<div class="grid" style="grid-template-columns: 1.3fr 0.7fr; margin-top: 16px;">
    <div class="card chart-card">
        <h3 style="margin-top:0;">Grafik Pelanggaran 6 Bulan Terakhir</h3>
        <canvas id="violationsChart" height="180" data-chart='<?= e(json_encode($chart)) ?>'></canvas>
    </div>
    <div class="card table-card">
        <h3 style="margin-top:0;">Top 10 Siswa Poin Tertinggi</h3>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Total Poin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$topSiswa): ?>
                        <tr><td colspan="4" style="text-align:center;color:var(--text-muted);">Belum ada data pelanggaran.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($topSiswa as $i => $t): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><a href="<?= rtrim(APP_BASE, '/') ?>/siswa/detail.php?id=<?= (int) $t['id'] ?>" style="color:var(--primary);font-weight:600;"><?= e($t['nama']) ?></a></td>
                            <td><?= e($t['nama_kelas'] ?? '-') ?></td>
                            <td><?= poin_badge((int) $t['total_poin']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>