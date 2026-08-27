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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
.dashboard-insight { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-top:16px; }
.insight-card { background:linear-gradient(135deg,#eff6ff,#ffffff); border:1px solid var(--border); border-radius:16px; padding:14px; }
.insight-card strong { display:block; font-size:13px; color:var(--text-muted); margin-bottom:6px; }
.insight-card span { font-size:18px; font-weight:800; color:var(--text); }
.chart-card canvas { width:100% !important; height:320px !important; display:block; }
@media (max-width:900px){ .dashboard-insight,.dashboard-grid { grid-template-columns:1fr !important; } }
</style>

<div class="dashboard-insight">
    <div class="insight-card"><strong>Risiko bulan ini</strong><span id="insightRisk">Memuat...</span></div>
    <div class="insight-card"><strong>Fokus pembinaan</strong><span id="insightFocus">Memuat...</span></div>
    <div class="insight-card"><strong>Prioritas kepala sekolah</strong><span id="insightPolicy">Memuat...</span></div>
</div>

<div class="grid dashboard-grid" style="grid-template-columns: 1.25fr 0.75fr; margin-top: 16px;">
    <div class="card chart-card">
        <h3 style="margin-top:0;">Tren Pelanggaran 6 Bulan</h3>
        <canvas id="trendChart" height="180"></canvas>
    </div>
    <div class="card chart-card">
        <h3 style="margin-top:0;">Kategori Dominan</h3>
        <canvas id="categoryChart" height="180"></canvas>
    </div>
</div>

<div class="grid dashboard-grid" style="grid-template-columns: 1.25fr 0.75fr; margin-top: 16px;">
    <div class="card chart-card">
        <h3 style="margin-top:0;">Top 5 Siswa Risiko Tinggi</h3>
        <canvas id="topStudentChart" height="180"></canvas>
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

<div class="card chart-card" style="margin-top: 16px;">
    <h3 style="margin-top:0;">Pelanggaran per Komponen</h3>
    <?php if (!$ringkasanKomponen): ?>
        <p style="color:var(--text-muted);">Belum ada data pelanggaran.</p>
    <?php else: ?>
        <canvas id="componentChart" height="320"></canvas>
<script>
            const componentData = <?= json_encode(array_map(fn($r) => [
                'komponen' => $r['komponen'],
                'jumlah' => (int) $r['jumlah'],
                'total_poin' => (int) $r['total_poin'],
            ], $ringkasanKomponen)) ?>;
            const componentColors = ['#2563eb', '#f97316', '#14b8a6', '#dc2626', '#8b5cf6', '#eab308', '#64748b'];
            const componentCanvas = document.getElementById('componentChart');
            if (componentCanvas) new Chart(componentCanvas, {
                type: 'bar',
                data: {
                    labels: componentData.map(x => x.komponen.length > 24 ? `${x.komponen.slice(0, 24)}…` : x.komponen),
                    datasets: [{
                        label: 'Jumlah kasus',
                        data: componentData.map(x => x.jumlah),
                        backgroundColor: componentColors,
                        borderRadius: 8,
                        maxBarThickness: 60,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    animation: { duration: 900, easing: 'easeOutQuart' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => `${ctx.parsed.y} kasus • ${componentData[ctx.dataIndex].total_poin} poin`,
                            },
                        },
                    },
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 }, title: { display: true, text: 'Kasus' } },
                        y: { ticks: { autoSkip: false, font: { size: 12 } } },
                    },
                    onClick: (e, items) => {
                        if (items.length) {
                            const komponen = componentData[items[0].index].komponen;
                            window.location = '<?= rtrim(APP_BASE, '/') ?>/pelanggaran/riwayat.php?komponen=' + encodeURIComponent(komponen);
                        }
                    },
                },
            });
        </script>
    <?php endif; ?>
</div>

<script>
(async () => {
    const response = await fetch('<?= rtrim(APP_BASE, '/') ?>/api/dashboard/stats.php', { credentials: 'same-origin' });
    const data = await response.json();
    if (data.error) return;
    const colors = ['#2563eb', '#f97316', '#14b8a6', '#dc2626', '#8b5cf6', '#eab308'];
    const common = {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 900, easing: 'easeOutQuart' },
        plugins: {
            legend: { labels: { usePointStyle: true, font: { size: 14, weight: '700' } } },
            tooltip: { bodyFont: { size: 14 }, titleFont: { size: 15, weight: '700' }, padding: 12 }
        }
    };
    new Chart(document.getElementById('trendChart'), { type:'line', data:{ labels:data.trenBulanan.labels, datasets:[{label:'Kasus',data:data.trenBulanan.data,borderColor:'#2563eb',backgroundColor:'rgba(37,99,235,.14)',fill:true,tension:.4,pointRadius:5,pointHoverRadius:8}] }, options:{...common, scales:{y:{beginAtZero:true,ticks:{precision:0,font:{size:13,weight:'600'}},title:{display:true,text:'Kasus',font:{size:14,weight:'700'}}},x:{ticks:{font:{size:13,weight:'600'}}}}} });
    new Chart(document.getElementById('categoryChart'), { type:'doughnut', data:{ labels:data.distribusiPerKategori.map(x=>x.kategori), datasets:[{data:data.distribusiPerKategori.map(x=>x.jumlah),backgroundColor:colors,borderWidth:3,borderColor:'#fff',hoverOffset:10}] }, options:{...common,cutout:'62%', plugins:{...common.plugins,legend:{position:'bottom',labels:{usePointStyle:true,font:{size:13,weight:'700'}}}}} });
    new Chart(document.getElementById('topStudentChart'), { type:'bar', data:{ labels:data.topSiswa.map(x=>x.nama.length>22?`${x.nama.slice(0,22)}…`:x.nama), datasets:[{label:'Total poin',data:data.topSiswa.map(x=>x.total_poin),backgroundColor:colors,borderRadius:8}] }, options:{...common,indexAxis:'y',plugins:{...common.plugins,legend:{display:false},tooltip:{callbacks:{title:items=>data.topSiswa[items[0].dataIndex].nama}}},scales:{x:{beginAtZero:true,ticks:{precision:0,font:{size:13,weight:'600'}},title:{display:true,text:'Poin',font:{size:14,weight:'700'}}},y:{ticks:{autoSkip:false,font:{size:12,weight:'600'}}}}} });
    const current = data.pelanggaran.bulanIni, previous = data.pelanggaran.bulanLalu;
    const focus = data.distribusiPerKategori[0];
    document.getElementById('insightRisk').textContent = current > previous ? `Naik: ${current} kasus` : `Terkendali: ${current} kasus`;
    document.getElementById('insightFocus').textContent = focus ? `${focus.kategori} (${focus.jumlah} kasus)` : 'Belum ada data';
    document.getElementById('insightPolicy').textContent = current > previous ? 'Tambah intervensi BK' : 'Pertahankan program';
})().catch(() => {});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>