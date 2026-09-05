<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Master User';
$activeMenu = 'user';

// Only Admin & Guru BK can manage users
if (!can_see_all_data()) {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$roleFilter = trim($_GET['role'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$validRoles = ['Admin', 'Guru BK', 'Wali Kelas', 'Siswa'];
$validStatuses = ['Aktif', 'Nonaktif', 'pending', 'approved', 'rejected'];
if (!in_array($roleFilter, $validRoles, true)) {
    $roleFilter = '';
}
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = '';
}

$where = ['1=1'];
$params = [];

if ($roleFilter !== '') {
    $where[] = 'u.role = ?';
    $params[] = $roleFilter;
}
if ($statusFilter !== '') {
    if ($statusFilter === 'pending' || $statusFilter === 'approved' || $statusFilter === 'rejected') {
        $where[] = 'u.approval_status = ?';
    } else {
        $where[] = 'u.status = ?';
    }
    $params[] = $statusFilter;
}

$whereSql = implode(' AND ', $where);

$userList = db_fetch(
    "SELECT u.*, k.nama_kelas
     FROM users u
     LEFT JOIN kelas k ON k.id = u.kelas_id
     WHERE $whereSql
     ORDER BY FIELD(u.role, 'Admin', 'Guru BK', 'Wali Kelas', 'Siswa'), u.nama ASC",
    $params
);
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
    <div style="display: flex; gap: 8px; align-items: center;">
        <a href="<?= rtrim(APP_BASE, '/') ?>/user/tambah.php" class="primary-btn">+ Tambah User</a>
        <a href="<?= rtrim(APP_BASE, '/') ?>/user/approval.php" class="secondary-btn">Persetujuan</a>
    </div>
</div>

<div class="toolbar">
    <a href="<?= rtrim(APP_BASE, '/') ?>/user/index.php" class="secondary-btn <?= $roleFilter === '' ? 'ghost-btn' : '' ?>">Semua Role</a>
    <?php foreach ($validRoles as $r): ?>
        <a href="<?= rtrim(APP_BASE, '/') ?>/user/index.php?role=<?= urlencode($r) ?>&status=<?= urlencode($statusFilter) ?>"
           class="<?= $roleFilter === $r ? 'primary-btn' : 'secondary-btn' ?>"><?= e($r) ?></a>
    <?php endforeach; ?>
</div>

<div class="toolbar" style="margin-top: 8px;">
    <a href="<?= rtrim(APP_BASE, '/') ?>/user/index.php?status=all" class="secondary-btn <?= $statusFilter === '' ? 'ghost-btn' : '' ?>">Semua Status</a>
    <?php foreach ($validStatuses as $s): ?>
        <a href="<?= rtrim(APP_BASE, '/') ?>/user/index.php?status=<?= urlencode($s) ?>&role=<?= urlencode($roleFilter) ?>"
           class="<?= $statusFilter === $s ? 'primary-btn' : 'secondary-btn' ?>">
            <?= $s === 'pending' ? 'Menunggu' : ($s === 'approved' ? 'Disetujui' : ($s === 'rejected' ? 'Ditolak' : ucfirst($s))) ?>
        </a>
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
                    <th>Approval</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$userList): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--text-muted);">Tidak ada data user.</td></tr>
                <?php else: ?>
                    <?php foreach ($userList as $u): ?>
                        <tr>
                            <td><strong><?= e($u['nama']) ?></strong></td>
                            <td><?= e($u['username']) ?></td>
                            <td><span class="badge <?= $roleBadge[$u['role']] ?? 'badge-good' ?>"><?= e($u['role']) ?></span></td>
                            <td><?= e($u['nama_kelas'] ?? '-') ?></td>
                            <td><?= e($u['status']) ?></td>
                            <td><?= approval_badge($u['approval_status'] ?? 'approved') ?></td>
                            <td>
                                <div class="row-actions">
                                    <a href="<?= rtrim(APP_BASE, '/') ?>/user/edit.php?id=<?= (int) $u['id'] ?>" class="link-btn link-edit">Edit / Reset</a>
                                    <?php if (($u['approval_status'] ?? 'approved') === 'pending'): ?>
                                        <form method="post" action="<?= rtrim(APP_BASE, '/') ?>/user/approval.php" style="display:inline;" onsubmit="return confirm('Yakin menyetujui user <?= e($u['nama']) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                            <button type="submit" class="link-btn link-edit">✓ Setujui</button>
                                        </form>
                                        <form method="post" action="<?= rtrim(APP_BASE, '/') ?>/user/approval.php" style="display:inline;" onsubmit="return confirm('Yakin menolak user <?= e($u['nama']) ?>?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                            <button type="submit" class="link-btn link-delete">✗ Tolak</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="post" action="<?= rtrim(APP_BASE, '/') ?>/user/hapus.php?id=<?= (int) $u['id'] ?>" data-confirm="Hapus user <?= e($u['nama']) ?>?">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="link-btn link-delete">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>