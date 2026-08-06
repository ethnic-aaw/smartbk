<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/generate_lib.php';

// Only Admin can undo
if ($_SESSION['user']['role'] !== 'Admin') {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to(rtrim(APP_BASE, '/') . '/kelas/generate.php');
}

$logId = (int) ($_POST['log_id'] ?? 0);
if ($logId <= 0) {
    set_flash('error', 'Log generate tidak valid.');
    redirect_to(rtrim(APP_BASE, '/') . '/kelas/generate.php');
}

$result = undo_tahun_ajaran($logId);

if ($result['success']) {
    set_flash('success', $result['message']);
} else {
    set_flash('error', $result['message']);
}

redirect_to(rtrim(APP_BASE, '/') . '/kelas/generate.php');
