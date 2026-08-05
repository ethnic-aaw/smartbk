<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Only Admin & Guru BK
if (!can_see_all_data()) {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$pageTitle = 'Konsultasi Siswa';
$activeMenu = 'konsultasi';

$search = trim($_GET['q'] ?? '');
$dari = trim($_GET['dari'] ?? '');
$sampai = trim($_GET['sampai'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$where = ['1 = 1'];
$params = [];

if ($search !== '') {
    $where[] = '(s.nama LIKE ? OR s.nipd LIKE ? OR k.permasalahan LIKE ? OR k.tindak_lanjut LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($dari !== '') {
    $where[] = 'k.tanggal >= ?';
    $params[] = $dari;
}
if ($sampai !== '') {
    $where[] = 'k.tanggal <= ?';
    $params[] = $sampai;
}

$whereSql = implode(' AND ', $where);

$total = db_fetch(
    "SELECT COUNT(*) AS c FROM konsultasi_siswa k JOIN siswa s ON s.id = k.siswa_id WHERE $whereSql",
    $params,
    'row'
);
$total = (int) ($total['c'] ?? 0);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listParams = array_merge($params, [$perPage, $offset]);
$list = db_fetch(
    "SELECT k.*, s.nama AS siswa_nama, s.nipd, s.foto, kl.nama_kelas, u.nama AS konselor
     FROM konsultasi_siswa k
     JOIN siswa s ON s.id = k.siswa_id
     LEFT JOIN kelas kl ON kl.id = s.kelas_id
     LEFT JOIN users u ON u.id = k.konselor_id
     WHERE $whereSql
     ORDER BY k.tanggal DESC, k.id DESC
     LIMIT ? OFFSET ?",
    $listParams
);
$list = $list ?: [];

function ringkas(string $text, int $max = 80): string
{
    $text = trim($text);
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    return mb_substr($text, 0, $max) . '…';
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Konsultasi Siswa</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/konsultasi/tambah.php" class="primary-btn">+ Catat Konsultasi</a>
</div>

<div class="card table-card">
    <form method="get" action="<?= rtrim(APP_BASE, '/') ?>/konsultasi/index.php" class="toolbar">
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Cari nama siswa / permasalahan...">
        <input type="date" name="dari" value="<?= e($dari) ?>" title="Dari tanggal">
        <span style="color: var(--text-muted); font-size: 13px;">s/d</span>
        <input type="date" name="sampai" value="<?= e($sampai) ?>" title="Sampai tanggal">
        <button type="submit" class="secondary-btn">Filter</button>
        <a href="<?= rtrim(APP_BASE, '/') ?>/konsultasi/index.php" class="ghost-btn">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:50px;">No</th>
                    <th>Siswa</th>
                    <th>Kelas</th>
                    <th>Tanggal</th>
                    <th>Permasalahan</th>
                    <th>Tindak Lanjut</th>
                    <th>Lampiran</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="8" style="text-align:center;color:var(--text-muted);">Belum ada catatan konsultasi.</td></tr>
                <?php endif; ?>
                <?php foreach ($list as $i => $k): ?>
                    <tr>
                        <td><?= (($page - 1) * $perPage) + $i + 1 ?></td>
                        <td>
                            <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/detail.php?id=<?= (int) $k['siswa_id'] ?>" style="color:var(--primary);font-weight:600;"><?= e($k['siswa_nama']) ?></a>
                            <div style="font-size:12px;color:var(--text-muted);"><?= e($k['nipd']) ?></div>
                        </td>
                        <td><?= e($k['nama_kelas'] ?? '-') ?></td>
                        <td><?= e($k['tanggal']) ?></td>
                        <td style="max-width:220px;"><?= e(ringkas($k['permasalahan'] ?: '-')) ?></td>
                        <td style="max-width:220px;"><?= e(ringkas($k['tindak_lanjut'] ?: '-')) ?></td>
                        <td>
                            <?php if (!empty($k['lampiran_file'])): ?>
                                <a href="<?= rtrim(APP_BASE, '/') ?>/konsultasi/download.php?id=<?= (int) $k['id'] ?>" title="<?= e($k['lampiran_original']) ?>">
                                    <span class="badge badge-good">📎 <?= e(pathinfo($k['lampiran_original'], PATHINFO_EXTENSION)) ?></span>
                                </a>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a href="<?= rtrim(APP_BASE, '/') ?>/konsultasi/edit.php?id=<?= (int) $k['id'] ?>" class="link-btn link-edit">Edit</a>
                                <form method="post" action="<?= rtrim(APP_BASE, '/') ?>/konsultasi/hapus.php?id=<?= (int) $k['id'] ?>" data-confirm="Hapus catatan konsultasi <?= e($k['siswa_nama']) ?>?">
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
            $pageUrl = rtrim(APP_BASE, '/') . '/konsultasi/index.php?page=';
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
