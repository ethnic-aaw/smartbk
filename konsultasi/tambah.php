<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

// Only Admin & Guru BK
if (!can_see_all_data()) {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$pageTitle = 'Catat Konseling';
$activeMenu = 'konsultasi';

$tahunAjaran = current_tahun_ajaran();

// Daftar kelas untuk dropdown level 1
$kelasList = db_fetch(
    'SELECT k.id, k.nama_kelas FROM kelas k WHERE k.tahun_ajaran = ? ORDER BY k.nama_kelas ASC',
    [$tahunAjaran]
);
$kelasList = $kelasList ?: [];

$errors = [];
$old = [
    'siswa_id' => (int) ($_GET['siswa_id'] ?? 0),
    'tanggal' => date('Y-m-d'),
    'permasalahan' => '',
    'tindak_lanjut' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'siswa_id' => (int) ($_POST['siswa_id'] ?? 0),
        'tanggal' => trim($_POST['tanggal'] ?? ''),
        'permasalahan' => trim($_POST['permasalahan'] ?? ''),
        'tindak_lanjut' => trim($_POST['tindak_lanjut'] ?? ''),
    ];

    if (empty($old['siswa_id'])) {
        $errors['siswa_id'] = 'Pilih siswa terlebih dahulu.';
    }
    if ($old['tanggal'] === '') {
        $errors['tanggal'] = 'Tanggal wajib diisi.';
    }
    if ($old['permasalahan'] === '') {
        $errors['permasalahan'] = 'Permasalahan wajib diisi.';
    }

    // Upload lampiran (1 file, opsional)
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
            'INSERT INTO konsultasi_siswa (siswa_id, tanggal, permasalahan, tindak_lanjut, konselor_id, lampiran_file, lampiran_original, lampiran_type, lampiran_size)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $old['siswa_id'],
                $old['tanggal'],
                $old['permasalahan'],
                $old['tindak_lanjut'] !== '' ? $old['tindak_lanjut'] : null,
                (int) ($_SESSION['user']['id'] ?? 0) ?: null,
                $lampiran ? $lampiran['file'] : null,
                $lampiran ? $lampiran['original'] : null,
                $lampiran ? $lampiran['type'] : null,
                $lampiran ? $lampiran['size'] : null,
            ]
        );

        if ($ok) {
            set_flash('success', 'Konseling berhasil dicatat.');
            redirect_to(rtrim(APP_BASE, '/') . '/siswa/detail.php?id=' . $old['siswa_id']);
        }
        $errors['siswa_id'] = 'Gagal menyimpan data ke database.';
        if ($lampiran) {
            hapus_lampiran_konsultasi($lampiran['file']);
        }
    }

    if ($lampiranErr) {
        $errors['lampiran'] = $lampiranErr;
    }
}

// Pre-selection untuk dropdown kaskade (kelas → siswa)
$selectedSiswa = null;
$selectedKelasId = null;
if (!empty($old['siswa_id'])) {
    $selectedSiswa = db_fetch(
        'SELECT s.id, s.kelas_id, s.nama, s.nipd, k.nama_kelas
         FROM siswa s
         LEFT JOIN kelas k ON k.id = s.kelas_id
         WHERE s.id = ? LIMIT 1',
        [$old['siswa_id']],
        'row'
    );
    if ($selectedSiswa) {
        $selectedKelasId = (int) $selectedSiswa['kelas_id'];

        // Pastikan kelas siswa terpilih ada di daftar kelas (jika beda tahun ajaran)
        $kelasFound = false;
        foreach ($kelasList as $k) {
            if ((int) $k['id'] === $selectedKelasId) {
                $kelasFound = true;
                break;
            }
        }
        if (!$kelasFound) {
            $kelasSiswa = db_fetch('SELECT id, nama_kelas FROM kelas WHERE id = ? LIMIT 1', [$selectedKelasId], 'row');
            if ($kelasSiswa) {
                array_unshift($kelasList, $kelasSiswa);
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h3>Form Catat Konseling</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/konsultasi/index.php" class="secondary-btn">Kembali</a>
</div>
<div class="card form-card">
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="form-grid">
            <div class="form-group">
                <label>Kelas</label>
                <select id="kelas-select">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($kelasList as $k): ?>
                        <option value="<?= (int) $k['id'] ?>" <?= (int) $selectedKelasId === (int) $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Siswa</label>
                <select name="siswa_id" id="siswa-select" class="<?= isset($errors['siswa_id']) ? 'input-invalid' : '' ?>" <?= $selectedSiswa ? '' : 'disabled' ?>>
                    <?php if ($selectedSiswa): ?>
                        <option value="<?= (int) $selectedSiswa['id'] ?>" selected><?= e($selectedSiswa['nama']) ?> (<?= e($selectedSiswa['nipd']) ?>)</option>
                    <?php else: ?>
                        <option value="">-- Pilih Kelas dahulu --</option>
                    <?php endif; ?>
                </select>
                <?php if (isset($errors['siswa_id'])): ?><span class="field-error"><?= e($errors['siswa_id']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Tanggal</label>
                <input type="date" name="tanggal" value="<?= e($old['tanggal']) ?>" class="<?= isset($errors['tanggal']) ? 'input-invalid' : '' ?>">
                <?php if (isset($errors['tanggal'])): ?><span class="field-error"><?= e($errors['tanggal']) ?></span><?php endif; ?>
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
            <div class="form-group">
                <label>Bukti Dukung (foto / surat)</label>
                <input type="file" name="lampiran" accept=".jpg,.jpeg,.png,.webp,.pdf" class="<?= isset($errors['lampiran']) ? 'input-invalid' : '' ?>">
                <small style="color: var(--text-muted);">JPG, PNG, atau PDF - maksimal 2MB - 1 file</small>
                <?php if (isset($errors['lampiran'])): ?><span class="field-error"><?= e($errors['lampiran']) ?></span><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Konselor (Guru BK)</label>
                <input type="text" value="<?= e($_SESSION['user']['name'] ?? '') ?>" readonly>
            </div>
        </div>
        <div class="form-actions">
            <button class="primary-btn" type="submit">Simpan</button>
            <a href="<?= rtrim(APP_BASE, '/') ?>/konsultasi/index.php" class="secondary-btn">Batal</a>
        </div>
    </form>
</div>
<script>
(function () {
    var API_BASE = '<?= rtrim(APP_BASE, '/') ?>/';
    var preselectedKelasId = '<?= (int) ($selectedKelasId ?? 0) ?>';
    var preselectedSiswaId = '<?= (int) ($old['siswa_id'] ?? 0) ?>';

    var kelasSelect = document.getElementById('kelas-select');
    var siswaSelect = document.getElementById('siswa-select');

    function loadSiswa(kelasId) {
        if (!kelasId) {
            siswaSelect.innerHTML = '<option value="">-- Pilih Kelas dahulu --</option>';
            siswaSelect.disabled = true;
            return;
        }
        siswaSelect.innerHTML = '<option value="">Memuat siswa...</option>';
        siswaSelect.disabled = true;

        fetch(API_BASE + 'api/siswa/list.php?kelas=' + encodeURIComponent(kelasId) + '&per_page=100')
            .then(function (r) { return r.json(); })
            .then(function (res) {
                var items = (res && res.data && res.data.data) ? res.data.data : [];
                siswaSelect.innerHTML = '';
                if (items.length === 0) {
                    var empty = document.createElement('option');
                    empty.value = '';
                    empty.text = 'Tidak ada siswa di kelas ini';
                    siswaSelect.appendChild(empty);
                    siswaSelect.disabled = true;
                    return;
                }
                var ph = document.createElement('option');
                ph.value = '';
                ph.text = '-- Pilih Siswa --';
                siswaSelect.appendChild(ph);
                items.forEach(function (s) {
                    var opt = document.createElement('option');
                    opt.value = s.id;
                    opt.text = s.nama + ' (' + s.nipd + ')';
                    siswaSelect.appendChild(opt);
                });
                siswaSelect.disabled = false;
                if (preselectedSiswaId) {
                    siswaSelect.value = String(preselectedSiswaId);
                }
            })
            .catch(function () {
                siswaSelect.innerHTML = '<option value="">Gagal memuat data siswa</option>';
                siswaSelect.disabled = true;
            });
    }

    kelasSelect.addEventListener('change', function () {
        loadSiswa(this.value);
    });

    if (preselectedKelasId) {
        kelasSelect.value = String(preselectedKelasId);
        loadSiswa(preselectedKelasId);
    }
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
