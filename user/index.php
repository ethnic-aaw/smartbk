<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Master User';
$activeMenu = 'user';

// Only Admin can manage users
if ($_SESSION['user']['role'] !== 'Admin') {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$roleFilter = trim($_GET['role'] ?? '');
$validRoles = ['Admin', 'Guru BK', 'Wali Kelas', 'Siswa'];
if (!in_array($roleFilter, $validRoles, true)) {
    $roleFilter = '';
}

if ($roleFilter !== '') {
    $userList = db_fetch(
        'SELECT u.*, k.nama_kelas
         FROM users u
         LEFT JOIN kelas k ON k.id = u.kelas_id
         WHERE u.role = ?
         ORDER BY u.nama ASC',
        [$roleFilter]
    );
} else {
    $userList = db_fetch(
        'SELECT u.*, k.nama_kelas
         FROM users u
         LEFT JOIN kelas k ON k.id = u.kelas_id
         ORDER BY FIELD(u.role, "Admin", "Guru BK", "Wali Kelas", "Siswa"), u.nama ASC'
    );
}
$userList = $userList ?: [];

$roleBadge = [
    'Admin' => 'badge-good',
    'Guru BK' => 'badge-warning',
    'Wali Kelas' => 'badge-warning',
    'Siswa' => 'badge-danger',
];

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Daftar User</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/user/tambah.php" class="primary-btn">+ Tambah User</a>
</div>

<div class="toolbar">
    <a href="<?= rtrim(APP_BASE, '/') ?>/user/index.php" class="secondary-btn <?= $roleFilter === '' ? 'ghost-btn' : '' ?>">Semua</a>
    <?php foreach ($validRoles as $r): ?>
        <a href="<?= rtrim(APP_BASE, '/') ?>/user/index.php?role=<?= urlencode($r) ?>"
           class="<?= $roleFilter === $r ? 'primary-btn' : 'secondary-btn' ?>"><?= e($r) ?></a>
    <?php endforeach; ?>
</div>

<div class="card table-card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Kelas</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$userList): ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">Tidak ada data user.</td></tr>
                <?php endif; ?>
                <?php foreach ($userList as $u): ?>
                    <tr>
                        <td><strong><?= e($u['nama']) ?></strong></td>
                        <td><?= e($u['username']) ?></td>
                        <td><span class="badge <?= $roleBadge[$u['role']] ?? 'badge-good' ?>"><?= e($u['role']) ?></span></td>
                        <td><?= e($u['nama_kelas'] ?? '-') ?></td>
                        <td><?= e($u['status']) ?></td>
                        <td>
                            <div class="row-actions">
                                <a href="<?= rtrim(APP_BASE, '/') ?>/user/edit.php?id=<?= (int) $u['id'] ?>" class="link-btn link-edit">Edit / Reset</a>
                                <form method="post" action="<?= rtrim(APP_BASE, '/') ?>/user/hapus.php?id=<?= (int) $u['id'] ?>" data-confirm="Hapus user <?= e($u['nama']) ?>?">
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