<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'       => '/',
        'domain'     => '',
        'secure'     => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly'   => true,
        'samesite'   => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}

function is_login_locked(): bool
{
    $lockout = $_SESSION['login_lockout'] ?? 0;
    return $lockout > time();
}

function login_failed(string $message = ''): void
{
    $attempts = ($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['login_attempts'] = $attempts;
    if ($attempts >= 5) {
        $_SESSION['login_lockout'] = time() + 900; // 15-minute lockout
        $_SESSION['login_attempts'] = 0;
    }
}

function login_succeeded(): void
{
    unset($_SESSION['login_attempts'], $_SESSION['login_lockout']);
    session_regenerate_id(true);
}
