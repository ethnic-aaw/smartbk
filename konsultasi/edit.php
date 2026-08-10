<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

// Only Admin & Guru BK
if (!can_see_all_data()) {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$pageTitle = 'Edit Konseling';
$activeMenu = 'konsultasi';

$id = (int) ($_GET['id'] ?? 0);
$kons = db_fetch('SELECT * FROM konsultasi_siswa WHERE id = ? LIMIT 1', [$id], 'row');

if (!$kons) {
    set_flash('error', 'Data konseling tidak ditemukan.');
    redirect_to(rtrim(APP_BASE, '/') . '/konsultasi/index.php');
}

$errors = [];
$old = $kons;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'id' => $id,
        'siswa_id' => (int) $kons['siswa_id'],
        'tanggal' => trim($_POST['tanggal'] ?? ''),
        'permasalahan' => trim($_POST['permasalahan'] ?? ''),
        'tindak_lanjut' => trim($_POST['tindak_lanjut'] ?? ''),
    ];

    if ($old['tanggal'] === '') {
        $errors['tanggal'] = 'Tanggal wajib diisi.';
    }
    if ($old['permasalahan'] === '') {
        $errors['permasalahan'] = 'Permasalahan wajib diisi.';
    }

    $lampiran = null;
    $lampiranErr = null;
    if (isset($_FILES['lampiran']) && !empty($_FILES['lampiran']['name'])) {
        $up = upload_lampiran_konsultasi($_FILES['lampiran']);
        if (!$up['ok']) {
            $lampiranErr = $up['error'];
        } else {
            $lampiran = $up;
        }
    }

    if (!$errors && !$lampiranErr) {
        $ok = db_query(
            'UPDATE konsultasi_siswa SET tanggal = ?, permasalahan = ?, tindak_lanjut = ? WHERE id = ?',
            [
                $old['tanggal'],
                $old['permasalahan'],
                $old['tindak_lanjut'] !== '' ? $old['tindak_lanjut'] : null,
                $id,
            ]
        );

        if ($ok) {
            // Ganti lampiran: hapus file lama, simpan file baru
            if ($lampiran) {
                if (!empty($kons['lampiran_file'])) {
                    hapus_lampiran_konsultasi($kons['lampiran_file']);
                }
                db_query(
                    'UPDATE konsultasi_siswa SET lampiran_file = ?, lampiran_original = ?, lampiran_type = ?, lampiran_size = ? WHERE id = ?',
                    [$lampiran['file'], $lampiran['original'], $lampiran['type'], $lampiran['size'], $id]
                );
            }

            set_flash('success', 'Konseling berhasil diperbarui.');
            redirect_to(rtrim(APP_BASE, '/') . '/siswa/detail.php?id=' . (int) $kons['siswa_id']);
        }
        $errors['permasalahan'] = 'Gagal memperbarui data di database.';
        if ($lampiran) {
            hapus_lampiran_konsultasi($lampiran['file']);
        }
    }

    if ($lampiranErr) {
        $errors['lampiran'] = $lampiranErr;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Edit Konseling</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/konsultasi/index.php" class="secondary-btn">Kembali</a>
</div>
<div class="card form-card">
    <form method="post" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group">
                <label>Tanggal</label>
                <input type="date" name="tanggal" value="<?= e($old['tanggal']) ?>" class="<?= isset($errors['tanggal']) ? 'input-invalid' : '' ?>">
                <?php if (isset($errors['tanggal'])): ?><span class="field-error"><?= e($errors['tanggal']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Bukti Dukung Saat Ini</label>
                <?php if (!empty($kons['lampiran_file'])): ?>
                    <a href="<?= rtrim(APP_BASE, '/') ?>/konsultasi/download.php?id=<?= (int) $kons['id'] ?>" class="secondary-btn" style="display:inline-block;">
                        📄 <?= e($kons['lampiran_original']) ?>
                    </a>
                    <a href="<?= rtrim(APP_BASE, '/') ?>/konsultasi/hapus_lampiran.php?id=<?= (int) $kons['id'] ?>" class="link-btn link-delete" style="display:inline-block;"
                       onclick="return confirm('Hapus lampiran ini?');">Hapus Lampiran</a>
                <?php else: ?>
                    <span style="color: var(--text-muted);">Belum ada lampiran</span>
                <?php endif; ?>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Permasalahan</label>
                <textarea name="permasalahan" rows="4" class="<?= isset($errors['permasalahan']) ? 'input-invalid' : '' ?>"><?= e($old['permasalahan']) ?></textarea>
                <?php if (isset($errors['permasalahan'])): ?><span class="field-error"><?= e($errors['permasalahan']) ?></span><?php endif; ?>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Tindak Lanjut</label>
                <textarea name="tindak_lanjut" rows="4"><?= e($old['tindak_lanjut']) ?></textarea>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Ganti Bukti Dukung (kosongkan jika tetap)</label>
                <input type="file" name="lampiran" accept=".jpg,.jpeg,.png,.webp,.pdf" class="<?= isset($errors['lampiran']) ? 'input-invalid' : '' ?>">
                <small style="color: var(--text-muted);">JPG, PNG, atau PDF - maksimal 2MB - file lama akan diganti</small>
                <?php if (isset($errors['lampiran'])): ?><span class="field-error"><?= e($errors['lampiran']) ?></span><?php endif; ?>
            </div>
        </div>
        <div class="form-actions">
            <button class="primary-btn" type="submit">Simpan Perubahan</button>
            <a href="<?= rtrim(APP_BASE, '/') ?>/konsultasi/index.php" class="secondary-btn">Batal</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
