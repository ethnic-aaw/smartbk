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
    $kelas = db_fetch('SELECT id, nama_kelas FROM kelas WHERE id = ? LIMIT 1', [$id], 'row');

    if (!$kelas) {
        set_flash('error', 'Data kelas tidak ditemukan.');
    } else {
        db_query('UPDATE siswa SET kelas_id = NULL WHERE kelas_id = ?', [$id]);
        $ok = db_query('DELETE FROM kelas WHERE id = ?', [$id]);

        if ($ok) {
            set_flash('success', 'Kelas "' . $kelas['nama_kelas'] . '" telah dihapus.');
        } else {
            set_flash('error', 'Gagal menghapus data kelas.');
        }
    }
} else {
    set_flash('error', 'Permintaan tidak valid.');
}

redirect_to(rtrim(APP_BASE, '/') . '/kelas/index.php');