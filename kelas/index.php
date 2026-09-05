<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Master Kelas';
$activeMenu = 'kelas';

// Admin + Guru BK dapat mengelola kelas; Wali Kelas read-only.
if (!can_see_all_data()) {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$tahunAjaran = current_tahun_ajaran();
$kelasList = db_fetch(
    'SELECT k.*, u.nama AS wali_kelas,
            (SELECT COUNT(*) FROM siswa s WHERE s.kelas_id = k.id) AS jumlah_siswa
     FROM kelas k
     LEFT JOIN users u ON u.id = k.wali_kelas_id
     WHERE k.tahun_ajaran = ?
     ORDER BY k.nama_kelas ASC',
    [$tahunAjaran]
);
$kelasList = $kelasList ?: [];

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Daftar Kelas</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/kelas/tambah.php" class="primary-btn">+ Tambah Kelas</a>
</div>
<div class="card table-card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nama Kelas</th>
                    <th>Tingkat</th>
                    <th>Tahun Ajaran</th>
                    <th>Wali Kelas</th>
                    <th>Jumlah Siswa</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$kelasList): ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">Belum ada data kelas.</td></tr>
                <?php endif; ?>
                <?php foreach ($kelasList as $k): ?>
                    <tr>
                        <td><strong><?= e($k['nama_kelas']) ?></strong></td>
                        <td><?= e($k['tingkat']) ?></td>
                        <td><?= e($k['tahun_ajaran']) ?></td>
                        <td><?= e($k['wali_kelas'] ?? '-') ?></td>
                        <td><?= (int) $k['jumlah_siswa'] ?> siswa</td>
                        <td>
                            <div class="row-actions">
                                <a href="<?= rtrim(APP_BASE, '/') ?>/kelas/edit.php?id=<?= (int) $k['id'] ?>" class="link-btn link-edit">Edit</a>
                                <form method="post" action="<?= rtrim(APP_BASE, '/') ?>/kelas/hapus.php?id=<?= (int) $k['id'] ?>" data-confirm="Hapus kelas <?= e($k['nama_kelas']) ?>? Siswa dalam kelas tersebut akan menjadi tanpa kelas.">
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
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>