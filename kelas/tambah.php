<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Only Admin can access this page
if ($_SESSION['user']['role'] !== 'Admin') {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$pageTitle = 'Tambah Kelas';
$activeMenu = 'kelas';

$waliList = db_fetch('SELECT id, nama FROM users WHERE role = ? ORDER BY nama ASC', ['Wali Kelas']);
$waliList = $waliList ?: [];

$errors = [];
$old = ['nama_kelas' => '', 'tingkat' => 'X', 'wali_kelas_id' => '', 'tahun_ajaran' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'nama_kelas' => trim($_POST['nama_kelas'] ?? ''),
        'tingkat' => trim($_POST['tingkat'] ?? 'X'),
        'wali_kelas_id' => (int) ($_POST['wali_kelas_id'] ?? 0) ?: null,
        'tahun_ajaran' => trim($_POST['tahun_ajaran'] ?? ''),
    ];

    if ($old['nama_kelas'] === '') {
        $errors['nama_kelas'] = 'Nama kelas wajib diisi.';
    } elseif (strlen($old['nama_kelas']) > 100) {
        $errors['nama_kelas'] = 'Nama kelas maksimal 100 karakter.';
    }
    if ($old['tahun_ajaran'] === '') {
        $errors['tahun_ajaran'] = 'Tahun ajaran wajib diisi.';
    }

    if (!$errors) {
        $ok = db_query(
            'INSERT INTO kelas (nama_kelas, tingkat, wali_kelas_id, tahun_ajaran) VALUES (?, ?, ?, ?)',
            [$old['nama_kelas'], $old['tingkat'], $old['wali_kelas_id'], $old['tahun_ajaran']]
        );

        if ($ok) {
            set_flash('success', 'Kelas "' . $old['nama_kelas'] . '" berhasil ditambahkan.');
            redirect_to(rtrim(APP_BASE, '/') . '/kelas/index.php');
        }
        $errors['nama_kelas'] = 'Gagal menyimpan data ke database.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Form Tambah Kelas</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/kelas/index.php" class="secondary-btn">Kembali</a>
</div>
<div class="card form-card">
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-group">
                <label>Nama Kelas</label>
                <input type="text" name="nama_kelas" value="<?= e($old['nama_kelas']) ?>" placeholder="Contoh: X IPA 1" class="<?= isset($errors['nama_kelas']) ? 'input-invalid' : '' ?>">
                <?php if (isset($errors['nama_kelas'])): ?><span class="field-error"><?= e($errors['nama_kelas']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Tahun Ajaran</label>
                <input type="text" name="tahun_ajaran" value="<?= e($old['tahun_ajaran']) ?>" placeholder="2024/2025" class="<?= isset($errors['tahun_ajaran']) ? 'input-invalid' : '' ?>">
                <?php if (isset($errors['tahun_ajaran'])): ?><span class="field-error"><?= e($errors['tahun_ajaran']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Tingkat</label>
                <select name="tingkat">
                    <?php foreach (['X', 'XI', 'XII', 'VII', 'VIII', 'IX'] as $t): ?>
                        <option value="<?= e($t) ?>" <?= $old['tingkat'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Wali Kelas</label>
                <select name="wali_kelas_id">
                    <option value="">-- Pilih --</option>
                    <?php foreach ($waliList as $u): ?>
                        <option value="<?= (int) $u['id'] ?>" <?= (int) $old['wali_kelas_id'] === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-actions">
            <button class="primary-btn" type="submit">Simpan</button>
            <a href="<?= rtrim(APP_BASE, '/') ?>/kelas/index.php" class="secondary-btn">Batal</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>