<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || empty($_SESSION['tahun_ajaran'])) {
    header('Location: ' . APP_BASE . 'login.php');
    exit;
}

// Check approval status for OAuth users
if (!empty($_SESSION['user']['id'])) {
    $userId = $_SESSION['user']['id'];
    $user = db_fetch('SELECT approval_status FROM users WHERE id = ? LIMIT 1', [$userId], 'row');
    if ($user && in_array($user['approval_status'], ['pending', 'rejected'], true)) {
        // Clear session and redirect to pending page
        session_unset();
        session_destroy();
        header('Location: ' . APP_BASE . 'pending_approval.php');
        exit;
    }
}

// CSRF guard for every state-changing request handled by protected pages.
if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'DELETE', 'PATCH'], true)) {
    csrf_check_or_die();
}

function is_wali_kelas(): bool
{
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Wali Kelas';
}

function get_user_kelas_id(): ?int
{
    return isset($_SESSION['user']['kelas_id']) ? (int) $_SESSION['user']['kelas_id'] : null;
}

function can_see_all_data(): bool
{
    $role = $_SESSION['user']['role'] ?? '';
    return in_array($role, ['Admin', 'Guru BK'], true);
}

function can_approve_users(): bool
{
    $role = $_SESSION['user']['role'] ?? '';
    return in_array($role, ['Admin', 'Guru BK'], true);
}

function is_approved(): bool
{
    // Untuk user yang sudah login, sudah dicek di atas
    return true;
}