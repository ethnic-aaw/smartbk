<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);

$rec = db_fetch(
    'SELECT p.*, s.kelas_id AS siswa_kelas
     FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     WHERE p.id = ? LIMIT 1',
    [$id],
    'row'
);

if (!$rec || empty($rec['bukti_file'])) {
    set_flash('error', 'Barang bukti tidak ditemukan.');
    redirect_to(rtrim(APP_BASE, '/') . '/pelanggaran/riwayat.php');
}

// Wali Kelas hanya boleh melihat/mengunduh bukti siswa di kelasnya
if (is_wali_kelas()) {
    if ((int) $rec['siswa_kelas'] !== get_user_kelas_id()) {
        http_response_code(403);
        die('Akses ditolak.');
    }
}

$dir = __DIR__ . '/../assets/uploads/bukti_pelanggaran';
$path = $dir . '/' . $rec['bukti_file'];

if (!file_exists($path)) {
    set_flash('error', 'File bukti tidak ditemukan di server.');
    redirect_to(rtrim(APP_BASE, '/') . '/pelanggaran/riwayat.php');
}

$types = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
];
$contentType = $rec['bukti_type'] ?? 'application/octet-stream';
if (!isset($types[$contentType])) {
    $contentType = 'application/octet-stream';
}

$originalName = $rec['bukti_original'] ?: 'barang_bukti';

header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $originalName . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-store');

readfile($path);
exit;