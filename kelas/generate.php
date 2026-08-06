<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/generate_lib.php';

// Only Admin can access this page
if ($_SESSION['user']['role'] !== 'Admin') {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$pageTitle = 'Generate Tahun Ajaran';
$activeMenu = 'generate';

$tahunLama = current_tahun_ajaran();

$kelasRows = db_fetch(
    'SELECT k.id, k.nama_kelas, k.tingkat, k.wali_kelas_id, u.nama AS wali_kelas,
            (SELECT COUNT(*) FROM siswa s WHERE s.kelas_id = k.id AND s.status = ?) AS jumlah_siswa
     FROM kelas k
     LEFT JOIN users u ON u.id = k.wali_kelas_id
     WHERE k.tahun_ajaran = ?
     ORDER BY k.tingkat ASC, k.nama_kelas ASC',
    ['Aktif', $tahunLama]
);
$kelasRows = $kelasRows ?: [];

$totalNaik = 0;
$totalLulus = 0;
$naikRincian = [];
$lulusRincian = [];
foreach ($kelasRows as $k) {
    $jml = (int) $k['jumlah_siswa'];
    if (naik_tingkat($k['tingkat']) !== null) {
        $totalNaik += $jml;
        if ($jml > 0) {
            $naikRincian[] = $k['tingkat'] . '→' . naik_tingkat($k['tingkat']) . ' (' . $jml . ')';
        }
    } elseif (tingkat_lulus($k['tingkat'])) {
        $totalLulus += $jml;
        if ($jml > 0) {
            $lulusRincian[] = $k['tingkat'] . ' (' . $jml . ')';
        }
    }
}

// Cek apakah ada generate aktif untuk tahun ajaran ini (untuk tombol Undo)
$logAktif = db_fetch(
    'SELECT * FROM log_generate WHERE tahun_ajaran_lama = ? AND status = ? ORDER BY id DESC LIMIT 1',
    [$tahunLama, 'Aktif'],
    'row'
);

$errors = [];
$old = ['tahun_ajaran' => '', 'buat_kelas_x' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['tahun_ajaran'] = trim($_POST['tahun_ajaran'] ?? '');
    $old['buat_kelas_x'] = isset($_POST['buat_kelas_x']) ? 1 : 0;

    if ($old['tahun_ajaran'] === '') {
        $errors['tahun_ajaran'] = 'Tahun ajaran baru wajib diisi.';
    } elseif (!preg_match('/^\d{4}\/\d{4}$/', $old['tahun_ajaran'])) {
        $errors['tahun_ajaran'] = 'Format tahun ajaran harus YYYY/YYYY (contoh: 2025/2026).';
    } elseif ($old['tahun_ajaran'] === $tahunLama) {
        $errors['tahun_ajaran'] = 'Tahun ajaran baru tidak boleh sama dengan tahun ajaran berjalan.';
    } else {
        $exists = db_fetch('SELECT COUNT(*) AS c FROM kelas WHERE tahun_ajaran = ?', [$old['tahun_ajaran']], 'row');
        if ((int) ($exists['c'] ?? 0) > 0) {
            $errors['tahun_ajaran'] = 'Tahun ajaran "' . $old['tahun_ajaran'] . '" sudah terdaftar. Silakan batalkan generate sebelumnya atau gunakan tahun lain.';
        }
    }

    if ($logAktif) {
        $errors['global'] = 'Masih ada generate aktif untuk tahun ajaran ini. Batalkan (Undo) generate sebelumnya terlebih dahulu.';
    }

    if (!$errors) {
        $result = generate_tahun_ajaran($tahunLama, $old['tahun_ajaran'], (bool) $old['buat_kelas_x']);
        if ($result['success']) {
            set_flash('success', $result['message']);
        } else {
            set_flash('error', $result['message']);
        }
        redirect_to(rtrim(APP_BASE, '/') . '/kelas/generate.php');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h3>Generate Tahun Ajaran Baru</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/dashboard.php" class="secondary-btn">Kembali</a>
</div>

<?php if (isset($errors['global'])): ?>
    <div class="alert error"><?= e($errors['global']) ?></div>
<?php endif; ?>

<?php if ($logAktif): ?>
    <div class="card" style="margin-bottom:16px; padding:18px; background:#fef2f2; border:1px solid #fecaca;">
        <div style="display:flex; gap:12px; align-items:flex-start; flex-wrap:wrap;">
            <div style="flex:1;">
                <strong>Generate Tahun Ajaran <?= e($logAktif['tahun_ajaran_baru']) ?> aktif.</strong>
                <p style="margin:6px 0 0; color:var(--text-muted); font-size:13px;">
                    Data siswa sudah dipindahkan/naik kelas. Jika belum sesuai, Anda bisa membatalkannya agar kembali ke kondisi semula (siswa XII dikembalikan ke status Aktif, kelas baru dihapus).
                </p>
            </div>
            <form method="post" action="<?= rtrim(APP_BASE, '/') ?>/kelas/generate_undo.php" data-confirm="Yakin ingin membatalkan generate tahun ajaran <?= e($logAktif['tahun_ajaran_baru']) ?>? Data siswa akan dikembalikan ke kondisi sebelum generate.">
                <input type="hidden" name="log_id" value="<?= (int) $logAktif['id'] ?>">
                <button type="submit" class="danger-btn" style="padding:10px 16px;">↩ Batalkan / Undo</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if (!$kelasRows): ?>
    <div class="alert error">Belum ada data kelas pada tahun ajaran <?= e($tahunLama) ?>. Buat kelas terlebih dahulu di <a href="<?= rtrim(APP_BASE, '/') ?>/kelas/index.php">Master Kelas</a>.</div>
<?php else: ?>

<div class="grid" style="grid-template-columns: 1fr 1fr; gap:16px;">
    <div class="card form-card">
        <h3 style="margin-top:0;">Form Generate</h3>
        <form method="post">
            <div class="form-group">
                <label>Tahun Ajaran Berjalan</label>
                <input type="text" value="<?= e($tahunLama) ?>" disabled>
            </div>
            <div class="form-group">
                <label>Tahun Ajaran Baru *</label>
                <input type="text" name="tahun_ajaran" value="<?= e($old['tahun_ajaran']) ?>" placeholder="Contoh: 2025/2026" class="<?= isset($errors['tahun_ajaran']) ? 'input-invalid' : '' ?>">
                <?php if (isset($errors['tahun_ajaran'])): ?><span class="field-error"><?= e($errors['tahun_ajaran']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label style="display:flex; align-items:center; gap:8px; font-weight:normal;">
                    <input type="checkbox" name="buat_kelas_x" value="1" <?= $old['buat_kelas_x'] ? 'checked' : '' ?>>
                    <span>Buat kelas X kosong untuk tahun baru (untuk siswa baru yang di-import)</span>
                </label>
            </div>
            <div class="form-actions">
                <button type="submit" class="primary-btn">🚀 Generate Tahun Ajaran</button>
            </div>
        </form>
    </div>

    <div class="card form-card">
        <h3 style="margin-top:0;">Ringkasan</h3>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div style="padding:12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; text-align:center;">
                <div style="font-size:22px; font-weight:700; color:var(--primary);"><?= $totalNaik ?></div>
                <div style="font-size:13px; color:var(--text-muted);">Siswa naik kelas<br><?= e(implode(', ', $naikRincian) ?: '-') ?></div>
            </div>
            <div style="padding:12px; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; text-align:center;">
                <div style="font-size:22px; font-weight:700; color:var(--danger);"><?= $totalLulus ?></div>
                <div style="font-size:13px; color:var(--text-muted);">Siswa lulus/alumni<br><?= e(implode(', ', $lulusRincian) ?: '-') ?></div>
            </div>
        </div>
        <div style="margin-top:14px; font-size:13px; color:var(--text-muted); line-height:1.7;">
            Siswa yang lulus tetap tersimpan di tahun ajaran lama (<?= e($tahunLama) ?>) dengan status <strong>Lulus</strong> dan riwayatnya tetap bisa dilihat.
        </div>
    </div>
</div>

<div class="card table-card" style="margin-top:16px;">
    <h3 style="margin-top:0;">Preview Kenaikan Kelas</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kelas Lama (<?= e($tahunLama) ?>)</th>
                    <th>Tingkat</th>
                    <th>Siswa Aktif</th>
                    <th>Wali Kelas</th>
                    <th>Menjadi</th>
                    <th>Kelas Baru (<?= e($old['tahun_ajaran'] ?: 'Tahun Baru') ?>)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kelasRows as $k): ?>
                    <?php
                    $tingkatBaru = naik_tingkat($k['tingkat']);
                    $lulus = tingkat_lulus($k['tingkat']);
                    ?>
                    <tr>
                        <td><strong><?= e($k['nama_kelas']) ?></strong></td>
                        <td><?= e($k['tingkat']) ?></td>
                        <td><?= (int) $k['jumlah_siswa'] ?> siswa</td>
                        <td><?= e($k['wali_kelas'] ?? '-') ?></td>
                        <td>
                            <?php if ($tingkatBaru !== null): ?>
                                <span class="badge badge-good"><?= e($k['tingkat']) ?> → <?= e($tingkatBaru) ?></span>
                            <?php elseif ($lulus): ?>
                                <span class="badge badge-danger">Lulus / Alumni</span>
                            <?php else: ?>
                                <span class="badge">Tidak berubah</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($tingkatBaru !== null): ?>
                                <?= e(nama_kelas_naik($k['nama_kelas'], $k['tingkat'], $tingkatBaru)) ?>
                            <?php elseif ($lulus): ?>
                                <em style="color:var(--text-muted);">- (tetap di tahun lama)</em>
                            <?php else: ?>
                                <em style="color:var(--text-muted);">-</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:16px; padding:18px; background:#eff6ff; border:1px solid #bfdbfe;">
    <strong>📌 Langkah setelah Generate:</strong>
    <ul style="margin:8px 0 0; padding-left:20px; font-size:14px; line-height:1.8;">
        <li>Isi data siswa baru (kelas X) dengan <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/import.php">📤 Import CSV</a> menggunakan template yang tersedia, atau tambah manual.</li>
        <li>Atur ulang wali kelas untuk kelas X baru di <a href="<?= rtrim(APP_BASE, '/') ?>/kelas/index.php">Master Kelas</a>.</li>
        <li>Jika belum sesuai, gunakan tombol <strong>Batalkan / Undo</strong> di atas sebelum mulai input data baru.</li>
    </ul>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
