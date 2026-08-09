<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Riwayat Pelanggaran';
$activeMenu = 'pelanggaran_riwayat';

$search = trim($_GET['q'] ?? '');
$kelasFilter = (int) ($_GET['kelas'] ?? 0);
$tahunAjaran = current_tahun_ajaran();
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$where = ['1 = 1'];
$params = [];

// Role-based: Wali Kelas hanya melihat pelanggaran siswa di kelasnya
if (is_wali_kelas()) {
    $userKelasId = get_user_kelas_id();
    if ($userKelasId) {
        $where[] = 's.kelas_id = ?';
        $params[] = $userKelasId;
    } else {
        $where[] = '1 = 0';
    }
}

if ($search !== '') {
    $where[] = '(s.nama LIKE ? OR s.nipd LIKE ? OR j.nama LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($kelasFilter > 0 && can_see_all_data()) {
    $where[] = 's.kelas_id = ?';
    $params[] = $kelasFilter;
}
if ($tahunAjaran !== '') {
    $where[] = 'k.tahun_ajaran = ?';
    $params[] = $tahunAjaran;
}

$whereSql = implode(' AND ', $where);

$total = db_fetch(
    "SELECT COUNT(*) AS c
     FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     LEFT JOIN kelas k ON k.id = s.kelas_id
     JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
     WHERE $whereSql",
    $params,
    'row'
);
$total = (int) ($total['c'] ?? 0);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listParams = array_merge($params, [$perPage, $offset]);
$list = db_fetch(
    "SELECT p.id, p.tanggal, p.lokasi, p.keterangan, p.tindakan, p.bukti_file, p.bukti_original, p.bukti_type,
            s.id AS siswa_id, s.nama AS siswa_nama, s.nipd, k.nama_kelas,
            j.nama AS jenis_nama, j.bobot_poin,
            u.nama AS pelapor,
            COALESCE((SELECT SUM(j2.bobot_poin)
                      FROM pelanggaran_siswa p2
                      JOIN jenis_pelanggaran j2 ON j2.id = p2.jenis_pelanggaran_id
                      WHERE p2.siswa_id = s.id), 0) AS total_poin
     FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     LEFT JOIN kelas k ON k.id = s.kelas_id
     JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
     LEFT JOIN users u ON u.id = p.pelapor_id
     WHERE $whereSql
     ORDER BY p.tanggal DESC, p.id DESC
     LIMIT ? OFFSET ?",
    $listParams
);
$list = $list ?: [];

$kelasList = db_fetch('SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas ASC');
$kelasList = $kelasList ?: [];

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Riwayat Pelanggaran<?php if (is_wali_kelas()): ?> - Kelas Saya<?php endif; ?></h3>
    <?php if (can_see_all_data()): ?>
        <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/tambah.php" class="primary-btn">+ Catat Pelanggaran</a>
    <?php endif; ?>
</div>

<div class="card table-card">
    <form method="get" action="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/riwayat.php" class="toolbar">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cari siswa / pelanggaran...">
        <?php if (can_see_all_data()): ?>
        <select name="kelas">
            <option value="0">Semua Kelas</option>
            <?php foreach ($kelasList as $k): ?>
                <option value="<?= (int) $k['id'] ?>" <?= $kelasFilter === (int) $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kelas']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <button type="submit" class="secondary-btn">Filter</button>
        <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/riwayat.php" class="ghost-btn">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th>Pelanggaran</th>
                    <th>Poin</th>
                    <th>Pelapor</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--text-muted);">Tidak ada catatan pelanggaran.</td></tr>
                <?php endif; ?>
                <?php foreach ($list as $r): ?>
                    <tr>
                        <td><?= e($r['tanggal']) ?></td>
                        <td>
                            <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/detail.php?id=<?= (int) $r['siswa_id'] ?>" style="color:var(--primary);font-weight:600;"><?= e($r['siswa_nama']) ?></a>
                            <div class="progress-summary">
                                <div class="progress-label">Pencapaian poin: <?= (int) $r['total_poin'] ?> / 100</div>
                                <div class="progress-track">
                                    <div class="progress-fill" style="width: <?= min(100, max(0, (int) round($r['total_poin'] * 100 / 100))) ?>%;"></div>
                                </div>
                            </div>
                        </td>
                        <td><?= e($r['nama_kelas'] ?? '-') ?></td>
                        <td><?= e($r['jenis_nama']) ?></td>
                        <td><span class="badge badge-warning"><?= (int) $r['bobot_poin'] ?></span></td>
                        <td><?= e($r['pelapor'] ?? '-') ?></td>
                        <td>
                            <?php if (can_see_all_data()): ?>
                            <div class="row-actions">
                                <?php if (!empty($r['bukti_file'])): ?>
                                    <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/download.php?id=<?= (int) $r['id'] ?>" title="Unduh bukti: <?= e($r['bukti_original']) ?>">📎 <?= strtoupper(pathinfo($r['bukti_original'], PATHINFO_EXTENSION)) ?></a>
                                <?php endif; ?>
                                <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/edit.php?id=<?= (int) $r['id'] ?>" class="link-btn link-edit">Edit</a>
                                <form method="post" action="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/hapus.php?id=<?= (int) $r['id'] ?>" data-confirm="Hapus catatan pelanggaran ini?">
                                    <button type="submit" class="link-btn link-delete">Hapus</button>
                                </form>
                            </div>
                            <?php else: ?>
                                <span style="color: var(--text-muted); font-size: 12px;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $qs = http_build_query(array_filter(['q' => $search, 'kelas' => $kelasFilter]));
            $qs = $qs !== '' ? '&' . $qs : '';
            $pageUrl = rtrim(APP_BASE, '/') . '/pelanggaran/riwayat.php?page=';
            ?>
            <span>Menampilkan <?= $total === 0 ? 0 : (($page - 1) * $perPage + 1) ?>–<?= min($page * $perPage, $total) ?> dari <?= $total ?> data</span>
            <div style="display:flex;gap:6px;">
                <?php if ($page > 1): ?><a href="<?= $pageUrl ?><?= $page - 1 ?><?= $qs ?>">Prev</a><?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $page): ?><span class="current"><?= $i ?></span>
                    <?php else: ?><a href="<?= $pageUrl ?><?= $i ?><?= $qs ?>"><?= $i ?></a><?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?><a href="<?= $pageUrl ?><?= $page + 1 ?><?= $qs ?>">Next</a><?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>