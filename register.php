<?php
/**
 * Halaman Registrasi Berbasis Role (Setelah Google OAuth)
 * Hanya akses setelah OAuth callback (memerlukan $_SESSION['oauth_data'])
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

// Cek apakah ada data OAuth di session
if (empty($_SESSION['oauth_data'])) {
    set_flash('error', 'Sesi registrasi tidak valid. Silakan login dengan Google terlebih dahulu.');
    header('Location: ' . APP_BASE . 'login.php');
    exit;
}

$oauthData = $_SESSION['oauth_data'];
$email = $oauthData['email'];
$name = $oauthData['name'];
$googleId = $oauthData['google_id'];

// Cek apakah email sudah terdaftar (double check)
$existing = db_fetch('SELECT id FROM users WHERE email = ? LIMIT 1', [$email], 'row');
if ($existing) {
    // Seharusnya sudah ditangani di callback, tapi double check
    set_flash('error', 'Email sudah terdaftar. Silakan login langsung.');
    unset($_SESSION['oauth_data']);
    header('Location: ' . APP_BASE . 'login.php');
    exit;
}

$pageTitle = 'Lengkapi Registrasi';
$activeMenu = 'login';

$errors = [];
$old = [
    'role' => '',
    'kelas_id' => '',
    'nipd' => '',
];

// Ambil daftar kelas untuk dropdown Wali Kelas
$kelasList = db_fetch('SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas ASC');
$kelasList = $kelasList ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $errors['csrf'] = 'Token CSRF tidak valid. Segarkan halaman dan coba lagi.';
    } else {
        $old = [
            'role' => trim($_POST['role'] ?? ''),
            'kelas_id' => (int) ($_POST['kelas_id'] ?? 0) ?: null,
            'nipd' => trim($_POST['nipd'] ?? ''),
        ];
        
        $validRoles = ['Guru BK', 'Wali Kelas', 'Guru', 'Siswa'];
        
        if (!in_array($old['role'], $validRoles, true)) {
            $errors['role'] = 'Role tidak valid.';
        }
        
        // Validasi per role
        if ($old['role'] === 'Wali Kelas') {
            if (empty($old['kelas_id'])) {
                $errors['kelas_id'] = 'Wali Kelas harus memilih kelas yang diampu.';
            } else {
                // Cek apakah kelas sudah punya wali
                $kelas = db_fetch('SELECT wali_kelas_id FROM kelas WHERE id = ? LIMIT 1', [$old['kelas_id']], 'row');
                if ($kelas && !empty($kelas['wali_kelas_id'])) {
                    $errors['kelas_id'] = 'Kelas ini sudah memiliki wali kelas.';
                }
            }
        }
        
        if ($old['role'] === 'Siswa') {
            if ($old['nipd'] === '') {
                $errors['nipd'] = 'NIPD/NIS wajib diisi untuk registrasi siswa.';
            } else {
                // Cek NIPD di tabel siswa (harus Aktif)
                $siswa = db_fetch(
                    'SELECT id, nama, kelas_id FROM siswa WHERE nipd = ? AND status = ? LIMIT 1',
                    [$old['nipd'], 'Aktif'],
                    'row'
                );
                if (!$siswa) {
                    $errors['nipd'] = 'NIPD tidak ditemukan atau siswa tidak aktif.';
                } else {
                    // Cek apakah siswa sudah punya akun user
                    $existingSiswaUser = db_fetch('SELECT id FROM users WHERE siswa_id = ? LIMIT 1', [$siswa['id']], 'row');
                    if ($existingSiswaUser) {
                        $errors['nipd'] = 'Siswa ini sudah memiliki akun login.';
                    }
                }
            }
        }
        
        if (empty($errors)) {
            try {
                $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); // Random password, login via Google
                
                $siswaId = null;
                $kelasId = null;
                
                if ($old['role'] === 'Siswa') {
                    $siswa = db_fetch(
                        'SELECT id, kelas_id FROM siswa WHERE nipd = ? AND status = ? LIMIT 1',
                        [$old['nipd'], 'Aktif'],
                        'row'
                    );
                    $siswaId = $siswa['id'];
                    $kelasId = $siswa['kelas_id'];
                    
                    // Update siswa dengan google_id & email
                    db_query(
                        'UPDATE siswa SET google_id = ?, email = ? WHERE id = ?',
                        [$googleId, $email, $siswaId]
                    );
                } elseif ($old['role'] === 'Wali Kelas') {
                    $kelasId = $old['kelas_id'];
                    
                    // Update kelas dengan wali_kelas_id (akan di-update setelah approved)
                    // Untuk sekarang simpan ke users.kelas_id
                }
                
                $stmt = db_query(
                    'INSERT INTO users (nama, username, email, password_hash, role, kelas_id, siswa_id, google_id, email_verified_at, approval_status, status, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, NOW())',
                    [
                        $name,
                        $email, // username = email untuk OAuth users
                        $email,
                        $passwordHash,
                        $old['role'],
                        $kelasId,
                        $siswaId,
                        $googleId,
                        'pending', // approval_status
                        'Aktif'
                    ]
                );
                
                if ($stmt) {
                    $userId = db_last_id();
                    
                    // Jika Wali Kelas, set kelas.wali_kelas_id setelah approved
                    // Untuk sekarang simpan dulu, akan di-update saat approve
                    
                    unset($_SESSION['oauth_data']);
                    
                    set_flash('success', 'Registrasi berhasil! Akun Anda menunggu persetujuan dari Administrator/Guru BK.');
                    header('Location: ' . APP_BASE . 'pending_approval.php');
                    exit;
                } else {
                    $errors['general'] = 'Gagal menyimpan registrasi. Silakan coba lagi.';
                }
            } catch (Throwable $e) {
                error_log('Registration Error: ' . $e->getMessage());
                $errors['general'] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <h3>Lengkapi Registrasi</h3>
    <a href="<?= rtrim(APP_BASE, '/') ?>/login.php" class="secondary-btn">Kembali ke Login</a>
</div>

<div class="card form-card" style="max-width: 600px;">
    <?php if ($message ?? false): ?>
        <div class="alert error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if (!empty($errors['general'])): ?>
        <div class="alert error"><?= htmlspecialchars($errors['general']) ?></div>
    <?php endif; ?>
    
    <div class="alert info" style="margin-bottom: 1rem;">
        <strong>Akun Google terverifikasi:</strong><br>
        Nama: <?= htmlspecialchars($name) ?><br>
        Email: <?= htmlspecialchars($email) ?><br>
        <small>Email sudah diverifikasi oleh Google (@belajar.id)</small>
    </div>
    
    <form method="post">
        <?= csrf_field() ?>
        
        <div class="form-group">
            <label>Peran / Role <span style="color:red;">*</span></label>
            <select name="role" id="role-select" class="<?= isset($errors['role']) ? 'input-invalid' : '' ?>" required>
                <option value="">-- Pilih Peran --</option>
                <option value="Guru BK" <?= $old['role'] === 'Guru BK' ? 'selected' : '' ?>>Guru BK</option>
                <option value="Wali Kelas" <?= $old['role'] === 'Wali Kelas' ? 'selected' : '' ?>>Wali Kelas</option>
                <option value="Guru" <?= $old['role'] === 'Guru' ? 'selected' : '' ?>>Guru Mata Pelajaran</option>
                <option value="Siswa" <?= $old['role'] === 'Siswa' ? 'selected' : '' ?>>Siswa</option>
            </select>
            <?php if (isset($errors['role'])): ?><span class="field-error"><?= htmlspecialchars($errors['role']) ?></span><?php endif; ?>
        </div>
        
        <div class="form-group" id="kelas-group" style="display: none;">
            <label>Kelas yang Diampu <span style="color:red;">*</span></label>
            <select name="kelas_id" class="<?= isset($errors['kelas_id']) ? 'input-invalid' : '' ?>" required>
                <option value="">-- Pilih Kelas --</option>
                <?php foreach ($kelasList as $k): ?>
                    <option value="<?= (int) $k['id'] ?>" <?= $old['kelas_id'] === (int) $k['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($k['nama_kelas']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['kelas_id'])): ?><span class="field-error"><?= htmlspecialchars($errors['kelas_id']) ?></span><?php endif; ?>
            <small>Pilih kelas yang belum memiliki wali kelas.</small>
        </div>
        
        <div class="form-group" id="nipd-group" style="display: none;">
            <label>NIPD / NIS <span style="color:red;">*</span></label>
            <input type="text" name="nipd" value="<?= htmlspecialchars($old['nipd']) ?>" placeholder="Contoh: 2024001" class="<?= isset($errors['nipd']) ? 'input-invalid' : '' ?>" required>
            <?php if (isset($errors['nipd'])): ?><span class="field-error"><?= htmlspecialchars($errors['nipd']) ?></span><?php endif; ?>
            <small>NIPD harus terdaftar di data siswa aktif.</small>
        </div>
        
        <div class="form-group" style="margin-top: 1rem; padding: 1rem; background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px;">
            <strong>Catatan:</strong>
            <ul style="margin: 0.5rem 0 0 1.5rem;">
                <li>Akun akan <strong>menunggu persetujuan</strong> dari Administrator/Guru BK sebelum bisa login.</li>
                <li>Login menggunakan <strong>Google (@belajar.id)</strong> - tidak perlu password.</li>
                <li>Wali Kelas: Pilih kelas yang belum memiliki wali.</li>
                <li>Siswa: Masukkan NIPD yang terdaftar di sistem.</li>
            </ul>
        </div>
        
        <div class="form-actions">
            <button class="primary-btn" type="submit">Daftar & Tunggu Persetujuan</button>
            <a href="<?= rtrim(APP_BASE, '/') ?>/login.php" class="secondary-btn">Batal</a>
        </div>
    </form>
</div>

<script>
document.getElementById('role-select').addEventListener('change', function () {
    var kelasGroup = document.getElementById('kelas-group');
    var nipdGroup = document.getElementById('nipd-group');
    
    kelasGroup.style.display = this.value === 'Wali Kelas' ? '' : 'none';
    nipdGroup.style.display = this.value === 'Siswa' ? '' : 'none';
    
    // Reset required attributes
    var kelasSelect = kelasGroup.querySelector('select');
    var nipdInput = nipdGroup.querySelector('input');
    
    if (this.value === 'Wali Kelas') {
        kelasSelect.required = true;
        nipdInput.required = false;
    } else if (this.value === 'Siswa') {
        kelasSelect.required = false;
        nipdInput.required = true;
    } else {
        kelasSelect.required = false;
        nipdInput.required = false;
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>