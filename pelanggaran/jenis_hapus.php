<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Only Admin can access this page
if ($_SESSION['user']['role'] !== 'Admin') {
    set_flash('error', 'Anda tidak memiliki akses ke halaman ini.');
    redirect_to(rtrim(APP_BASE, '/') . '/dashboard.php');
}

$id = (int) ($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id > 0) {
    $jenis = db_fetch('SELECT id, nama FROM jenis_pelanggaran WHERE id = ? LIMIT 1', [$id], 'row');

    if (!$jenis) {
        set_flash('error', 'Jenis pelanggaran tidak ditemukan.');
    } else {
        $ref = db_fetch('SELECT COUNT(*) AS c FROM pelanggaran_siswa WHERE jenis_pelanggaran_id = ?', [$id], 'row');
        if ((int) ($ref['c'] ?? 0) > 0) {
            set_flash('error', 'Jenis pelanggaran ini masih digunakan oleh catatan pelanggaran dan tidak dapat dihapus.');
        } else {
            $ok = db_query('DELETE FROM jenis_pelanggaran WHERE id = ?', [$id]);
            if ($ok) {
                set_flash('success', 'Jenis pelanggaran "' . $jenis['nama'] . '" telah dihapus.');
            } else {
                set_flash('error', 'Gagal menghapus jenis pelanggaran.');
            }
        }
    }
} else {
    set_flash('error', 'Permintaan tidak valid.');
}

redirect_to(rtrim(APP_BASE, '/') . '/pelanggaran/master.php');