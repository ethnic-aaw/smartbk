<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/google_oauth.php';

$oauth = getGoogleOAuth();
$googleAuthUrl = $oauth->getAuthUrl();

$yearList = db_fetch('SELECT DISTINCT tahun_ajaran FROM kelas WHERE tahun_ajaran IS NOT NULL AND tahun_ajaran <> "" ORDER BY tahun_ajaran DESC');
$yearList = $yearList ?: [];
$yearOptions = array_column($yearList, 'tahun_ajaran');
if (!$yearOptions) {
    $year = (int) date('Y');
    $yearOptions = [date('n') >= 7 ? $year . '/' . ($year + 1) : ($year - 1) . '/' . $year];
}

$message = '';
if (is_login_locked()) {
    $message = 'Akun kamu dikunci sementara karena terlalu banyak percobaan login. Coba lagi dalam 15 menit.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $message = 'Token CSRF tidak valid. Segarkan halaman dan coba lagi.';
    } elseif ($message === '') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $tahunAjaran = trim($_POST['tahun_ajaran'] ?? '');

        if ($tahunAjaran === '') {
            $message = 'Pilih tahun ajaran.';
        } elseif (!in_array($tahunAjaran, $yearOptions, true)) {
            $message = 'Tahun ajaran tidak valid.';
        } elseif (!db_is_ready()) {
            $message = db_error();
        } elseif ($username === '' || $password === '') {
            $message = 'Username dan password wajib diisi.';
        } else {
            $row = db_fetch(
                'SELECT id, nama, username, password_hash, role, kelas_id, approval_status FROM users WHERE username = ? AND status = ? LIMIT 1',
                [$username, 'Aktif'],
                'row'
            );

            if ($row) {
                // Cek approval status untuk local login (non-Google)
                if ($row['approval_status'] === 'rejected') {
                    $message = 'Akun Anda ditolak. Hubungi administrator.';
                } elseif ($row['approval_status'] === 'pending') {
                    $message = 'Akun Anda menunggu persetujuan administrator.';
                } elseif (password_verify($password, $row['password_hash'])) {
                    login_succeeded();
                    $_SESSION['user'] = [
                        'id' => (int) $row['id'],
                        'name' => $row['nama'],
                        'role' => $row['role'],
                        'username' => $row['username'],
                        'kelas_id' => $row['kelas_id'] ? (int) $row['kelas_id'] : null,
                    ];
                    $_SESSION['tahun_ajaran'] = $tahunAjaran;
                    header('Location: ' . APP_BASE . 'dashboard.php');
                    exit;
                } else {
                    login_failed();
                    $message = 'Username atau password salah.';
                }
            } else {
                login_failed();
                $message = 'Username atau password salah.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Smart BK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="manifest" href="<?= rtrim(APP_BASE, '/') ?>/manifest.webmanifest">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" href="<?= rtrim(APP_BASE, '/') ?>/assets/icons/icon-192.png">
    <link rel="manifest" href="<?= rtrim(APP_BASE, '/') ?>/manifest.webmanifest">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="stylesheet" href="<?= rtrim(APP_BASE, '/') ?>/assets/css/style.css">
    <style>
        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0;
            color: var(--text-muted);
            font-size: 0.85rem;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border);
        }
        .divider:not(:empty)::before {
            margin-right: 0.75rem;
        }
        .divider:not(:empty)::after {
            margin-left: 0.75rem;
        }
        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.75rem 1rem;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text);
            cursor: pointer;
            transition: all 0.2s;
        }
        .google-btn:hover {
            background: #f8f9fa;
            border-color: #dadce0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .google-btn svg {
            width: 20px;
            height: 20px;
        }
        .login-info {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 1rem;
            padding: 0.75rem;
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 8px;
            text-align: center;
        }
    </style>
</head>
<body data-base="<?= rtrim(APP_BASE, '/') ?>">
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">SB</div>
        <h1>Masuk Smart BK</h1>
        <p>Selamat datang kembali, silakan masuk ke sistem.</p>
        
        <?php if ($message): ?>
            <div class="alert error"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <!-- Google OAuth Button -->
        <a href="<?= htmlspecialchars($googleAuthUrl) ?>" class="google-btn" style="text-decoration: none;">
            <svg viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            <span>Masuk dengan Google (@belajar.id)</span>
        </a>
        
        <div class="divider">atau</div>
        
        <?php if ($message): ?>
            <div class="alert error"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (!$yearOptions): ?>
            <div class="alert error">Belum ada data tahun ajaran. Pastikan database sudah di-import dari <code>sql/smart_bk.sql</code> (berisi tahun ajaran 2024/2025).</div>
        <?php endif; ?>
        
        <form method="post">
            <?= csrf_field() ?>
            <div class="form-group" style="margin-bottom: 14px;">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required>
            </div>
            <div class="form-group" style="margin-bottom: 18px;">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group" style="margin-bottom: 18px;">
                <label for="tahun_ajaran">Tahun Ajaran</label>
                <select name="tahun_ajaran" id="tahun_ajaran" required>
                    <option value="">-- Pilih Tahun Ajaran --</option>
                    <?php foreach ($yearOptions as $year): ?>
                        <option value="<?= e($year) ?>" <?= isset($tahunAjaran) && $tahunAjaran === $year ? 'selected' : '' ?>><?= e($year) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="primary-btn" style="width: 100%;">Masuk dengan Username</button>
        </form>
        
        <div class="login-info">
            <strong>Info:</strong> Gunakan akun <strong>@belajar.id</strong> untuk login dengan Google. 
            Admin lama bisa login dengan username/password lama.
        </div>
    </div>
</div>
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('<?= rtrim(APP_BASE, '/') ?>/sw.js').catch(() => {});
    }
</script>
</body>
</html>
