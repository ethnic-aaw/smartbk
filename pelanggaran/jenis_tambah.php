<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../src/Validators.php';

use SmartBK\Validators;

// Admin + Guru BK dapat mengelola jenis pelanggaran; Wali Kelas read-only.
if (!can_see_all_data()) {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$pageTitle = 'Tambah Pelanggaran';
$activeMenu = 'pelanggaran_master';

$errors = [];
$old = ['kode' => '', 'nama' => '', 'komponen' => 'Kehadiran', 'kategori' => 'Kedisiplinan', 'bobot_poin' => '', 'deskripsi' => '', 'konsekuensi' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'kode' => trim($_POST['kode'] ?? ''),
        'nama' => trim($_POST['nama'] ?? ''),
        'komponen' => trim($_POST['komponen'] ?? ''),
        'kategori' => trim($_POST['kategori'] ?? 'Kedisiplinan'),
        'bobot_poin' => trim($_POST['bobot_poin'] ?? ''),
        'deskripsi' => trim($_POST['deskripsi'] ?? ''),
        'konsekuensi' => trim($_POST['konsekuensi'] ?? ''),
    ];

    // Coerce invalid enum values to defaults (page allows silent fallback).
    if (!in_array($old['komponen'], Validators::KOMPONEN_PELANGGARAN, true)) {
        $old['komponen'] = 'Kehadiran';
    }
    if (!in_array($old['kategori'], Validators::KATEGORI_PELANGGARAN, true)) {
        $old['kategori'] = 'Kedisiplinan';
    }

    // Field-level validation via central validator (uniqueness checked below).
    $errors = Validators::validateJenisPelanggaran($old);

    // Kode uniqueness — enforced by DB UNIQUE constraint; check here for a friendly message.
    if (empty($errors['kode'])) {
        $dup = db_fetch('SELECT id FROM jenis_pelanggaran WHERE kode = ? LIMIT 1', [$old['kode']], 'row');
        if ($dup) {
            $errors['kode'] = 'Kode sudah digunakan.';
        }
    }

    if (!$errors) {
        $poin = (int) $old['bobot_poin'];
        $ok = db_query(
            'INSERT INTO jenis_pelanggaran (kode, nama, komponen, kategori, bobot_poin, deskripsi, konsekuensi) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$old['kode'], $old['nama'], $old['komponen'], $old['kategori'], $poin, $old['deskripsi'] ?: null, $old['konsekuensi'] ?: null]
        );

        if ($ok) {
            set_flash('success', 'Jenis pelanggaran "' . $old['nama'] . '" berhasil ditambahkan.');
            redirect_to(rtrim(APP_BASE, '/') . '/pelanggaran/master.php');
        }
        $errors['nama'] = 'Gagal menyimpan data ke database.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Form Tambah Pelanggaran</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/master.php" class="secondary-btn">Kembali</a>
</div>
<div class="card form-card">
    <form method="post">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-group">
                <label>Kode</label>
                <input type="text" name="kode" value="<?= e($old['kode']) ?>" placeholder="PLG-001" class="<?= isset($errors['kode']) ? 'input-invalid' : '' ?>">
                <?php if (isset($errors['kode'])): ?><span class="field-error"><?= e($errors['kode']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Bobot Poin (1–100)</label>
                <input type="number" name="bobot_poin" value="<?= e($old['bobot_poin']) ?>" min="1" max="100" class="<?= isset($errors['bobot_poin']) ? 'input-invalid' : '' ?>">
                <?php if (isset($errors['bobot_poin'])): ?><span class="field-error"><?= e($errors['bobot_poin']) ?></span><?php endif; ?>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Nama Pelanggaran</label>
                <input type="text" name="nama" value="<?= e($old['nama']) ?>" class="<?= isset($errors['nama']) ? 'input-invalid' : '' ?>">
                <?php if (isset($errors['nama'])): ?><span class="field-error"><?= e($errors['nama']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Komponen</label>
                <select name="komponen">
                    <?php foreach (Validators::KOMPONEN_PELANGGARAN as $komp): ?>
                        <option value="<?= e($komp) ?>" <?= $old['komponen'] === $komp ? 'selected' : '' ?>><?= e($komp) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Kategori</label>
                <select name="kategori">
                    <?php foreach (Validators::KATEGORI_PELANGGARAN as $cat): ?>
                        <option value="<?= e($cat) ?>" <?= $old['kategori'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="deskripsi" rows="3"><?= e($old['deskripsi']) ?></textarea>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Konsekuensi</label>
                <textarea name="konsekuensi" rows="3"><?= e($old['konsekuensi']) ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button class="primary-btn" type="submit">Simpan</button>
            <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/master.php" class="secondary-btn">Batal</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>