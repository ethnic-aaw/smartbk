<?php
if (!defined('APP_BASE')) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = strpos($scriptName, '/smartbk/') !== false ? '/smartbk/' : '/';
    define('APP_BASE', $basePath);
}

// OAuth redirect URI (used by google_oauth.php if GOOGLE_REDIRECT_URI not set in env)
if (!defined('OAUTH_REDIRECT_URI')) {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('OAUTH_REDIRECT_URI', $scheme . $host . rtrim(APP_BASE, '/') . '/auth/google_callback.php');
}