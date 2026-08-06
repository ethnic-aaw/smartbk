<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Master Siswa';
$activeMenu = 'siswa';

$search = trim($_GET['q'] ?? '');
$kelasFilter = (int) ($_GET['kelas'] ?? 0);
$tahunFilter = trim($_GET['tahun'] ?? current_tahun_ajaran());
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPageOptions = [10, 15, 25, 50, 100];
$perPage = (int) ($_GET['per_page'] ?? 15);
if (!in_array($perPage, $perPageOptions, true)) {
    $perPage = 15;
}

$where = ['1 = 1'];
$params = [];

// Role-based: Wali Kelas hanya melihat siswa di kelasnya
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
    $where[] = '(s.nama LIKE ? OR s.nipd LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}
if ($kelasFilter > 0 && can_see_all_data()) {
    $where[] = 's.kelas_id = ?';
    $params[] = $kelasFilter;
}
if ($tahunFilter !== '') {
    $where[] = 'k.tahun_ajaran = ?';
    $params[] = $tahunFilter;
}

$whereSql = implode(' AND ', $where);

$total = db_fetch("SELECT COUNT(*) AS c FROM siswa s LEFT JOIN kelas k ON k.id = s.kelas_id WHERE $whereSql", $params, 'row');
$total = (int) ($total['c'] ?? 0);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listParams = array_merge($params, [$perPage, $offset]);
$siswaList = db_fetch(
    "SELECT s.*, k.nama_kelas, k.tahun_ajaran,
        COALESCE((SELECT SUM(j.bobot_poin)
                  FROM pelanggaran_siswa p
                  JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
                  WHERE p.siswa_id = s.id), 0) AS total_poin
     FROM siswa s
     LEFT JOIN kelas k ON k.id = s.kelas_id
     WHERE $whereSql
     ORDER BY s.nama ASC
     LIMIT ? OFFSET ?",
    $listParams
);

$kelasList = db_fetch('SELECT id, nama_kelas, tahun_ajaran FROM kelas ORDER BY nama_kelas ASC');
$kelasList = $kelasList ?: [];
$tahunList = db_fetch('SELECT DISTINCT tahun_ajaran FROM kelas ORDER BY tahun_ajaran DESC');
$tahunList = $tahunList ?: [];

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Daftar Siswa<?php if (is_wali_kelas()): ?> - Kelas Saya<?php endif; ?></h3>
    <div class="row-actions">
        <?php if (can_see_all_data()): ?>
            <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/import.php" class="secondary-btn">📤 Import CSV</a>
            <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/tambah.php" class="primary-btn">+ Tambah Siswa</a>
        <?php endif; ?>
    </div>
</div>

<div class="card table-card">
    <form method="get" action="<?= rtrim(APP_BASE, '/') ?>/siswa/index.php" class="toolbar">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cari nama / NIPD...">
        <?php if (can_see_all_data()): ?>
        <select name="tahun">
            <option value="">Semua Tahun Ajaran</option>
            <?php foreach ($tahunList as $t): ?>
                <option value="<?= e($t['tahun_ajaran']) ?>" <?= $tahunFilter === $t['tahun_ajaran'] ? 'selected' : '' ?>><?= e($t['tahun_ajaran']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="kelas">
            <option value="0">Semua Kelas</option>
            <?php foreach ($kelasList as $k): ?>
                <option value="<?= (int) $k['id'] ?>" <?= $kelasFilter === (int) $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kelas']) ?> (<?= e($k['tahun_ajaran']) ?>)</option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="secondary-btn">Filter</button>
        <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/index.php" class="ghost-btn">Reset</a>
        <?php endif; ?>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="text-align:center;">No</th>
                    <th>Foto</th>
                    <th>NIPD/NIS</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>JK</th>
                    <th>Status</th>
                    <th>Total Poin</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$siswaList): ?>
                    <tr><td colspan="9" style="text-align:center; color:var(--text-muted);">Tidak ada data siswa.</td></tr>
                <?php endif; ?>
                <?php $nomor = $offset; foreach ($siswaList as $s): $nomor++; ?>
                    <tr>
                        <td style="text-align:center;"><?= $nomor ?></td>
                        <td>
                            <?php if (!empty($s['foto']) && file_exists(__DIR__ . '/../assets/uploads/foto_siswa/' . $s['foto'])): ?>
                                <img src="<?= rtrim(APP_BASE, '/') ?>/assets/uploads/foto_siswa/<?= e($s['foto']) ?>" alt="" class="table-avatar">
                            <?php else: ?>
                                <div class="avatar" style="width:36px;height:36px;font-size:14px;"><?= strtoupper(substr($s['nama'], 0, 1)) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= e($s['nipd']) ?></td>
                        <td><a href="<?= rtrim(APP_BASE, '/') ?>/siswa/detail.php?id=<?= (int) $s['id'] ?>" style="color:var(--primary);font-weight:600;"><?= e($s['nama']) ?></a></td>
                        <td><?= e($s['nama_kelas'] ? $s['nama_kelas'] . ' (' . $s['tahun_ajaran'] . ')' : '-') ?></td>
                        <td><?= e($s['jenis_kelamin']) ?></td>
                        <td><?= e($s['status']) ?></td>
                        <td><?= poin_badge((int) $s['total_poin']) ?> <?= fase_badge((int) $s['total_poin']) ?></td>
                        <td>
                            <div class="row-actions">
                                <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/detail.php?id=<?= (int) $s['id'] ?>" class="ghost-btn">Detail</a>
                                <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/edit.php?id=<?= (int) $s['id'] ?>" class="link-btn link-edit">Edit</a>
                                <form method="post" action="<?= rtrim(APP_BASE, '/') ?>/siswa/hapus.php?id=<?= (int) $s['id'] ?>" data-confirm="Yakin ingin menghapus siswa <?= e($s['nama']) ?>? Semua riwayat pelanggarannya juga akan terhapus.">
                                    <button type="submit" class="link-btn link-delete">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total > 0): ?>
        <div class="pagination">
            <?php
            $qs = http_build_query(array_filter(['q' => $search, 'tahun' => $tahunFilter, 'kelas' => $kelasFilter, 'per_page' => $perPage]));
            $qs = $qs !== '' ? '&' . $qs : '';
            $pageUrl = rtrim(APP_BASE, '/') . '/siswa/index.php?page=';
            ?>
            <span>Menampilkan <?= $total === 0 ? 0 : (($page - 1) * $perPage + 1) ?>–<?= min($page * $perPage, $total) ?> dari <?= $total ?> data</span>
            <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;">
                <form method="get" action="<?= rtrim(APP_BASE, '/') ?>/siswa/index.php" style="display:inline-flex;align-items:center;gap:6px;">
                    <label for="per_page" style="font-size:13px;color:var(--text-muted);">Tampilkan</label>
                    <select name="per_page" id="per_page" onchange="this.form.submit()">
                        <?php foreach ($perPageOptions as $opt): ?>
                            <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="q" value="<?= e($search) ?>">
                    <input type="hidden" name="tahun" value="<?= e($tahunFilter) ?>">
                    <input type="hidden" name="kelas" value="<?= (int) $kelasFilter ?>">
                </form>
                <?php if ($totalPages > 1): ?>
                <div style="display:flex;gap:6px;">
                    <?php if ($page > 1): ?>
                        <a href="<?= $pageUrl ?><?= $page - 1 ?><?= $qs ?>">Prev</a>
                    <?php endif; ?>
                    <?php
                    $window = 2;
                    $pagesToShow = [];
                    for ($i = max(1, $page - $window); $i <= min($totalPages, $page + $window); $i++) {
                        $pagesToShow[] = $i;
                    }
                    if (!in_array(1, $pagesToShow, true)) {
                        array_unshift($pagesToShow, 1);
                    }
                    if (!in_array($totalPages, $pagesToShow, true)) {
                        $pagesToShow[] = $totalPages;
                    }
                    $pagesToShow = array_values(array_unique($pagesToShow));
                    sort($pagesToShow);

                    $prevPage = 0;
                    foreach ($pagesToShow as $p):
                        if ($prevPage > 0 && $p - $prevPage > 1): ?>
                            <span style="border:0;background:transparent;padding:6px 2px;">…</span>
                        <?php endif;
                        if ($p === $page): ?>
                            <span class="current"><?= $p ?></span>
                        <?php else: ?>
                            <a href="<?= $pageUrl ?><?= $p ?><?= $qs ?>"><?= $p ?></a>
                        <?php endif;
                        $prevPage = $p;
                    endforeach;
                    ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= $pageUrl ?><?= $page + 1 ?><?= $qs ?>">Next</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
