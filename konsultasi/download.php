<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Only Admin & Guru BK
if (!can_see_all_data()) {
    http_response_code(403);
    die('Akses ditolak.');
}

$id = (int) ($_GET['id'] ?? 0);
$kons = db_fetch('SELECT * FROM konsultasi_siswa WHERE id = ? LIMIT 1', [$id], 'row');

if (!$kons || empty($kons['lampiran_file'])) {
    set_flash('error', 'Lampiran tidak ditemukan.');
    redirect_to(rtrim(APP_BASE, '/') . '/konsultasi/index.php');
}

$dir = __DIR__ . '/../assets/uploads/lampiran_konsultasi';
$path = $dir . '/' . $kons['lampiran_file'];

if (!file_exists($path)) {
    set_flash('error', 'File lampiran tidak ditemukan di server.');
    redirect_to(rtrim(APP_BASE, '/') . '/konsultasi/index.php');
}

// Tentukan content type & ekstensi
$types = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
];
$contentType = $kons['lampiran_type'] ?? 'application/octet-stream';
if (!isset($types[$contentType])) {
    $contentType = 'application/octet-stream';
}

$originalName = $kons['lampiran_original'] ?: 'lampiran';

header('Content-Type: ' . $contentType);
header('Content-Disposition: attachment; filename="' . $originalName . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: no-store');

readfile($path);
exit;
