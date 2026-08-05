<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Only Admin & Guru BK
if (!can_see_all_data()) {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$id = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $tamu = db_fetch('SELECT id, nama_tamu FROM buku_tamu WHERE id = ? LIMIT 1', [$id], 'row');

    if (!$tamu) {
        set_flash('error', 'Data tamu tidak ditemukan.');
    } else {
        $ok = db_query('DELETE FROM buku_tamu WHERE id = ?', [$id]);
        if ($ok) {
            set_flash('success', 'Catatan tamu "' . $tamu['nama_tamu'] . '" telah dihapus.');
        } else {
            set_flash('error', 'Gagal menghapus data tamu.');
        }
    }
} else {
    set_flash('error', 'Permintaan tidak valid.');
}

redirect_to(rtrim(APP_BASE, '/') . '/buku_tamu/index.php');
