<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

$pageTitle = 'Edit Pelanggaran';
$activeMenu = 'pelanggaran_riwayat';

// Wali Kelas tidak boleh edit pelanggaran
if (is_wali_kelas()) {
    set_flash('error', 'Anda tidak memiliki akses untuk mengedit pelanggaran.');
    redirect_to(rtrim(APP_BASE, '/') . '/pelanggaran/riwayat.php');
}

$id = (int) ($_GET['id'] ?? 0);
$rec = db_fetch('SELECT * FROM pelanggaran_siswa WHERE id = ? LIMIT 1', [$id], 'row');

if (!$rec) {
    set_flash('error', 'Catatan pelanggaran tidak ditemukan.');
    redirect_to(rtrim(APP_BASE, '/') . '/pelanggaran/riwayat.php');
}

$tahunAjaran = current_tahun_ajaran();
$siswaList = db_fetch(
    'SELECT s.id, s.nipd, s.nama, k.nama_kelas
     FROM siswa s
     JOIN kelas k ON k.id = s.kelas_id
     WHERE k.tahun_ajaran = ?
     ORDER BY s.nama ASC',
    [$tahunAjaran]
);
$siswaList = $siswaList ?: [];

$jenisList = db_fetch('SELECT id, kode, nama, bobot_poin FROM jenis_pelanggaran ORDER BY kode ASC');
$jenisList = $jenisList ?: [];

$errors = [];
$old = $rec;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'id' => $id,
        'siswa_id' => (int) ($_POST['siswa_id'] ?? 0),
        'jenis_pelanggaran_id' => (int) ($_POST['jenis_pelanggaran_id'] ?? 0),
        'tanggal' => trim($_POST['tanggal'] ?? ''),
        'lokasi' => trim($_POST['lokasi'] ?? ''),
        'keterangan' => trim($_POST['keterangan'] ?? ''),
        'tindakan' => trim($_POST['tindakan'] ?? ''),
    ];

    if (empty($old['siswa_id'])) {
        $errors['siswa_id'] = 'Pilih siswa terlebih dahulu.';
    }
    if (empty($old['jenis_pelanggaran_id'])) {
        $errors['jenis_pelanggaran_id'] = 'Pilih jenis pelanggaran.';
    }
    if ($old['tanggal'] === '') {
        $errors['tanggal'] = 'Tanggal kejadian wajib diisi.';
    }

    // Upload barang bukti baru (opsional; jika diisi, mengganti bukti lama)
    $bukti = null;
    $buktiErr = null;
    if (isset($_FILES['bukti']) && !empty($_FILES['bukti']['name'])) {
        $up = upload_bukti_pelanggaran($_FILES['bukti']);
        if (!$up['ok']) {
            $buktiErr = $up['error'];
        } else {
            $bukti = $up;
        }
    }

    if (!$errors && !$buktiErr) {
        $ok = null;
        if ($bukti) {
            $ok = db_query(
                'UPDATE pelanggaran_siswa SET siswa_id = ?, jenis_pelanggaran_id = ?, tanggal = ?, lokasi = ?, keterangan = ?, tindakan = ?, bukti_file = ?, bukti_original = ?, bukti_type = ?, bukti_size = ? WHERE id = ?',
                [
                    $old['siswa_id'], $old['jenis_pelanggaran_id'], $old['tanggal'],
                    $old['lokasi'] ?: null, $old['keterangan'] ?: null, $old['tindakan'] ?: null,
                    $bukti['file'], $bukti['original'], $bukti['type'], $bukti['size'],
                    $id,
                ]
            );
        } else {
            $ok = db_query(
                'UPDATE pelanggaran_siswa SET siswa_id = ?, jenis_pelanggaran_id = ?, tanggal = ?, lokasi = ?, keterangan = ?, tindakan = ? WHERE id = ?',
                [
                    $old['siswa_id'], $old['jenis_pelanggaran_id'], $old['tanggal'],
                    $old['lokasi'] ?: null, $old['keterangan'] ?: null, $old['tindakan'] ?: null,
                    $id,
                ]
            );
        }

        if ($ok) {
            if ($bukti && $rec['bukti_file'] && $rec['bukti_file'] !== $bukti['file']) {
                hapus_bukti_pelanggaran($rec['bukti_file']);
            }
            set_flash('success', 'Catatan pelanggaran berhasil diperbarui.');
            redirect_to(rtrim(APP_BASE, '/') . '/pelanggaran/riwayat.php');
        }
        $errors['siswa_id'] = 'Gagal memperbarui data di database.';
        if ($bukti) {
            hapus_bukti_pelanggaran($bukti['file']);
        }
    }

    if ($buktiErr) {
        $errors['bukti'] = $buktiErr;
    }

    if ($bukti && $errors) {
        hapus_bukti_pelanggaran($bukti['file']);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Edit Catatan Pelanggaran</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/riwayat.php" class="secondary-btn">Kembali</a>
</div>
<div class="card form-card">
    <form method="post" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group">
                <label>Siswa</label>
                <select name="siswa_id" class="<?= isset($errors['siswa_id']) ? 'input-invalid' : '' ?>">
                    <option value="">-- Pilih Siswa --</option>
                    <?php foreach ($siswaList as $s): ?>
                        <option value="<?= (int) $s['id'] ?>" <?= (int) $old['siswa_id'] === (int) $s['id'] ? 'selected' : '' ?>>
                            <?= e($s['nama']) ?> (<?= e($s['nipd']) ?>)<?= $s['nama_kelas'] ? ' — ' . e($s['nama_kelas']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['siswa_id'])): ?><span class="field-error"><?= e($errors['siswa_id']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Jenis Pelanggaran</label>
                <select name="jenis_pelanggaran_id" id="jenis-select" class="<?= isset($errors['jenis_pelanggaran_id']) ? 'input-invalid' : '' ?>">
                    <option value="">-- Pilih Jenis --</option>
                    <?php foreach ($jenisList as $j): ?>
                        <option value="<?= (int) $j['id'] ?>" data-poin="<?= (int) $j['bobot_poin'] ?>" <?= (int) $old['jenis_pelanggaran_id'] === (int) $j['id'] ? 'selected' : '' ?>>
                            <?= e($j['kode']) ?> — <?= e($j['nama']) ?> (<?= (int) $j['bobot_poin'] ?> poin)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['jenis_pelanggaran_id'])): ?><span class="field-error"><?= e($errors['jenis_pelanggaran_id']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Tanggal Kejadian</label>
                <input type="date" name="tanggal" value="<?= e($old['tanggal']) ?>" class="<?= isset($errors['tanggal']) ? 'input-invalid' : '' ?>">
                <?php if (isset($errors['tanggal'])): ?><span class="field-error"><?= e($errors['tanggal']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Poin Otomatis</label>
                <input type="text" id="poin-display" value="" readonly>
            </div>
            <div class="form-group">
                <label>Lokasi Kejadian</label>
                <input type="text" name="lokasi" value="<?= e($old['lokasi']) ?>">
            </div>
            <div class="form-group">
                <label>Pelapor</label>
                <input type="text" value="Tetap tercatat dari waktu pencatatan" readonly>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Keterangan</label>
                <textarea name="keterangan" rows="4"><?= e($old['keterangan']) ?></textarea>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Tindakan Diambil</label>
                <textarea name="tindakan" rows="4"><?= e($old['tindakan']) ?></textarea>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Barang Bukti (foto / dokumen)</label>
                <?php if (!empty($old['bukti_file'])): ?>
                    <div style="margin-bottom:8px;font-size:13px;">
                        Bukti saat ini:
                        <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/download.php?id=<?= (int) $id ?>" style="color:var(--primary);font-weight:600;">📎 <?= e($old['bukti_original']) ?></a>
                    </div>
                <?php endif; ?>
                <input type="file" name="bukti" accept=".jpg,.jpeg,.png,.webp,.pdf" class="<?= isset($errors['bukti']) ? 'input-invalid' : '' ?>">
                <small style="color: var(--text-muted);">Kosongkan jika tidak mengganti. JPG, PNG, atau PDF - maksimal 2MB - 1 file</small>
                <?php if (isset($errors['bukti'])): ?><span class="field-error"><?= e($errors['bukti']) ?></span><?php endif; ?>
            </div>
        </div>
        <div class="form-actions">
            <button class="primary-btn" type="submit">Simpan Perubahan</button>
            <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/riwayat.php" class="secondary-btn">Batal</a>
        </div>
    </form>
</div>
<script>
document.getElementById('jenis-select').addEventListener('change', function () {
    var opt = this.options[this.selectedIndex];
    document.getElementById('poin-display').value = opt && opt.dataset.poin ? opt.dataset.poin : '';
});
document.getElementById('jenis-select').dispatchEvent(new Event('change'));
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>