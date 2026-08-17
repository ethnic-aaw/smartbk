<?php
/**
 * Halaman Menunggu Persetujuan
 * Ditampilkan setelah registrasi berhasil, sebelum akun di-approve
 */

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Menunggu Persetujuan';
$activeMenu = 'login';

// Cek apakah ada pending_user di session
$pendingUser = $_SESSION['pending_user'] ?? null;

if (!$pendingUser) {
    // Cek apakah user sudah login tapi status pending
    if (!empty($_SESSION['user'])) {
        $user = db_fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [$_SESSION['user']['id']], 'row');
        if ($user && $user['approval_status'] === 'pending') {
            $pendingUser = [
                'id' => $user['id'],
                'name' => $user['nama'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];
        }
    }
}

if (!$pendingUser) {
    header('Location: ' . APP_BASE . 'login.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>
<div class="page-header">
    <h3>Menunggu Persetujuan</h3>
</div>

<div class="card form-card" style="max-width: 600px; text-align: center;">
    <div style="font-size: 4rem; margin-bottom: 1rem;">⏳</div>
    <h2>Akun Menunggu Persetujuan</h2>
    
    <div class="alert info" style="margin: 1.5rem 0; text-align: left;">
        <strong>Detail Akun:</strong><br>
        Nama: <?= htmlspecialchars($pendingUser['name']) ?><br>
        Email: <?= htmlspecialchars($pendingUser['email']) ?><br>
        Role: <span class="badge badge-warning"><?= htmlspecialchars($pendingUser['role']) ?></span>
    </div>
    
    <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
        Registrasi Anda telah berhasil dikirim. Akun ini saat ini menunggu persetujuan dari 
        <strong>Administrator</strong> atau <strong>Guru BK</strong>.
    </p>
    
    <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; text-align: left;">
        <strong>Yang akan terjadi selanjutnya:</strong>
        <ol style="margin: 0.5rem 0 0 1.5rem;">
            <li>Admin/Guru BK akan melihat permintaan Anda di halaman <em>Persetujuan User</em>.</li>
            <li>Mereka akan meninjau data dan memilih <strong>Setujui</strong> atau <strong>Tolak</strong>.</li>
            <li>Anda akan mendapat notifikasi (di halaman ini) setelah keputusan dibuat.</li>
            <li>Jika <strong>disetujui</strong>: Anda bisa login langsung dengan Google.</li>
            <li>Jika <strong>ditolak</strong>: Alasan penolakan akan ditampilkan.</li>
        </ol>
    </div>
    
    <p style="color: var(--text-muted); font-size: 0.9rem;">
        Halaman ini akan otomatis merefresh setiap 30 detik untuk memeriksa status.
    </p>
    
    <div style="margin-top: 2rem;">
        <a href="<?= rtrim(APP_BASE, '/') ?>/login.php" class="secondary-btn">Kembali ke Login</a>
    </div>
</div>

<script>
// Auto-refresh setiap 30 detik untuk cek status
setInterval(function() {
    fetch('<?= rtrim(APP_BASE, '/') ?>/api/auth/check.php')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success && res.data && res.data.user) {
                // User sudah approved dan login otomatis
                window.location.href = '<?= rtrim(APP_BASE, '/') ?>/dashboard.php';
            } else if (res.success === false && res.error) {
                // Cek apakah error karena rejected
                if (res.error.includes('ditolak') || res.error.includes('rejected')) {
                    window.location.reload(); // Reload untuk tampilkan pesan penolakan
                }
            }
        })
        .catch(function() {
            // Ignore network errors
        });
}, 30000);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>