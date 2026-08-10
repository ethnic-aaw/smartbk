<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

require_role(['Admin', 'Guru BK']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    api_error('ID konseling tidak valid.');
}

$existing = db_fetch('SELECT * FROM konsultasi_siswa WHERE id = ? LIMIT 1', [$id], 'row');
if (!$existing) {
    api_error('Konseling tidak ditemukan.', 404);
}

// Hapus file lampiran jika ada
if (!empty($existing['lampiran_file'])) {
    $path = __DIR__ . '/../../assets/uploads/lampiran_konsultasi/' . $existing['lampiran_file'];
    if (file_exists($path)) {
        @unlink($path);
    }
}

$stmt = db_query('DELETE FROM konsultasi_siswa WHERE id = ?', [$id]);

if (!$stmt) {
    api_error('Gagal menghapus konseling.', 500);
}

api_success([], 'Konseling berhasil dihapus.');
