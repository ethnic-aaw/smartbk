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
    } elseif (empty($kons['lampiran_file'])) {
        set_flash('error', 'Konsultasi ini tidak memiliki lampiran.');
    } else {
        hapus_lampiran_konsultasi($kons['lampiran_file']);
        $ok = db_query(
            'UPDATE konsultasi_siswa SET lampiran_file = NULL, lampiran_original = NULL, lampiran_type = NULL, lampiran_size = NULL WHERE id = ?',
            [$id]
        );
        if ($ok) {
            set_flash('success', 'Lampiran berhasil dihapus.');
        } else {
            set_flash('error', 'Gagal menghapus lampiran.');
        }
    }
} else {
    set_flash('error', 'Permintaan tidak valid.');
}

redirect_to(rtrim(APP_BASE, '/') . '/konsultasi/edit.php?id=' . $id);
