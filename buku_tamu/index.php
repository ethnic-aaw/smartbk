<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Only Admin & Guru BK
if (!can_see_all_data()) {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$pageTitle = 'Buku Tamu';
$activeMenu = 'buku_tamu';

$search = trim($_GET['q'] ?? '');
$dari = trim($_GET['dari'] ?? '');
$sampai = trim($_GET['sampai'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$where = ['1 = 1'];
$params = [];

if ($search !== '') {
    $where[] = '(t.nama_tamu LIKE ? OR t.keperluan LIKE ? OR t.tindak_lanjut LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($dari !== '') {
    $where[] = 't.tanggal >= ?';
    $params[] = $dari;
}
if ($sampai !== '') {
    $where[] = 't.tanggal <= ?';
    $params[] = $sampai;
}

$whereSql = implode(' AND ', $where);

$total = db_fetch("SELECT COUNT(*) AS c FROM buku_tamu t WHERE $whereSql", $params, 'row');
$total = (int) ($total['c'] ?? 0);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listParams = array_merge($params, [$perPage, $offset]);
$list = db_fetch(
    "SELECT t.*, u.nama AS pencatat
     FROM buku_tamu t
     LEFT JOIN users u ON u.id = t.pencatat_id
     WHERE $whereSql
     ORDER BY t.tanggal DESC, t.id DESC
     LIMIT ? OFFSET ?",
    $listParams
);
$list = $list ?: [];

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Buku Tamu</h3>
    <div class="row-actions">
        <a href="<?= rtrim(APP_BASE, '/') ?>/buku_tamu/tambah.php" class="primary-btn">+ Catat Tamu</a>
    </div>
</div>

<div class="card table-card">
    <form method="get" action="<?= rtrim(APP_BASE, '/') ?>/buku_tamu/index.php" class="toolbar">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cari nama tamu / keperluan...">
        <input type="date" name="dari" value="<?= e($dari) ?>" title="Dari tanggal">
        <span style="color: var(--text-muted); font-size: 13px;">s/d</span>
        <input type="date" name="sampai" value="<?= e($sampai) ?>" title="Sampai tanggal">
        <button type="submit" class="secondary-btn">Filter</button>
        <a href="<?= rtrim(APP_BASE, '/') ?>/buku_tamu/index.php" class="ghost-btn">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:50px;">No</th>
                    <th>Hari &amp; Tanggal</th>
                    <th>Nama Tamu</th>
                    <th>Keperluan</th>
                    <th>Tindak Lanjut Guru BK</th>
                    <th>Pencatat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--text-muted);">Belum ada catatan buku tamu.</td></tr>
                <?php endif; ?>
                <?php foreach ($list as $i => $t): ?>
                    <?php
                    $dayNames = [
                        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
                    ];
                    $day = $dayNames[date('l', strtotime($t['tanggal']))] ?? date('l', strtotime($t['tanggal']));
                    $dateFormatted = date('d M Y', strtotime($t['tanggal']));
                    ?>
                    <tr>
                        <td><?= (($page - 1) * $perPage) + $i + 1 ?></td>
                        <td>
                            <strong><?= e($day) ?></strong><br>
                            <span style="font-size:12px;color:var(--text-muted);"><?= e($dateFormatted) ?></span>
                        </td>
                        <td><strong><?= e($t['nama_tamu']) ?></strong></td>
                        <td><?= nl2br(e($t['keperluan'] ?: '-')) ?></td>
                        <td><?= nl2br(e($t['tindak_lanjut'] ?: '-')) ?></td>
                        <td><?= e($t['pencatat'] ?? '-') ?></td>
                        <td>
                            <div class="row-actions">
                                <a href="<?= rtrim(APP_BASE, '/') ?>/buku_tamu/edit.php?id=<?= (int) $t['id'] ?>" class="link-btn link-edit">Edit</a>
                                <form method="post" action="<?= rtrim(APP_BASE, '/') ?>/buku_tamu/hapus.php?id=<?= (int) $t['id'] ?>" data-confirm="Hapus catatan tamu <?= e($t['nama_tamu']) ?>?">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="link-btn link-delete">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $qs = http_build_query(array_filter(['q' => $search, 'dari' => $dari, 'sampai' => $sampai]));
            $qs = $qs !== '' ? '&' . $qs : '';
            $pageUrl = rtrim(APP_BASE, '/') . '/buku_tamu/index.php?page=';
            ?>
            <span>Menampilkan <?= $total === 0 ? 0 : (($page - 1) * $perPage + 1) ?>–<?= min($page * $perPage, $total) ?> dari <?= $total ?> data</span>
            <div style="display:flex;gap:6px;">
                <?php if ($page > 1): ?>
                    <a href="<?= $pageUrl ?><?= $page - 1 ?><?= $qs ?>">Prev</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= $pageUrl ?><?= $i ?><?= $qs ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="<?= $pageUrl ?><?= $page + 1 ?><?= $qs ?>">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
