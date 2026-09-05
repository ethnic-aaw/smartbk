<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Only Admin & Guru BK can access this page
if (!can_see_all_data()) {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$id = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    if (isset($_SESSION['user']['id']) && (int) $_SESSION['user']['id'] === $id) {
        set_flash('error', 'Anda tidak dapat menghapus akun sendiri.');
    } else {
        $user = db_fetch('SELECT id, nama FROM users WHERE id = ? LIMIT 1', [$id], 'row');

        if (!$user) {
            set_flash('error', 'Data user tidak ditemukan.');
        } else {
            $ok = db_query('DELETE FROM users WHERE id = ?', [$id]);
            if ($ok) {
                set_flash('success', 'User "' . $user['nama'] . '" telah dihapus.');
            } else {
                set_flash('error', 'Gagal menghapus data user.');
            }
        }
    }
} else {
    set_flash('error', 'Permintaan tidak valid.');
}

redirect_to(rtrim(APP_BASE, '/') . '/user/index.php');