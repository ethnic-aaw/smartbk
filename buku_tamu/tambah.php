<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Only Admin & Guru BK
if (!can_see_all_data()) {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$pageTitle = 'Tambah Tamu';
$activeMenu = 'buku_tamu';

$errors = [];
$old = [
    'tanggal' => date('Y-m-d'),
    'nama_tamu' => '',
    'keperluan' => '',
    'tindak_lanjut' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'tanggal' => trim($_POST['tanggal'] ?? ''),
        'nama_tamu' => trim($_POST['nama_tamu'] ?? ''),
        'keperluan' => trim($_POST['keperluan'] ?? ''),
        'tindak_lanjut' => trim($_POST['tindak_lanjut'] ?? ''),
    ];

    if ($old['tanggal'] === '') {
        $errors['tanggal'] = 'Tanggal wajib diisi.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $old['tanggal'])) {
        $errors['tanggal'] = 'Format tanggal tidak valid.';
    }

    if ($old['nama_tamu'] === '') {
        $errors['nama_tamu'] = 'Nama tamu wajib diisi.';
    } elseif (strlen($old['nama_tamu']) > 150) {
        $errors['nama_tamu'] = 'Nama tamu maksimal 150 karakter.';
    }

    if ($old['keperluan'] === '') {
        $errors['keperluan'] = 'Keperluan wajib diisi.';
    }

    if (!$errors) {
        $ok = db_query(
            'INSERT INTO buku_tamu (tanggal, nama_tamu, keperluan, tindak_lanjut, pencatat_id) VALUES (?, ?, ?, ?, ?)',
            [
                $old['tanggal'],
                $old['nama_tamu'],
                $old['keperluan'],
                $old['tindak_lanjut'] !== '' ? $old['tindak_lanjut'] : null,
                (int) ($_SESSION['user']['id'] ?? 0) ?: null,
            ]
        );

        if ($ok) {
            set_flash('success', 'Tamu "' . $old['nama_tamu'] . '" berhasil dicatat.');
            redirect_to(rtrim(APP_BASE, '/') . '/buku_tamu/index.php');
        }
        $errors['nama_tamu'] = 'Gagal menyimpan data ke database.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Form Catat Tamu</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/buku_tamu/index.php" class="secondary-btn">Kembali</a>
</div>
<div class="card form-card">
    <form method="post">
        <div class="form-grid">
            <div class="form-group">
                <label>Hari &amp; Tanggal</label>
                <input type="date" name="tanggal" value="<?= e($old['tanggal']) ?>" class="<?= isset($errors['tanggal']) ? 'input-invalid' : '' ?>">
                <?php if (isset($errors['tanggal'])): ?><span class="field-error"><?= e($errors['tanggal']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Nama Tamu</label>
                <input type="text" name="nama_tamu" value="<?= e($old['nama_tamu']) ?>" class="<?= isset($errors['nama_tamu']) ? 'input-invalid' : '' ?>">
                <?php if (isset($errors['nama_tamu'])): ?><span class="field-error"><?= e($errors['nama_tamu']) ?></span><?php endif; ?>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Keperluan</label>
                <textarea name="keperluan" rows="4" class="<?= isset($errors['keperluan']) ? 'input-invalid' : '' ?>"><?= e($old['keperluan']) ?></textarea>
                <?php if (isset($errors['keperluan'])): ?><span class="field-error"><?= e($errors['keperluan']) ?></span><?php endif; ?>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Tindak Lanjut dari Guru BK</label>
                <textarea name="tindak_lanjut" rows="4"><?= e($old['tindak_lanjut']) ?></textarea>
            </div>
            <div class="form-group">
                <label>Pencatat</label>
                <input type="text" value="<?= e($_SESSION['user']['name'] ?? '') ?>" readonly>
            </div>
        </div>
        <div class="form-actions">
            <button class="primary-btn" type="submit">Simpan</button>
            <a href="<?= rtrim(APP_BASE, '/') ?>/buku_tamu/index.php" class="secondary-btn">Batal</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
