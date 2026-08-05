<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/upload.php';

// Only Admin & Guru BK
if (!can_see_all_data()) {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$id = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $kons = db_fetch('SELECT * FROM konsultasi_siswa WHERE id = ? LIMIT 1', [$id], 'row');

    if (!$kons) {
        set_flash('error', 'Data konsultasi tidak ditemukan.');
    } else {
        if (!empty($kons['lampiran_file'])) {
            hapus_lampiran_konsultasi($kons['lampiran_file']);
        }
        $ok = db_query('DELETE FROM konsultasi_siswa WHERE id = ?', [$id]);
        if ($ok) {
            set_flash('success', 'Catatan konsultasi beserta lampirannya telah dihapus.');
        } else {
            set_flash('error', 'Gagal menghapus data konsultasi.');
        }
    }
} else {
    set_flash('error', 'Permintaan tidak valid.');
}

redirect_to(rtrim(APP_BASE, '/') . '/konsultasi/index.php');
