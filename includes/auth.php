<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['user']) || empty($_SESSION['tahun_ajaran'])) {
    header('Location: ' . APP_BASE . 'login.php');
    exit;
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
