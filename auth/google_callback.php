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
    
    // 7. Cek apakah user sudah ada di database
    $dbUser = db_fetch(
        'SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1',
        [$userInfo['google_id'], $userInfo['email']],
        'row'
    );
    
    if ($dbUser) {
        // User sudah ada - update google_id jika belum ada
        if (empty($dbUser['google_id'])) {
            db_query(
                'UPDATE users SET google_id = ?, email_verified_at = NOW() WHERE id = ?',
                [$userInfo['google_id'], $dbUser['id']]
            );
        }
        
        // Cek approval status
        if ($dbUser['approval_status'] === 'rejected') {
            set_flash('error', 'Akun Anda ditolak. Hubungi administrator.');
            header('Location: ' . APP_BASE . 'login.php');
            exit;
        }
        
        if ($dbUser['approval_status'] === 'pending') {
            // Simpan data OAuth ke session untuk ditampilkan di halaman pending
            $_SESSION['pending_user'] = [
                'id' => $dbUser['id'],
                'name' => $dbUser['nama'],
                'email' => $dbUser['email'],
                'role' => $dbUser['role'],
            ];
            header('Location: ' . APP_BASE . 'pending_approval.php');
            exit;
        }
        
        // Approved - login
        login_succeeded();
        $_SESSION['user'] = [
            'id' => (int) $dbUser['id'],
            'name' => $dbUser['nama'],
            'role' => $dbUser['role'],
            'username' => $dbUser['username'],
            'kelas_id' => $dbUser['kelas_id'] ? (int) $dbUser['kelas_id'] : null,
        ];
        
        // Update last_login_at
        db_query('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$dbUser['id']]);
        
        // Redirect ke dashboard (tahun ajaran akan dipilih nanti atau dari session)
        $yearList = db_fetch('SELECT DISTINCT tahun_ajaran FROM kelas WHERE tahun_ajaran IS NOT NULL AND tahun_ajaran <> "" ORDER BY tahun_ajaran DESC');
        $yearOptions = $yearList ? array_column($yearList, 'tahun_ajaran') : [];
        if ($yearOptions) {
            $_SESSION['tahun_ajaran'] = $yearOptions[0]; // default ke tahun terbaru
        }
        
        header('Location: ' . APP_BASE . 'dashboard.php');
        exit;
        
    } else {
        // User baru - simpan data OAuth ke session, redirect ke register.php
        $_SESSION['oauth_data'] = [
            'google_id' => $userInfo['google_id'],
            'email' => $userInfo['email'],
            'name' => $userInfo['name'],
            'given_name' => $userInfo['given_name'] ?? '',
            'family_name' => $userInfo['family_name'] ?? '',
            'picture' => $userInfo['picture'] ?? '',
        ];
        
        header('Location: ' . APP_BASE . 'register.php');
        exit;
    }
    
} catch (Throwable $e) {
    error_log('Google OAuth Error: ' . $e->getMessage());
    set_flash('error', 'Gagal login dengan Google: ' . $e->getMessage());
    header('Location: ' . APP_BASE . 'login.php');
    exit;
}