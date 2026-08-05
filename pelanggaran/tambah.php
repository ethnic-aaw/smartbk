<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Catat Pelanggaran';
$activeMenu = 'pelanggaran_riwayat';

// Wali Kelas tidak boleh mencatat pelanggaran
if (is_wali_kelas()) {
    set_flash('error', 'Anda tidak memiliki akses untuk mencatat pelanggaran.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$tahunAjaran = current_tahun_ajaran();
$userKelasId = get_user_kelas_id();

if (is_wali_kelas()) {
    $siswaList = db_fetch(
        'SELECT s.id, s.nipd, s.nama, k.nama_kelas
         FROM siswa s
         JOIN kelas k ON k.id = s.kelas_id
         WHERE s.status = ? AND k.tahun_ajaran = ? AND s.kelas_id = ?
         ORDER BY s.nama ASC',
        ['Aktif', $tahunAjaran, $userKelasId]
    );
} else {
    $siswaList = db_fetch(
        'SELECT s.id, s.nipd, s.nama, k.nama_kelas
         FROM siswa s
         JOIN kelas k ON k.id = s.kelas_id
         WHERE s.status = ? AND k.tahun_ajaran = ?
         ORDER BY s.nama ASC',
        ['Aktif', $tahunAjaran]
    );
}
$siswaList = $siswaList ?: [];

$jenisList = db_fetch('SELECT id, kode, nama, bobot_poin FROM jenis_pelanggaran ORDER BY kode ASC');
$jenisList = $jenisList ?: [];

$errors = [];
$old = [
    'siswa_id' => (int) ($_GET['siswa_id'] ?? 0),
    'jenis_pelanggaran_id' => '',
    'tanggal' => date('Y-m-d'),
    'lokasi' => '',
    'keterangan' => '',
    'tindakan' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
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

    if (!$errors) {
        $ok = db_query(
            'INSERT INTO pelanggaran_siswa (siswa_id, jenis_pelanggaran_id, tanggal, lokasi, keterangan, tindakan, pelapor_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $old['siswa_id'], $old['jenis_pelanggaran_id'], $old['tanggal'],
                $old['lokasi'] ?: null, $old['keterangan'] ?: null, $old['tindakan'] ?: null,
                (int) ($_SESSION['user']['id'] ?? 0) ?: null,
            ]
        );

        if ($ok) {
            set_flash('success', 'Pelanggaran berhasil dicatat.');
            redirect_to(rtrim(APP_BASE, '/') . '/siswa/detail.php?id=' . $old['siswa_id']);
        }
        $errors['siswa_id'] = 'Gagal menyimpan data ke database.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Form Catat Pelanggaran Siswa</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/pelanggaran/riwayat.php" class="secondary-btn">Riwayat Pelanggaran</a>
</div>
<div class="card form-card">
    <form method="post">
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
                <input type="text" value="<?= e($_SESSION['user']['name'] ?? '') ?>" readonly>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Keterangan</label>
                <textarea name="keterangan" rows="4"><?= e($old['keterangan']) ?></textarea>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Tindakan Diambil</label>
                <textarea name="tindakan" rows="4"><?= e($old['tindakan']) ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button class="primary-btn" type="submit">Simpan</button>
            <a href="<?= rtrim(APP_BASE, '/') ?>/dashboard.php" class="secondary-btn">Batal</a>
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