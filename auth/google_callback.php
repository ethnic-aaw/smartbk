<?php
/**
 * Google OAuth Callback Handler
 * Menerima callback dari Google setelah user login
 */



require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/google_oauth.php';

$oauth = getGoogleOAuth();

// 1. Verifikasi state parameter (CSRF protection)
$state = $_GET['state'] ?? '';
if (!$oauth->verifyState($state)) {
    set_flash('error', 'State OAuth tidak valid. Silakan coba login lagi.');
    header('Location: ' . APP_BASE . 'login.php');
    exit;
}

// 2. Cek error dari Google
if (isset($_GET['error'])) {
    $errorMsg = match ($_GET['error']) {
        'access_denied' => 'Anda menolak izin akses. Silakan coba lagi dan izinkan akses.',
        'invalid_request' => 'Permintaan tidak valid.',
        default => 'Terjadi kesalahan: ' . htmlspecialchars($_GET['error']),
    };
    set_flash('error', $errorMsg);
    header('Location: ' . APP_BASE . 'login.php');
    exit;
}

// 3. Ambil authorization code
$code = $_GET['code'] ?? '';
if ($code === '') {
    set_flash('error', 'Authorization code tidak diterima dari Google.');
    header('Location: ' . APP_BASE . 'login.php');
    exit;
}

try {
    // 4. Tukar code dengan token
    $token = $oauth->fetchToken($code);
    
    // 5. Ambil user info dari Google
    $userInfo = $oauth->getUserInfo($token);
    
    // 6. Validasi domain @belajar.id
    if (!$oauth->validateDomain($userInfo['email'])) {
        set_flash('error', 'Login hanya diperbolehkan untuk akun @belajar.id');
        header('Location: ' . APP_BASE . 'login.php');
        exit;
    }
    
    // 7. Cek user berdasarkan email
    $dbUser = db_fetch(
        'SELECT id, nama, username, role, kelas_id, status, google_id, approval_status FROM users WHERE email = ? LIMIT 1',
        [$userInfo['email']],
        'row'
    );

    if (!$dbUser) {
        $nama = $userInfo['name'] ?? $userInfo['email'];
        $role = preg_match('/@guru\.smk\.belajar\.id$/i', $userInfo['email']) ? 'Guru BK' : 'Siswa';
        $approvalStatus = 'pending';

        $passwordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        db_query(
            'INSERT INTO users (nama, username, email, password_hash, google_id, role, status, approval_status, email_verified_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [
                $nama,
                $userInfo['email'],
                $userInfo['email'],
                $passwordHash,
                $userInfo['google_id'],
                $role,
                'Aktif',
                $approvalStatus,
            ]
        );

        $dbUser = db_fetch(
            'SELECT id, nama, username, role, kelas_id, status, google_id, approval_status FROM users WHERE email = ? LIMIT 1',
            [$userInfo['email']],
            'row'
        );

        set_flash('error', 'Akun baru berhasil dibuat. Menunggu persetujuan administrator.');
        header('Location: ' . APP_BASE . 'login.php');
        exit;
    }

    if (($dbUser['status'] ?? 'Aktif') !== 'Aktif') {
        set_flash('error', 'Akun ini tidak aktif. Hubungi administrator.');
        header('Location: ' . APP_BASE . 'login.php');
        exit;
    }

    $approval = $dbUser['approval_status'] ?? 'approved';
    if ($approval === 'rejected') {
        set_flash('error', 'Akun Anda ditolak. Hubungi administrator.');
        header('Location: ' . APP_BASE . 'login.php');
        exit;
    }
    if ($approval === 'pending') {
        set_flash('error', 'Akun Anda menunggu persetujuan administrator.');
        header('Location: ' . APP_BASE . 'login.php');
        exit;
    }

    if (empty($dbUser['google_id'])) {
        db_query(
            'UPDATE users SET google_id = ?, email_verified_at = NOW() WHERE id = ?',
            [$userInfo['google_id'], $dbUser['id']]
        );
    }

    login_succeeded();
    $_SESSION['user'] = [
        'id' => (int) $dbUser['id'],
        'name' => $dbUser['nama'],
        'role' => $dbUser['role'],
        'username' => $dbUser['username'],
        'kelas_id' => $dbUser['kelas_id'] ? (int) $dbUser['kelas_id'] : null,
    ];

    db_query('UPDATE users SET last_login_at = NOW(), email_verified_at = NOW() WHERE id = ?', [$dbUser['id']]);

    $yearList = db_fetch('SELECT DISTINCT tahun_ajaran FROM kelas WHERE tahun_ajaran IS NOT NULL AND tahun_ajaran <> "" ORDER BY tahun_ajaran DESC');
    $yearOptions = $yearList ? array_column($yearList, 'tahun_ajaran') : [];
    if ($yearOptions) {
        $_SESSION['tahun_ajaran'] = $yearOptions[0];
    }

    header('Location: ' . APP_BASE . 'dashboard.php');
    exit;
    
    } catch (Throwable $e) {
    error_log('Google OAuth Error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
    set_flash('error', 'Gagal login dengan Google: ' . $e->getMessage());
    header('Location: ' . APP_BASE . 'login.php');
    exit;
}