<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Only Admin/Guru BK can import
$role = $_SESSION['user']['role'] ?? '';
if (!in_array($role, ['Admin', 'Guru BK'], true)) {
    set_flash('error', 'Anda tidak memiliki akses untuk import siswa.');
    redirect_to(rtrim(APP_BASE, '/') . '/siswa/index.php');
}

$pageTitle = 'Import Siswa dari Excel';
$activeMenu = 'siswa';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h3>Import Siswa dari File Excel</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/index.php" class="secondary-btn">Kembali</a>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr; gap: 16px;">
    <!-- Step 1: Download Template -->
    <div class="card form-card">
        <h3 style="margin-top: 0;">📥 Step 1: Download Template</h3>
        <p style="margin-bottom: 14px;">Download template Excel terlebih dahulu. Buka di Excel/Google Sheets, isi data per kolom, lalu upload kembali.</p>
        <a href="<?= rtrim(APP_BASE, '/') ?>/siswa/template_import.php" class="primary-btn" style="display: inline-block;">
            📄 Download Template Excel (.xls)
        </a>
        <div style="margin-top: 14px; padding: 12px; background: #eff6ff; border-radius: 8px; font-size: 13px;">
            <strong>Kolom yang harus diisi:</strong>
            <ul style="margin: 8px 0; padding-left: 20px;">
                <li><strong>NIPD/NIS</strong> (wajib, harus unik)</li>
                <li><strong>Nama Lengkap</strong> (wajib)</li>
                <li><strong>Jenis Kelamin</strong> (wajib: L atau P)</li>
                <li>Kelas (opsional)</li>
                <li>Tempat/Tanggal Lahir (opsional)</li>
                <li>Data Orang Tua — Nama Ayah, No HP Ayah, Pekerjaan Ayah, Nama Ibu, No HP Ibu, Pekerjaan Ibu, Nama Wali, Alamat Orang Tua (opsional)</li>
                <li>Alamat (opsional)</li>
                <li>Status (opsional, default: Aktif)</li>
            </ul>
        </div>
    </div>

    <!-- Step 2: Upload File -->
    <div class="card form-card">
        <h3 style="margin-top: 0;">📤 Step 2: Upload File</h3>
        <form method="post" action="<?= rtrim(APP_BASE, '/') ?>/siswa/import_process.php" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="form-group">
                <label>Pilih File Excel / CSV</label>
                <input type="file" name="csv_file" accept=".xls,.csv" required>
                <small style="color: var(--text-muted);">Format: .xls (Excel) atau .csv | Maksimal 2MB</small>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                    <input type="checkbox" name="skip_duplicate" value="1" checked>
                    <span>Skip NIPD yang sudah ada (tidak error)</span>
                </label>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                    <input type="checkbox" name="update_existing" value="1">
                    <span>Update data jika NIPD sudah ada</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-btn">📤 Upload & Preview Data</button>
            </div>
        </form>
    </div>
</div>

<!-- Step 3: Petunjuk -->
<div class="card" style="margin-top: 16px; padding: 18px;">
    <h3 style="margin-top: 0;">📋 Petunjuk Import</h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div>
            <h4 style="margin: 0 0 8px; color: var(--primary);">✅ Yang HARUS Dilakukan:</h4>
            <ul style="margin: 0; padding-left: 20px; font-size: 14px; line-height: 1.8;">
                <li>Download template terlebih dahulu</li>
                <li>Isi data sesuai format di template</li>
                <li>Pastikan NIPD/NIS unik (tidak ada yang sama)</li>
                <li>Jenis Kelamin: <strong>L</strong> atau <strong>P</strong></li>
                <li>Tanggal Lahir format: <strong>YYYY-MM-DD</strong></li>
                <li>Nama Kelas harus sesuai Master Kelas</li>
                <li>Hapus baris contoh dan petunjuk di template</li>
                <li>Jangan hapus baris header (baris pertama)</li>
            </ul>
        </div>
        
        <div>
            <h4 style="margin: 0 0 8px; color: var(--danger);">❌ Yang TIDAK Boleh:</h4>
            <ul style="margin: 0; padding-left: 20px; font-size: 14px; line-height: 1.8;">
                <li>Mengubah urutan kolom di template</li>
                <li>Menghapus baris header (kolom judul)</li>
                <li>NIPD/NIS kosong atau duplikat</li>
                <li>Jenis Kelamin selain L atau P</li>
                <li>Upload lebih dari 500 siswa sekaligus</li>
                <li>File selain format Excel/CSV</li>
                <li>Nama kelas yang tidak ada di Master Kelas</li>
            </ul>
        </div>
    </div>
</div>

<?php
// Get kelas list for format preview
$kelasPreview = db_fetch('SELECT nama_kelas, tahun_ajaran FROM kelas ORDER BY nama_kelas ASC');
$kelasPreview = $kelasPreview ?: [];
$kelas1Preview = $kelasPreview[0]['nama_kelas'] ?? 'X IPA 1';
$kelas2Preview = isset($kelasPreview[1]) ? $kelasPreview[1]['nama_kelas'] : $kelas1Preview;
?>

<!-- Format Preview -->
<div class="card" style="margin-top: 16px; padding: 18px;">
    <h3 style="margin-top: 0;">📄 Contoh Format File Excel</h3>
    <p style="margin: 0 0 14px; color: var(--text-muted);">Berikut tampilan format file yang harus diisi (setiap kolom di cell Excel). Baris pertama (header) <strong>jangan dihapus</strong>.</p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>NIPD/NIS *</th>
                    <th>Nama Lengkap *</th>
                    <th>Jenis Kelamin *</th>
                    <th>Kelas</th>
                    <th>Tempat Lahir</th>
                    <th>Tanggal Lahir</th>
                    <th>Nama Ayah</th>
                    <th>No HP Ayah</th>
                    <th>Pekerjaan Ayah</th>
                    <th>Nama Ibu</th>
                    <th>No HP Ibu</th>
                    <th>Pekerjaan Ibu</th>
                    <th>Nama Wali</th>
                    <th>Alamat Orang Tua</th>
                    <th>Alamat</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>2024001</code></td>
                    <td>Nama Siswa 1</td>
                    <td>L</td>
                    <td><?= e($kelas1Preview) ?></td>
                    <td>Jakarta</td>
                    <td>2008-01-15</td>
                    <td>Nama Ayah 1</td>
                    <td>081234567891</td>
                    <td>Wiraswasta</td>
                    <td>Nama Ibu 1</td>
                    <td>081234567892</td>
                    <td>Ibu Rumah Tangga</td>
                    <td></td>
                    <td></td>
                    <td>Jl. Contoh No. 1</td>
                    <td>Aktif</td>
                </tr>
                <tr>
                    <td><code>2024002</code></td>
                    <td>Nama Siswa 2</td>
                    <td>P</td>
                    <td><?= e($kelas2Preview) ?></td>
                    <td>Bandung</td>
                    <td>2008-03-20</td>
                    <td>Nama Ayah 2</td>
                    <td>081234567893</td>
                    <td>Wiraswasta</td>
                    <td>Nama Ibu 2</td>
                    <td>081234567894</td>
                    <td>PNS</td>
                    <td></td>
                    <td></td>
                    <td>Jl. Contoh No. 2</td>
                    <td>Aktif</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 14px; display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div style="padding: 12px; background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; font-size: 13px;">
            <strong style="color: var(--accent);">📌 Keterangan Kolom:</strong>
            <ul style="margin: 8px 0 0; padding-left: 18px;">
                <li><strong>NIPD/NIS</strong> - wajib, harus unik</li>
                <li><strong>Nama Lengkap</strong> - wajib</li>
                <li><strong>Jenis Kelamin</strong> - wajib, isi <strong>L</strong> atau <strong>P</strong></li>
                <li><strong>Kelas</strong> - opsional, harus sesuai daftar kelas</li>
                <li><strong>Tanggal Lahir</strong> - format <code>YYYY-MM-DD</code></li>
                <li><strong>Status</strong> - Aktif / Tidak Aktif / Pindah / Lulus</li>
                <li><strong>Data orang tua</strong> (nama/hp/pekerjaan ayah-ibu, nama wali, alamat ortu) - opsional</li>
            </ul>
        </div>
        <div style="padding: 12px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; font-size: 13px;">
            <strong style="color: var(--primary);">🏫 Daftar Kelas yang Tersedia:</strong>
            <ul style="margin: 8px 0 0; padding-left: 18px;">
                <?php if (!$kelasPreview): ?>
                    <li>Belum ada kelas terdaftar</li>
                <?php else: ?>
                    <?php foreach ($kelasPreview as $k): ?>
                        <li><code><?= e($k['nama_kelas']) ?></code> (<?= e($k['tahun_ajaran']) ?>)</li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<!-- Info -->
<div class="card" style="margin-top: 16px; padding: 18px; background: #fff7ed; border: 1px solid #fed7aa;">
    <div style="display: flex; gap: 12px; align-items: start;">
        <span style="font-size: 24px;">💡</span>
        <div>
            <strong>Tips:</strong>
            <ul style="margin: 8px 0 0; padding-left: 20px; font-size: 14px;">
                <li>Gunakan Microsoft Excel, Google Sheets, atau LibreOffice Calc untuk edit template</li>
                <li>Setelah upload, data akan ditampilkan untuk preview sebelum disimpan</li>
                <li>Jika ada error, akan ditampilkan baris mana yang bermasalah</li>
                <li>Data yang valid akan tetap bisa di-import meskipun ada beberapa yang error</li>
                <li>Proses import bisa memakan waktu beberapa detik untuk data yang banyak</li>
            </ul>
        </div>
    </div>
</div>

<style>
h4 { font-size: 14px; }
input[type="checkbox"] { width: auto; cursor: pointer; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
