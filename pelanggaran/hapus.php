<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);

// Wali Kelas tidak boleh hapus pelanggaran
if (is_wali_kelas()) {
    set_flash('error', 'Anda tidak memiliki akses untuk menghapus pelanggaran.');
    redirect_to(rtrim(APP_BASE, '/') . '/pelanggaran/riwayat.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $rec = db_fetch('SELECT id, siswa_id FROM pelanggaran_siswa WHERE id = ? LIMIT 1', [$id], 'row');

    if (!$rec) {
        set_flash('error', 'Catatan pelanggaran tidak ditemukan.');
    } else {
        $ok = db_query('DELETE FROM pelanggaran_siswa WHERE id = ?', [$id]);
        if ($ok) {
            set_flash('success', 'Catatan pelanggaran telah dihapus.');
        } else {
            set_flash('error', 'Gagal menghapus catatan pelanggaran.');
        }
    }
} else {
    set_flash('error', 'Permintaan tidak valid.');
}

redirect_to(rtrim(APP_BASE, '/') . '/pelanggaran/riwayat.php');