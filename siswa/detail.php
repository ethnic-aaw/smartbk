<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Detail Siswa';
$activeMenu = 'siswa';

$id = (int) ($_GET['id'] ?? 0);
$siswa = db_fetch(
    'SELECT s.*, k.nama_kelas
     FROM siswa s
     LEFT JOIN kelas k ON k.id = s.kelas_id
     WHERE s.id = ? LIMIT 1',
    [$id],
    'row'
);

if (!$siswa) {
    set_flash('error', 'Data siswa tidak ditemukan.');
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/index.php');
}

// Wali Kelas hanya bisa lihat siswa di kelasnya
if (is_wali_kelas()) {
    $userKelasId = get_user_kelas_id();
    if ((int) $siswa['kelas_id'] !== $userKelasId) {
        set_flash('error', 'Anda hanya bisa melihat siswa di kelas Anda.');
        redirect_to(rtrim(APP_BASE, '/') . '/siswa/index.php');
    }
}

$riwayat = db_fetch(
    'SELECT p.id, p.tanggal, p.lokasi, p.keterangan, p.tindakan, j.nama AS jenis_nama, j.bobot_poin,
            u.nama AS pelapor
     FROM pelanggaran_siswa p
     JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
     LEFT JOIN users u ON u.id = p.pelapor_id
     WHERE p.siswa_id = ?
     ORDER BY p.tanggal DESC, p.id DESC',
    [$id]
);
$riwayat = $riwayat ?: [];

$totalPoin = 0;
foreach ($riwayat as $r) {
    $totalPoin += (int) $r['bobot_poin'];
}

$fotoUrl = '';
if (!empty($siswa['foto']) && file_exists(__DIR__ . '/../assets/uploads/foto_siswa/' . $siswa['foto'])) {
    $fotoUrl = rtrim(APP_BASE, '/') . '/assets/uploads/foto_siswa/' . $siswa['foto'];
}

// Riwayat konsultasi (khusus Admin & Guru BK)
$konsultasi = [];
if (can_see_all_data()) {
    $konsultasi = db_fetch(
        'SELECT k.*, u.nama AS konselor
         FROM konsultasi_siswa k
         LEFT JOIN users u ON u.id = k.konselor_id
         WHERE k.siswa_id = ?
         ORDER BY k.tanggal DESC, k.id DESC',
        [$id]
    );
    $konsultasi = $konsultasi ?: [];
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3><?= e($siswa['nama']) ?></h3>
    <div class="row-actions">
        <?php if (can_see_all_data()): ?>
            <a href="<?= rtrim(APP_BASE, '/') ?>/konsultasi/tambah.php?siswa_id=<?= (int) $siswa['id'] ?>" class="primary-btn">+ Catat Konsultasi</a>
            <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/cetak_konsultasi.php?id=<?= (int) $siswa['id'] ?>" class="secondary-btn" target="_blank">🖨 Cetak PDF</a>
        <?php endif; ?>
        <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/tambah.php?siswa_id=<?= (int) $siswa['id'] ?>" class="secondary-btn">+ Catat Pelanggaran</a>
        <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/edit.php?id=<?= (int) $siswa['id'] ?>" class="secondary-btn">Edit</a>
    </div>
</div>

<div class="grid" style="grid-template-columns: 0.4fr 1.6fr; margin-bottom: 16px;">
    <div class="card form-card" style="text-align:center;">
        <?php if ($fotoUrl): ?>
            <img src="<?= e($fotoUrl) ?>" alt="Foto <?= e($siswa['nama']) ?>" style="width:120px;height:120px;border-radius:12px;object-fit:cover;">
        <?php else: ?>
            <div class="avatar" style="width:120px;height:120px;font-size:44px;margin:0 auto;"><?= strtoupper(substr($siswa['nama'], 0, 1)) ?></div>
        <?php endif; ?>
        <h3 style="margin:12px 0 2px;"><?= e($siswa['nama']) ?></h3>
        <p style="margin:0;color:var(--text-muted);font-size:13px;"><?= e($siswa['nipd']) ?></p>
    </div>
    <div class="card form-card">
        <div class="form-grid">
            <div class="form-group"><label>Kelas</label><div><?= e($siswa['nama_kelas'] ?? '-') ?></div></div>
            <div class="form-group"><label>Jenis Kelamin</label><div><?= $siswa['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></div></div>
            <div class="form-group"><label>Tempat / Tanggal Lahir</label><div><?= e($siswa['tempat_lahir'] ?: '-') ?><?= $siswa['tanggal_lahir'] ? ' / ' . e($siswa['tanggal_lahir']) : '' ?></div></div>
            <div class="form-group"><label>Status</label><div><?= e($siswa['status']) ?></div></div>
            <div class="form-group"><label>Nama Orang Tua</label><div><?= e($siswa['nama_orang_tua'] ?: '-') ?></div></div>
            <div class="form-group"><label>No. HP Orang Tua</label><div><?= e($siswa['no_hp_orang_tua'] ?: '-') ?></div></div>
            <div class="form-group"><label>Total Poin</label><div><?= poin_badge($totalPoin) ?></div></div>
            <div class="form-group"><label>Alamat</label><div><?= e($siswa['alamat'] ?: '-') ?></div></div>
        </div>
    </div>
</div>

<div class="card table-card">
    <h3 style="margin-top: 0;">Riwayat Pelanggaran</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Tanggal</th><th>Pelanggaran</th><th>Poin</th><th>Pelapor</th><th>Lokasi</th><th>Keterangan</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php if (!$riwayat): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--text-muted);">Belum ada catatan pelanggaran.</td></tr>
                <?php endif; ?>
                <?php foreach ($riwayat as $r): ?>
                    <tr>
                        <td><?= e($r['tanggal']) ?></td>
                        <td><?= e($r['jenis_nama']) ?></td>
                        <td><span class="badge badge-warning"><?= (int) $r['bobot_poin'] ?></span></td>
                        <td><?= e($r['pelapor'] ?? '-') ?></td>
                        <td><?= e($r['lokasi'] ?: '-') ?></td>
                        <td><?= e($r['keterangan'] ?: '-') ?></td>
                        <td>
                            <div class="row-actions">
                                <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/edit.php?id=<?= (int) $r['id'] ?>" class="link-btn link-edit">Edit</a>
                                <form method="post" action="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/hapus.php?id=<?= (int) $r['id'] ?>" data-confirm="Hapus catatan pelanggaran ini?">
                                    <button type="submit" class="link-btn link-delete">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (can_see_all_data()): ?>
<div class="card table-card" style="margin-top:16px;">
    <h3 style="margin-top: 0;">Riwayat Konsultasi</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Tanggal</th><th>Permasalahan</th><th>Tindak Lanjut</th><th>Bukti Dukung</th><th>Konselor</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php if (!$konsultasi): ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">Belum ada catatan konsultasi.</td></tr>
                <?php endif; ?>
                <?php foreach ($konsultasi as $k): ?>
                    <tr>
                        <td><?= e($k['tanggal']) ?></td>
                        <td style="max-width:260px;"><?= nl2br(e($k['permasalahan'] ?: '-')) ?></td>
                        <td style="max-width:260px;"><?= nl2br(e($k['tindak_lanjut'] ?: '-')) ?></td>
                        <td>
                            <?php if (!empty($k['lampiran_file'])): ?>
                                <a href="<?= rtrim(APP_BASE, '/') ?>/konsultasi/download.php?id=<?= (int) $k['id'] ?>" class="secondary-btn" style="display:inline-block; font-size:12px; padding:6px 10px;">
                                    📄 <?= e($k['lampiran_original']) ?>
                                </a>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($k['konselor'] ?? '-') ?></td>
                        <td>
                            <div class="row-actions">
                                <a href="<?= rtrim(APP_BASE, '/') ?>/konsultasi/edit.php?id=<?= (int) $k['id'] ?>" class="link-btn link-edit">Edit</a>
                                <form method="post" action="<?= rtrim(APP_BASE, '/') ?>/konsultasi/hapus.php?id=<?= (int) $k['id'] ?>" data-confirm="Hapus catatan konsultasi ini?">
                                    <button type="submit" class="link-btn link-delete">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
