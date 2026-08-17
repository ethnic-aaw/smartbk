<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Only Admin/Guru BK can import
$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['Admin', 'Guru BK'], true)) {
    set_flash('error', 'Access denied');
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/index.php');
}

// Check if import data exists in session
if (!isset($_SESSION['import_data'])) {
    set_flash('error', 'Tidak ada data untuk di-preview. Silakan upload file terlebih dahulu.');
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/import.php');
}

$importData = $_SESSION['import_data'];
$validData = $importData['valid'];
$errors = $importData['errors'];
$skipped = $importData['skipped'];
$totalRows = $importData['total_rows'];

$pageTitle = 'Preview Import Siswa';
$activeMenu = 'siswa';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h3>Preview Import Siswa</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/import.php" class="secondary-btn">Batal</a>
</div>

<!-- Summary Cards -->
<div class="grid grid-4" style="margin-bottom: 16px;">
    <div class="card stat-card">
        <div class="label">Total Baris</div>
        <div class="value"><?= $totalRows ?></div>
        <div class="sub">Dari file CSV</div>
    </div>
    <div class="card stat-card" style="border-left: 3px solid var(--accent);">
        <div class="label">Data Valid</div>
        <div class="value" style="color: var(--accent);"><?= count($validData) ?></div>
        <div class="sub">Siap di-import</div>
    </div>
    <div class="card stat-card" style="border-left: 3px solid var(--danger);">
        <div class="label">Data Error</div>
        <div class="value" style="color: var(--danger);"><?= count($errors) ?></div>
        <div class="sub">Tidak bisa di-import</div>
    </div>
    <div class="card stat-card" style="border-left: 3px solid var(--warning);">
        <div class="label">Data Di-skip</div>
        <div class="value" style="color: var(--warning);"><?= count($skipped) ?></div>
        <div class="sub">NIPD sudah ada</div>
    </div>
</div>

<?php if (count($validData) > 0): ?>
<!-- Confirm Import Form -->
<div class="card" style="padding: 18px; margin-bottom: 16px; background: #f0fdf4; border: 1px solid #86efac;">
    <form method="post" action="<?= rtrim(APP_BASE, '/') ?>/siswa/import_execute.php">
        <?= csrf_field() ?>
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0 0 4px; color: var(--accent);">✅ Siap Import <?= count($validData) ?> Siswa</h3>
                <p style="margin: 0; color: var(--text-muted);">Data sudah divalidasi dan siap disimpan ke database</p>
            </div>
            <button type="submit" class="primary-btn" style="font-size: 16px; padding: 12px 24px;">
                🚀 Import Sekarang
            </button>
        </div>
    </form>
</div>

<!-- Valid Data Table -->
<div class="card table-card">
    <h3 style="margin-top: 0;">📋 Data Valid (<?= count($validData) ?>)</h3>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Baris</th>
                    <th>NIPD/NIS</th>
                    <th>Nama</th>
                    <th>JK</th>
                    <th>Kelas</th>
                    <th>Status</th>
                    <th>Aksi</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($validData as $data): ?>
                    <tr>
                        <td><?= $data['line'] ?></td>
                        <td><strong><?= e($data['nipd']) ?></strong></td>
                        <td><?= e($data['nama']) ?></td>
                        <td><?= $data['jenis_kelamin'] ?></td>
                        <td><?= e($data['kelas_nama'] ?: '-') ?></td>
                        <td><span class="badge badge-good"><?= e($data['status']) ?></span></td>
                        <td>
                            <?php if ($data['is_update']): ?>
                                <span class="badge badge-warning">UPDATE</span>
                            <?php else: ?>
                                <span class="badge badge-good">INSERT</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size: 12px; color: var(--text-muted);">
                            <?php if (!empty($data['warnings'])): ?>
                                <?php foreach ($data['warnings'] as $w): ?>
                                    <div>⚠️ <?= e($w) ?></div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color: var(--accent);">✓ OK</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (count($errors) > 0): ?>
<!-- Error Data -->
<div class="card table-card" style="margin-top: 16px;">
    <h3 style="margin-top: 0; color: var(--danger);">❌ Data Error (<?= count($errors) ?>)</h3>
    <p style="margin: 0 0 14px; color: var(--text-muted);">Data berikut tidak bisa di-import karena ada kesalahan. Perbaiki data di file CSV lalu upload ulang.</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Baris</th>
                    <th>NIPD/NIS</th>
                    <th>Nama</th>
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($errors as $err): ?>
                    <tr style="background: #fef2f2;">
                        <td><?= $err['line'] ?></td>
                        <td><?= e($err['nipd']) ?></td>
                        <td><?= e($err['nama']) ?></td>
                        <td style="color: var(--danger);">
                            <?php foreach ($err['errors'] as $e): ?>
                                <div>• <?= e($e) ?></div>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (count($skipped) > 0): ?>
<!-- Skipped Data -->
<div class="card table-card" style="margin-top: 16px;">
    <h3 style="margin-top: 0; color: var(--warning);">⏭️ Data Di-skip (<?= count($skipped) ?>)</h3>
    <p style="margin: 0 0 14px; color: var(--text-muted);">Data berikut di-skip karena NIPD sudah ada di database. Aktifkan "Update data jika NIPD sudah ada" jika ingin update.</p>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Baris</th>
                    <th>NIPD/NIS</th>
                    <th>Nama</th>
                    <th>Alasan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($skipped as $skip): ?>
                    <tr style="background: #fffbeb;">
                        <td><?= $skip['line'] ?></td>
                        <td><?= e($skip['nipd']) ?></td>
                        <td><?= e($skip['nama']) ?></td>
                        <td style="color: var(--warning);"><?= e($skip['reason']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (count($validData) === 0): ?>
<!-- No Valid Data -->
<div class="card" style="padding: 32px; text-align: center;">
    <div style="font-size: 48px; margin-bottom: 16px;">😕</div>
    <h3 style="margin: 0 0 8px;">Tidak Ada Data Valid untuk Di-import</h3>
    <p style="margin: 0 0 16px; color: var(--text-muted);">
        Semua data memiliki error atau di-skip. Perbaiki data di file CSV dan coba lagi.
    </p>
    <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/import.php" class="primary-btn">
        Kembali ke Upload
    </a>
</div>
<?php endif; ?>

<style>
.stat-card .value { margin-top: 8px; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
