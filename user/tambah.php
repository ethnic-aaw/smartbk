<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Only Admin can access this page
if ($_SESSION['user']['role'] !== 'Admin') {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$pageTitle = 'Tambah User';
$activeMenu = 'user';

$kelasList = db_fetch('SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas ASC');
$kelasList = $kelasList ?: [];

$errors = [];
$old = ['nama' => '', 'username' => '', 'role' => 'Admin', 'kelas_id' => '', 'status' => 'Aktif', 'approval_status' => 'approved'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'nama' => trim($_POST['nama'] ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'role' => trim($_POST['role'] ?? 'Admin'),
        'kelas_id' => (int) ($_POST['kelas_id'] ?? 0) ?: null,
        'status' => trim($_POST['status'] ?? 'Aktif') === 'Nonaktif' ? 'Nonaktif' : 'Aktif',
        'approval_status' => 'approved', // Admin creates are auto-approved
    ];
    $password = (string) ($_POST['password'] ?? '');

    if ($old['nama'] === '') {
        $errors['nama'] = 'Nama lengkap wajib diisi.';
    } elseif (strlen($old['nama']) > 100) {
        $errors['nama'] = 'Nama maksimal 100 karakter.';
    }

    if ($old['username'] === '') {
        $errors['username'] = 'Username wajib diisi.';
    } else {
        $dup = db_fetch('SELECT id FROM users WHERE username = ? LIMIT 1', [$old['username']], 'row');
        if ($dup) {
            $errors['username'] = 'Username sudah digunakan.';
        } elseif ($old['role'] === 'Guru BK' && !preg_match('/^[a-zA-Z0-9._%+-]+@belajar\.id$/i', $old['username'])) {
            $errors['username'] = 'Username Guru BK harus berformat nama@belajar.id.';
        }
    }

    if ($password === '') {
        $errors['password'] = 'Password wajib diisi.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password minimal 8 karakter.';
    }

    if ($old['role'] === 'Wali Kelas' && empty($old['kelas_id'])) {
        $errors['kelas_id'] = 'Wali Kelas harus memiliki kelas yang diampu.';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $ok = db_query(
            'INSERT INTO users (nama, username, email, password_hash, role, kelas_id, status, approval_status, email_verified_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [$old['nama'], $old['username'], $old['username'], $hash, $old['role'], $old['kelas_id'], $old['status'], $old['approval_status']]
        );

        if ($ok) {
            $userId = db_last_id();
            
            // Jika Wali Kelas, update kelas.wali_kelas_id
            if ($old['role'] === 'Wali Kelas' && !empty($old['kelas_id'])) {
                db_query('UPDATE kelas SET wali_kelas_id = ? WHERE id = ?', [$userId, $old['kelas_id']]);
            }
            
            set_flash('success', 'User "' . $old['nama'] . '" berhasil ditambahkan (auto-approved).');
            redirect_to(rtrim(APP_BASE, '/') . '/user/index.php');
        }
        $errors['nama'] = 'Gagal menyimpan data ke database.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Form Tambah User</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/user/index.php" class="secondary-btn">Kembali</a>
</div>
<div class="card form-card">
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" value="<?= e($old['nama']) ?>" class="<?= isset($errors['nama']) ? 'input-invalid' : '' ?>">
                <?php if (isset($errors['nama'])): ?><span class="field-error"><?= e($errors['nama']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?= e($old['username']) ?>" placeholder="nama@belajar.id / admin" class="<?= isset($errors['username']) ? 'input-invalid' : '' ?>">
                <?php if (isset($errors['username'])): ?><span class="field-error"><?= e($errors['username']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Password (min 8 karakter)</label>
                <input type="password" name="password" class="<?= isset($errors['password']) ? 'input-invalid' : '' ?>">
                <?php if (isset($errors['password'])): ?><span class="field-error"><?= e($errors['password']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" id="role-select">
                    <?php foreach (['Admin', 'Guru BK', 'Wali Kelas', 'Siswa'] as $r): ?>
                        <option value="<?= e($r) ?>" <?= $old['role'] === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" id="kelas-group" style="<?= $old['role'] === 'Wali Kelas' ? '' : 'display:none;' ?>">
                <label>Kelas Diampu</label>
                <select name="kelas_id" class="<?= isset($errors['kelas_id']) ? 'input-invalid' : '' ?>">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($kelasList as $k): ?>
                        <option value="<?= (int) $k['id'] ?>" <?= (int) $old['kelas_id'] === (int) $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['kelas_id'])): ?><span class="field-error"><?= e($errors['kelas_id']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="Aktif" <?= $old['status'] === 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="Nonaktif" <?= $old['status'] === 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>
            <div class="form-group" style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 1rem;">
                <strong>Info:</strong> User yang dibuat Admin otomatis <strong>disetujui (approved)</strong> dan bisa login langsung.
                Email/username diset sama untuk local login. Approval hanya untuk registrasi via Google OAuth.
            </div>
        </div>
        <div class="form-actions">
            <button class="primary-btn" type="submit">Simpan</button>
            <a href="<?= rtrim(APP_BASE, '/') ?>/user/index.php" class="secondary-btn">Batal</a>
        </div>
    </form>
</div>
<script>
document.getElementById('role-select').addEventListener('change', function () {
    var group = document.getElementById('kelas-group');
    group.style.display = this.value === 'Wali Kelas' ? '' : 'none';
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>