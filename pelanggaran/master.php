<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Master Pelanggaran';
$activeMenu = 'pelanggaran_master';

// Only Admin can manage jenis pelanggaran
if ($_SESSION['user']['role'] !== 'Admin') {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$list = db_fetch('SELECT * FROM jenis_pelanggaran ORDER BY bobot_poin ASC, kode ASC');
$list = $list ?: [];

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Pelanggaran Semua Siswa</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/jenis_tambah.php" class="primary-btn">+ Tambah Pelanggaran</a>
</div>
<div class="card table-card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Pelanggaran</th>
                    <th>Komponen</th>
                    <th>Kategori</th>
                    <th>Bobot Poin</th>
                    <th>Deskripsi</th>
                    <th>Konsekuensi</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="8" style="text-align:center;color:var(--text-muted);">Belum ada jenis pelanggaran.</td></tr>
                <?php endif; ?>
                <?php foreach ($list as $p): ?>
                    <tr>
                        <td><strong><?= e($p['kode']) ?></strong></td>
                        <td><?= e($p['nama']) ?></td>
                        <td><?= e($p['komponen'] ?: '-') ?></td>
                        <td><span class="badge badge-warning"><?= e($p['kategori']) ?></span></td>
                        <td><?= (int) $p['bobot_poin'] ?></td>
                        <td><?= e($p['deskripsi'] ?: '-') ?></td>
                        <td><?= e($p['konsekuensi'] ?: '-') ?></td>
                        <td>
                            <div class="row-actions">
                                <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/jenis_edit.php?id=<?= (int) $p['id'] ?>" class="link-btn link-edit">Edit</a>
                                <form method="post" action="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/jenis_hapus.php?id=<?= (int) $p['id'] ?>" data-confirm="Hapus jenis pelanggaran <?= e($p['nama']) ?>?">
                                    <button type="submit" class="link-btn link-delete">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>