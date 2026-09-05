<?php
/**
 * Halaman Persetujuan User (Admin & Guru BK)
 * Mengelola approval user yang registrasi via Google OAuth
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

// Hanya Admin & Guru BK bisa approve
if (!can_approve_users()) {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$pageTitle = 'Persetujuan User';
$activeMenu = 'user';

// Handle approve/reject action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        set_flash('error', 'Token CSRF tidak valid.');
    } else {
        $action = $_POST['action'] ?? '';
        $userId = (int) ($_POST['user_id'] ?? 0);
        $note = trim($_POST['note'] ?? '');
        
        if ($userId > 0 && in_array($action, ['approve', 'reject'], true)) {
            $user = db_fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [$userId], 'row');
            
            if ($user) {
                if ($user['approval_status'] !== 'pending') {
                    set_flash('error', 'User ini sudah diproses sebelumnya.');
                } else {
                    $newStatus = $action === 'approve' ? 'approved' : 'rejected';
                    $now = date('Y-m-d H:i:s');
                    
                    db_query(
                        'UPDATE users SET approval_status = ?, approved_by = ?, approved_at = ? WHERE id = ?',
                        [$newStatus, $_SESSION['user']['id'], $now, $userId]
                    );
                    
                    // Log ke user_approvals
                    db_query(
                        'INSERT INTO user_approvals (user_id, approver_id, action, note) VALUES (?, ?, ?, ?)',
                        [$userId, $_SESSION['user']['id'], $action === 'approve' ? 'approved' : 'rejected', $note]
                    );
                    
                    // Jika approve Wali Kelas, update kelas.wali_kelas_id
                    if ($action === 'approve' && $user['role'] === 'Wali Kelas' && !empty($user['kelas_id'])) {
                        db_query(
                            'UPDATE kelas SET wali_kelas_id = ? WHERE id = ?',
                            [$userId, $user['kelas_id']]
                        );
                    }
                    
                    // Jika approve Siswa, pastikan siswa.google_id & email sudah terisi
                    if ($action === 'approve' && $user['role'] === 'Siswa' && !empty($user['siswa_id'])) {
                        // Sudah di-handle di registrasi, tapi double check
                        db_query(
                            'UPDATE siswa SET google_id = (SELECT google_id FROM users WHERE id = ?), email = (SELECT email FROM users WHERE id = ?) WHERE id = ?',
                            [$userId, $userId, $user['siswa_id']]
                        );
                    }
                    
                    set_flash('success', 'User ' . $user['nama'] . ' berhasil ' . ($action === 'approve' ? 'disetujui' : 'ditolak') . '.');
                }
            } else {
                set_flash('error', 'User tidak ditemukan.');
            }
        } else {
            set_flash('error', 'Data tidak valid.');
        }
    }
    redirect_to(rtrim(APP_BASE, '/') . '/user/approval.php');
}

// Filter
$statusFilter = $_GET['status'] ?? 'pending';
$validStatuses = ['pending', 'approved', 'rejected', 'all'];
if (!in_array($statusFilter, $validStatuses, true)) {
    $statusFilter = 'pending';
}

$roleFilter = $_GET['role'] ?? '';
$validRoles = ['Guru BK', 'Wali Kelas', 'Guru', 'Siswa'];

// Build query
$where = ['1=1'];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = 'approval_status = ?';
    $params[] = $statusFilter;
}

if ($roleFilter !== '') {
    $where[] = 'role = ?';
    $params[] = $roleFilter;
}

// Exclude Admin dari daftar approval (Admin tidak perlu approval)
$where[] = 'role != ?';
$params[] = 'Admin';

$whereSql = implode(' AND ', $where);

$userList = db_fetch(
    "SELECT u.*, k.nama_kelas, s.nipd, s.nama AS siswa_nama
     FROM users u
     LEFT JOIN kelas k ON k.id = u.kelas_id
     LEFT JOIN siswa s ON s.id = u.siswa_id
     WHERE $whereSql
     ORDER BY 
        CASE u.approval_status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 WHEN 'rejected' THEN 2 ELSE 3 END,
        u.created_at DESC",
    $params
);
$userList = $userList ?: [];

// Stats
$stats = db_fetch(
    "SELECT 
        SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) AS approved,
        SUM(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) AS rejected
     FROM users 
     WHERE role != 'Admin'",
    [],
    'row'
);
?>

<?php
require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Persetujuan User</h3>
</div>

<div class="toolbar" style="margin-bottom: 1rem;">
    <a href="<?= rtrim(APP_BASE, '/') ?>/user/approval.php?status=all" class="<?= $statusFilter === 'all' ? 'primary-btn' : 'secondary-btn' ?>">
        Semua <span class="badge badge-good"><?= $stats['pending'] + $stats['approved'] + $stats['rejected'] ?></span>
    </a>
    <a href="<?= rtrim(APP_BASE, '/') ?>/user/approval.php?status=pending" class="<?= $statusFilter === 'pending' ? 'primary-btn' : 'secondary-btn' ?>">
        Menunggu <span class="badge badge-warning"><?= (int)($stats['pending'] ?? 0) ?></span>
    </a>
    <a href="<?= rtrim(APP_BASE, '/') ?>/user/approval.php?status=approved" class="<?= $statusFilter === 'approved' ? 'primary-btn' : 'secondary-btn' ?>">
        Disetujui <span class="badge badge-good"><?= (int)($stats['approved'] ?? 0) ?></span>
    </a>
    <a href="<?= rtrim(APP_BASE, '/') ?>/user/approval.php?status=rejected" class="<?= $statusFilter === 'rejected' ? 'primary-btn' : 'secondary-btn' ?>">
        Ditolak <span class="badge badge-danger"><?= (int)($stats['rejected'] ?? 0) ?></span>
    </a>
</div>

<?php if ($roleFilter !== '' || $statusFilter !== 'all'): ?>
<div class="toolbar" style="margin-bottom: 1rem;">
    <?php foreach ($validRoles as $r): ?>
        <a href="<?= rtrim(APP_BASE, '/') ?>/user/approval.php?status=<?= $statusFilter ?>&role=<?= urlencode($r) ?>"
           class="<?= $roleFilter === $r ? 'primary-btn' : 'secondary-btn' ?>">
            <?= $r ?>
        </a>
    <?php endforeach; ?>
    <a href="<?= rtrim(APP_BASE, '/') ?>/user/approval.php?status=pending" class="ghost-btn">Reset Filter</a>
</div>
<?php endif; ?>

<div class="card table-card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Kelas / NIPD</th>
                    <th>Status</th>
                    <th>Daftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$userList): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--text-muted);">Tidak ada data user.</td></tr>
                <?php else: ?>
                    <?php foreach ($userList as $u): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['nama']) ?></strong></td>
                            <td><?= htmlspecialchars($u['email'] ?? $u['username']) ?></td>
                            <td>
                                <?php
                                $roleBadge = [
                                    'Guru BK' => 'badge-warning',
                                    'Wali Kelas' => 'badge-warning',
                                    'Guru' => 'badge-good',
                                    'Siswa' => 'badge-danger',
                                ];
                                ?>
                                <span class="badge <?= $roleBadge[$u['role']] ?? 'badge-good' ?>"><?= htmlspecialchars($u['role']) ?></span>
                            </td>
                            <td>
                                <?php if ($u['role'] === 'Wali Kelas' || $u['role'] === 'Guru'): ?>
                                    <?= htmlspecialchars($u['nama_kelas'] ?? '-') ?>
                                <?php elseif ($u['role'] === 'Siswa'): ?>
                                    NIPD: <?= htmlspecialchars($u['nipd'] ?? '-') ?>
                                    (<?= htmlspecialchars($u['siswa_nama'] ?? '-') ?>)
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $statusClass = [
                                    'pending' => 'badge-warning',
                                    'approved' => 'badge-good',
                                    'rejected' => 'badge-danger',
                                ];
                                $statusLabel = [
                                    'pending' => 'Menunggu',
                                    'approved' => 'Disetujui',
                                    'rejected' => 'Ditolak',
                                ];
                                ?>
                                <span class="badge <?= $statusClass[$u['approval_status']] ?? 'badge-good' ?>">
                                    <?= $statusLabel[$u['approval_status']] ?? $u['approval_status'] ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($u['created_at']))) ?></td>
                            <td>
                                <?php if ($u['approval_status'] === 'pending'): ?>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Yakin ingin menyetujui user ini?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                        <button type="submit" class="link-btn link-edit" title="Setujui">✓ Setujui</button>
                                    </form>
                                    <form method="post" style="display:inline; margin-left: 4px;" onsubmit="return confirm('Yakin ingin menolak user ini?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                        <input type="hidden" name="note" value="Ditolak oleh Admin/Guru BK">
                                        <button type="submit" class="link-btn link-delete" title="Tolak">✗ Tolak</button>
                                    </form>
                                <?php elseif ($u['approval_status'] === 'rejected'): ?>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Yakin ingin menyetujui user ini?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                        <button type="submit" class="link-btn link-edit" title="Setujui Ulang">✓ Setujui</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">Sudah diproses</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>