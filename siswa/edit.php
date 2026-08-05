<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

$pageTitle = 'Edit Siswa';
$activeMenu = 'siswa';

$id = (int) ($_GET['id'] ?? 0);
$siswa = db_fetch('SELECT * FROM siswa WHERE id = ? LIMIT 1', [$id], 'row');

if (!$siswa) {
    set_flash('error', 'Data siswa tidak ditemukan.');
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/index.php');
}

// Wali Kelas hanya bisa edit siswa di kelasnya
if (is_wali_kelas()) {
    $userKelasId = get_user_kelas_id();
    if ((int) $siswa['kelas_id'] !== $userKelasId) {
        set_flash('error', 'Anda hanya bisa mengedit siswa di kelas Anda.');
        redirect_to(rtrim(APP_BASE, '/') . '/siswa/index.php');
    }
}

$kelasList = db_fetch('SELECT id, nama_kelas, tahun_ajaran FROM kelas ORDER BY nama_kelas ASC');
$kelasList = $kelasList ?: [];

$errors = [];
$old = $siswa;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'id' => $id,
        'nipd' => trim($_POST['nipd'] ?? ''),
        'nama' => trim($_POST['nama'] ?? ''),
        'jenis_kelamin' => ($_POST['jenis_kelamin'] ?? 'L') === 'P' ? 'P' : 'L',
        'kelas_id' => (int) ($_POST['kelas_id'] ?? 0),
        'tempat_lahir' => trim($_POST['tempat_lahir'] ?? ''),
        'tanggal_lahir' => trim($_POST['tanggal_lahir'] ?? ''),
        'nama_orang_tua' => trim($_POST['nama_orang_tua'] ?? ''),
        'no_hp_orang_tua' => trim($_POST['no_hp_orang_tua'] ?? ''),
        'alamat' => trim($_POST['alamat'] ?? ''),
        'status' => trim($_POST['status'] ?? 'Aktif'),
        'foto' => $siswa['foto'],
    ];

    if ($old['nipd'] === '') {
        $errors['nipd'] = 'NIPD/NIS wajib diisi.';
    } elseif (strlen($old['nipd']) > 20) {
        $errors['nipd'] = 'NIPD/NIS maksimal 20 karakter.';
    } else {
        $dup = db_fetch('SELECT id FROM siswa WHERE nipd = ? AND id <> ? LIMIT 1', [$old['nipd'], $id], 'row');
        if ($dup) {
            $errors['nipd'] = 'NIPD/NIS sudah digunakan siswa lain.';
        }
    }

    if ($old['nama'] === '') {
        $errors['nama'] = 'Nama lengkap wajib diisi.';
    } elseif (strlen($old['nama']) > 100) {
        $errors['nama'] = 'Nama maksimal 100 karakter.';
    }

    if (!in_array($old['status'], ['Aktif', 'Tidak Aktif', 'Pindah', 'Lulus'], true)) {
        $old['status'] = 'Aktif';
    }

    $fotoName = $siswa['foto'];
    $fotoErr = null;
    $replaceFoto = false;
    if (isset($_FILES['foto']) && !empty($_FILES['foto']['name'])) {
        $up = upload_foto_siswa($_FILES['foto']);
        if (!$up['ok']) {
            $fotoErr = $up['error'];
        } else {
            $replaceFoto = true;
            $fotoName = $up['name'];
        }
    }

    if (!$errors && !$fotoErr) {
        $ok = db_query(
            'UPDATE siswa SET nipd = ?, nama = ?, jenis_kelamin = ?, kelas_id = ?, tempat_lahir = ?,
                tanggal_lahir = ?, nama_orang_tua = ?, no_hp_orang_tua = ?, foto = ?, alamat = ?, status = ?
             WHERE id = ?',
            [
                $old['nipd'], $old['nama'], $old['jenis_kelamin'],
                $old['kelas_id'] ?: null, $old['tempat_lahir'] ?: null,
                $old['tanggal_lahir'] ?: null, $old['nama_orang_tua'] ?: null,
                $old['no_hp_orang_tua'] ?: null, $fotoName, $old['alamat'] ?: null,
                $old['status'], $id,
            ]
        );

        if ($ok) {
            if ($replaceFoto && $siswa['foto']) {
                @unlink(__DIR__ . '/../assets/uploads/foto_siswa/' . $siswa['foto']);
            }
            set_flash('success', 'Data siswa "' . $old['nama'] . '" berhasil diperbarui.');
            redirect_to(rtrim(APP_BASE, '/') . '/siswa/index.php');
        }

        $fotoErr = 'Gagal memperbarui data di database.';
        if ($replaceFoto && $fotoName) {
            @unlink(__DIR__ . '/../assets/uploads/foto_siswa/' . $fotoName);
        }
    }

    if ($fotoErr) {
        $errors['foto'] = $fotoErr;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Edit Data Siswa</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/index.php" class="secondary-btn">Kembali</a>
</div>
<div class="card form-card">
    <form method="post" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group">
                <label>NIPD / NIS</label>
                <input type="text" name="nipd" value="<?= e($old['nipd']) ?>" class="<?= isset($errors['nipd']) ? 'input-invalid' : '' ?>">
                <?php if (isset($errors['nipd'])): ?><span class="field-error"><?= e($errors['nipd']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" value="<?= e($old['nama']) ?>" class="<?= isset($errors['nama']) ? 'input-invalid' : '' ?>">
                <?php if (isset($errors['nama'])): ?><span class="field-error"><?= e($errors['nama']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Jenis Kelamin</label>
                <select name="jenis_kelamin">
                    <option value="L" <?= $old['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="P" <?= $old['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                </select>
            </div>
            <div class="form-group">
                <label>Kelas</label>
                <select name="kelas_id">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($kelasList as $k): ?>
                        <option value="<?= (int) $k['id'] ?>" <?= (int) $old['kelas_id'] === (int) $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kelas']) ?> (<?= e($k['tahun_ajaran']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tempat Lahir</label>
                <input type="text" name="tempat_lahir" value="<?= e($old['tempat_lahir']) ?>">
            </div>
            <div class="form-group">
                <label>Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" value="<?= e($old['tanggal_lahir']) ?>">
            </div>
            <div class="form-group">
                <label>Nama Orang Tua</label>
                <input type="text" name="nama_orang_tua" value="<?= e($old['nama_orang_tua']) ?>">
            </div>
            <div class="form-group">
                <label>No. HP Orang Tua</label>
                <input type="text" name="no_hp_orang_tua" value="<?= e($old['no_hp_orang_tua']) ?>">
            </div>
            <div class="form-group">
                <label>Foto (JPG/PNG, max 500KB)</label>
                <input type="file" name="foto" accept="image/jpeg,image/png" class="<?= isset($errors['foto']) ? 'input-invalid' : '' ?>">
                <?php if (!empty($old['foto'])): ?>
                    <span style="font-size:12px;color:var(--text-muted);">Foto saat ini: <?= e($old['foto']) ?>. Kosongkan untuk mempertahankan.</span>
                <?php endif; ?>
                <?php if (isset($errors['foto'])): ?><span class="field-error"><?= e($errors['foto']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <?php foreach (['Aktif', 'Tidak Aktif', 'Pindah', 'Lulus'] as $st): ?>
                        <option value="<?= e($st) ?>" <?= $old['status'] === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Alamat</label>
                <textarea name="alamat" rows="4"><?= e($old['alamat']) ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button class="primary-btn" type="submit">Simpan Perubahan</button>
            <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/index.php" class="secondary-btn">Batal</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
