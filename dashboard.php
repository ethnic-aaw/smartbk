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
$pelanggaranParams = [$tahunAjaran];
$pelanggaranKelasSql = '';
if ($userKelasId) {
    $pelanggaranKelasSql = ' AND s.kelas_id = ?';
    $pelanggaranParams[] = $userKelasId;
}
$pelanggaranTahun = (int) (db_fetch(
    "SELECT COUNT(*) AS c FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     JOIN kelas k ON k.id = s.kelas_id
     WHERE k.tahun_ajaran = ?$pelanggaranKelasSql",
    $pelanggaranParams,
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

$periode = trim($_GET['periode'] ?? 'tahun');
if (!in_array($periode, ['harian', 'minggu', 'bulan', 'tahun'], true)) {
    $periode = 'tahun';
}
$periodeSql = '';
$periodeParams = [];
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
     WHERE k.tahun_ajaran = ?' . $periodeSql . '
     GROUP BY s.id
     ORDER BY total_poin DESC, s.nama ASC
     LIMIT 10',
    array_merge([$tahunAjaran], $periodeParams)
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
         WHERE k.tahun_ajaran = ? AND s.kelas_id = ?' . $periodeSql . '
         GROUP BY s.id
         ORDER BY total_poin DESC, s.nama ASC
         LIMIT 10',
        array_merge([$tahunAjaran, $userKelasId], $periodeParams)
    );
    $topSiswa = $topSiswa ?: [];
}

$ringkasanParams = [$tahunAjaran];
$ringkasanKelasSql = '';
if ($userKelasId) {
    $ringkasanKelasSql = ' AND s.kelas_id = ?';
    $ringkasanParams[] = $userKelasId;
}
$ringkasanKomponen = db_fetch(
    "SELECT COALESCE(j.komponen, 'Lain-lain') AS komponen, COUNT(*) AS jumlah,
            COALESCE(SUM(j.bobot_poin), 0) AS total_poin
     FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     JOIN kelas k ON k.id = s.kelas_id
     JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
     WHERE k.tahun_ajaran = ?$ringkasanKelasSql
     GROUP BY COALESCE(j.komponen, 'Lain-lain')
     ORDER BY jumlah DESC, komponen ASC",
    $ringkasanParams
) ?: [];

$chart = ['labels' => [], 'data' => []];
$mon = [
    '1' => 'Jan', '2' => 'Feb', '3' => 'Mar', '4' => 'Apr', '5' => 'Mei', '6' => 'Jun',
    '7' => 'Jul', '8' => 'Ags', '9' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des',
];

// Periode grafik: 6 bulan terakhir dari tahun ajaran terpilih (berakhir Juni)
$tahunAkhirAjaran = (int) substr($tahunAjaran, 5, 4);
$akhirPeriode = new DateTime($tahunAkhirAjaran . '-06-30');
$mulaiPeriode = clone $akhirPeriode;
$mulaiPeriode->modify('-5 months');
$mulaiPeriode->modify('first day of this month');
$mulaiPeriode->setTime(0, 0);

if ($userKelasId) {
    $chartRows = db_fetch(
        'SELECT MONTH(p.tanggal) AS m, YEAR(p.tanggal) AS y, COUNT(*) AS c
         FROM pelanggaran_siswa p
         JOIN siswa s ON s.id = p.siswa_id
         WHERE s.kelas_id = ? AND p.tanggal BETWEEN ? AND ?
         GROUP BY DATE_FORMAT(p.tanggal, "%Y-%m")
         ORDER BY p.tanggal ASC',
        [$userKelasId, $mulaiPeriode->format('Y-m-d'), $akhirPeriode->format('Y-m-d')]
    );
} else {
    $chartRows = db_fetch(
        'SELECT MONTH(tanggal) AS m, YEAR(tanggal) AS y, COUNT(*) AS c
         FROM pelanggaran_siswa
         WHERE tanggal BETWEEN ? AND ?
         GROUP BY DATE_FORMAT(tanggal, "%Y-%m")
         ORDER BY tanggal ASC',
        [$mulaiPeriode->format('Y-m-d'), $akhirPeriode->format('Y-m-d')]
    );
}
$map = [];
foreach (($chartRows ?: []) as $r) {
    $map[$r['y'] . '-' . $r['m']] = (int) $r['c'];
}
$cursor = $mulaiPeriode;
for ($i = 0; $i < 6; $i++) {
    $k = $cursor->format('Y') . '-' . (int) $cursor->format('m');
    $chart['labels'][] = $mon[(string) (int) $cursor->format('m')];
    $chart['data'][] = $map[$k] ?? 0;
    $cursor->modify('+1 month');
}

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
        <div class="value"><?= $pelanggaranTahun ?></div>
        <div class="sub">Tahun ajaran <?= e($tahunAjaran) ?></div>
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
        <div class="toolbar" style="margin-bottom:10px; padding:0;">
            <?php
            $periodeOptions = [
                'harian' => 'Hari Ini',
                'minggu' => '7 Hari',
                'bulan' => 'Bulan Ini',
                'tahun' => 'Tahun Ajaran',
            ];
            foreach ($periodeOptions as $pk => $pl):
                $url = rtrim(APP_BASE, '/') . '/dashboard.php' . ($pk === 'tahun' ? '' : '?periode=' . $pk);
                ?>
                <a href="<?= $url ?>" class="ghost-btn <?= $periode === $pk ? 'is-active' : '' ?>" style="font-size:13px;"><?= $pl ?></a>
            <?php endforeach; ?>
        </div>
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
                        <tr><td colspan="4" style="text-align:center;color:var(--text-muted);">Tidak ada pelanggaran pada periode ini.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($topSiswa as $i => $t): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><a href="<?= rtrim(APP_BASE, '/') ?>/siswa/detail.php?id=<?= (int) $t['id'] ?>" style="color:var(--primary);font-weight:600;"><?= e($t['nama']) ?></a></td>
                            <td><?= e($t['nama_kelas'] ?? '-') ?></td>
                            <td><?= poin_badge((int) $t['total_poin']) ?> <?= fase_badge((int) $t['total_poin']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid" style="grid-template-columns: 1.3fr 0.7fr; margin-top: 16px;">
    <div class="card chart-card">
        <h3 style="margin-top:0;">Pelanggaran per Komponen</h3>
        <?php if (!$ringkasanKomponen): ?>
            <p style="color:var(--text-muted);">Belum ada data pelanggaran.</p>
        <?php else:
            $maxJumlah = max(array_column($ringkasanKomponen, 'jumlah'));
        ?>
            <div class="bar-chart">
                <?php foreach ($ringkasanKomponen as $rk):
                    $pct = $maxJumlah > 0 ? (int) round($rk['jumlah'] / $maxJumlah * 100) : 0;
                ?>
                    <div class="bar-row">
                        <div class="bar-label" title="<?= e($rk['komponen']) ?>"><?= e($rk['komponen']) ?></div>
                        <div class="bar-track"><div class="bar-fill" style="width: <?= $pct ?>%;"></div></div>
                        <div class="bar-value">
                            <span class="badge badge-warning"><?= (int) $rk['jumlah'] ?></span>
                            <span class="bar-poin"><?= (int) $rk['total_poin'] ?> poin</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>