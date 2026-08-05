<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $siswa = db_fetch('SELECT id, nama, foto, kelas_id FROM siswa WHERE id = ? LIMIT 1', [$id], 'row');

    if (!$siswa) {
        set_flash('error', 'Data siswa tidak ditemukan.');
    } elseif (is_wali_kelas()) {
        set_flash('error', 'Anda tidak memiliki akses untuk menghapus siswa.');
    } else {
        $ok = db_query('DELETE FROM pelanggaran_siswa WHERE siswa_id = ?', [$id]);
        $ok2 = db_query('DELETE FROM siswa WHERE id = ?', [$id]);

        if ($ok && $ok2) {
            if (!empty($siswa['foto'])) {
                @unlink(__DIR__ . '/../assets/uploads/foto_siswa/' . $siswa['foto']);
            }
            set_flash('success', 'Siswa "' . $siswa['nama'] . '" beserta riwayat pelanggarannya telah dihapus.');
        } else {
            set_flash('error', 'Gagal menghapus data siswa.');
        }
    }
} else {
    set_flash('error', 'Permintaan tidak valid.');
}

redirect_to(rtrim(APP_BASE, '/') . '/siswa/index.php');
