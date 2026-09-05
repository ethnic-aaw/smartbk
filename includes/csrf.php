<?php
/**
 * CSRF protection helpers.
 *
 * Session lifecycle is owned exclusively by includes/session.php, which starts
 * the session exactly once. This file MUST be required AFTER session.php so the
 * $_SESSION superglobal is already active when the helpers below run. We
 * deliberately do NOT call session_start() here to avoid a duplicate start.
 */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_check(?string $token = null): bool
{
    $expected = $_SESSION['csrf_token'] ?? '';
    $provided = $token ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
}

function csrf_check_or_die(): void
{
    if (!csrf_check()) {
        http_response_code(419);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>419 CSRF Token Mismatch</title></head><body style="font-family:sans-serif;padding:2rem;text-align:center;"><h1>419</h1><p>Token CSRF tidak valid atau sudah kadaluarsa.</p><p><a href="javascript:history.back()">Kembali</a></p></body></html>';
        exit;
    }
}