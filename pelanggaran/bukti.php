<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

// Hanya Admin & Guru BK
if (!can_see_all_data()) {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/pelanggaran/riwayat.php');
}

$id = (int) ($_GET['id'] ?? 0);
$rec = db_fetch(
    'SELECT p.*, s.nama AS siswa_nama, s.nipd, k.nama_kelas, j.nama AS jenis_nama, j.bobot_poin, u.nama AS pelapor
     FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     LEFT JOIN kelas k ON k.id = s.kelas_id
     JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
     LEFT JOIN users u ON u.id = p.pelapor_id
     WHERE p.id = ? LIMIT 1',
    [$id],
    'row'
);

if (!$rec) {
    set_flash('error', 'Catatan pelanggaran tidak ditemukan.');
    redirect_to(rtrim(APP_BASE, '/') . '/pelanggaran/riwayat.php');
}

$pageTitle = 'Barang Bukti Pelanggaran';
$activeMenu = 'pelanggaran_riwayat';
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['hapus']) && !empty($rec['bukti_file'])) {
        hapus_bukti_pelanggaran($rec['bukti_file']);
        db_query(
            'UPDATE pelanggaran_siswa SET bukti_file = NULL, bukti_original = NULL, bukti_type = NULL, bukti_size = NULL WHERE id = ?',
            [$id]
        );
        set_flash('success', 'Barang bukti berhasil dihapus.');
        redirect_to(rtrim(APP_BASE, '/') . '/pelanggaran/bukti.php?id=' . $id);
    }

    if (isset($_FILES['bukti']) && !empty($_FILES['bukti']['name'])) {
        $up = upload_bukti_pelanggaran($_FILES['bukti']);
        if (!$up['ok']) {
            $error = $up['error'];
        } else {
            if ($rec['bukti_file'] && $rec['bukti_file'] !== $up['file']) {
                hapus_bukti_pelanggaran($rec['bukti_file']);
            }
            $ok = db_query(
                'UPDATE pelanggaran_siswa SET bukti_file = ?, bukti_original = ?, bukti_type = ?, bukti_size = ? WHERE id = ?',
                [$up['file'], $up['original'], $up['type'], $up['size'], $id]
            );
            if ($ok) {
                set_flash('success', 'Barang bukti berhasil diunggah.');
                redirect_to(rtrim(APP_BASE, '/') . '/pelanggaran/bukti.php?id=' . $id);
            }
            $error = 'Gagal menyimpan data ke database.';
            if ($rec['bukti_file'] !== $up['file']) {
                hapus_bukti_pelanggaran($up['file']);
            }
        }
    } else {
        $error = 'Pilih file bukti terlebih dahulu.';
    }
}

$buktiUrl = '';
if (!empty($rec['bukti_file']) && file_exists(__DIR__ . '/../assets/uploads/bukti_pelanggaran/' . $rec['bukti_file'])) {
    $buktiUrl = rtrim(APP_BASE, '/') . '/assets/uploads/bukti_pelanggaran/' . rawurlencode($rec['bukti_file']);
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Barang Bukti Pelanggaran</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/riwayat.php" class="secondary-btn">Riwayat Pelanggaran</a>
</div>

<div class="card form-card" style="max-width: 640px;">
    <div class="form-group">
        <label>Catatan Pelanggaran</label>
        <p style="margin:0;line-height:1.7;">
            <strong><?= e($rec['siswa_nama']) ?></strong> (NIPD: <?= e($rec['nipd']) ?>) — Kelas <?= e($rec['nama_kelas'] ?? '-') ?><br>
            <?= e($rec['tanggal']) ?> &middot; <?= e($rec['jenis_nama']) ?> (<?= (int) $rec['bobot_poin'] ?> poin)
        </p>
    </div>

    <hr style="border:none;border-top:1px solid var(--border);margin:18px 0;">

    <?php if ($error): ?>
        <div class="alert error flash-alert" style="margin-bottom:14px;"><span><?= e($error) ?></span></div>
    <?php endif; ?>

    <?php if ($buktiUrl !== ''): ?>
        <label>Bukti Saat Ini</label>
        <?php if (strpos($rec['bukti_type'], 'image/') === 0): ?>
            <div style="margin:10px 0;text-align:center;">
                <img src="<?= $buktiUrl ?>" alt="Bukti <?= e($rec['bukti_original']) ?>" style="max-width:100%;max-height:320px;border-radius:10px;border:1px solid var(--border);">
            </div>
        <?php else: ?>
            <div style="margin:10px 0;padding:14px;background:var(--background);border:1px solid var(--border);border-radius:10px;text-align:center;color:var(--text-muted);">
                📄 Dokumen (PDF)
            </div>
        <?php endif; ?>
        <div style="font-size:13px;color:var(--text-muted);margin-bottom:8px;">
            <?= e($rec['bukti_original']) ?> &middot; <?= number_format((int) $rec['bukti_size'] / 1024, 1) ?> KB
        </div>
        <div class="row-actions" style="margin-bottom:18px;">
            <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/download.php?id=<?= (int) $rec['id'] ?>" class="link-btn link-edit">Download</a>
            <form method="post" action="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/bukti.php?id=<?= (int) $rec['id'] ?>" data-confirm="Hapus barang bukti ini?">
                <input type="hidden" name="hapus" value="1">
                <button type="submit" class="link-btn link-delete">Hapus Bukti</button>
            </form>
        </div>
    <?php else: ?>
        <p style="color:var(--text-muted);">Belum ada barang bukti untuk pelanggaran ini.</p>
    <?php endif; ?>

    <label>Unggah Barang Bukti (foto / dokumen)</label>
    <form method="post" enctype="multipart/form-data" action="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/bukti.php?id=<?= (int) $rec['id'] ?>" style="margin-top:8px;">
        <div class="form-group" style="margin-bottom:0;">
            <input type="file" name="bukti" accept=".jpg,.jpeg,.png,.webp,.pdf">
            <small style="color: var(--text-muted);">JPG, PNG, atau PDF - maksimal 2MB - 1 file. File lama otomatis diganti.</small>
        </div>
        <div class="form-actions" style="padding-top:12px;">
            <button class="primary-btn" type="submit">Upload Bukti</button>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>