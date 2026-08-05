<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$message = '';
$yearList = db_fetch('SELECT DISTINCT tahun_ajaran FROM kelas WHERE tahun_ajaran IS NOT NULL AND tahun_ajaran <> "" ORDER BY tahun_ajaran DESC');
$yearList = $yearList ?: [];
$yearOptions = array_column($yearList, 'tahun_ajaran');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $tahunAjaran = trim($_POST['tahun_ajaran'] ?? '');

    if ($tahunAjaran === '') {
        $message = 'Pilih tahun ajaran.';
    } elseif (!in_array($tahunAjaran, $yearOptions, true)) {
        $message = 'Tahun ajaran tidak valid.';
    }

    if ($message === '') {
        if (!db_is_ready()) {
            $message = db_error();
        } elseif ($username !== '' && $password !== '') {
            $row = db_fetch(
                'SELECT id, nama, username, password_hash, role, kelas_id FROM users WHERE username = ? AND status = ? LIMIT 1',
                [$username, 'Aktif'],
                'row'
            );

            if ($row && password_verify($password, $row['password_hash'])) {
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
            }

            $message = 'Username atau password salah.';
        } else {
            $message = 'Username dan password wajib diisi.';
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
    <link rel="stylesheet" href="<?= rtrim(APP_BASE, '/') ?>/assets/css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">SB</div>
        <h1>Masuk Smart BK</h1>
        <p>Selamat datang kembali, silakan masuk ke sistem.</p>
        <?php if ($message): ?>
            <div class="alert error"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="post">
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
            <button class="primary-btn" style="width: 100%;">Masuk</button>
        </form>
        <p style="margin-top: 14px; font-size: 13px; color: var(--text-muted);">Demo: username <strong>admin</strong>, password <strong>admin123</strong></p>
    </div>
</div>
</body>
</html>
