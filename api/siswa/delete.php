<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

require_role(['Admin']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    api_error('ID siswa tidak valid.');
}

$existing = db_fetch('SELECT * FROM siswa WHERE id = ? LIMIT 1', [$id], 'row');
if (!$existing) {
    api_error('Siswa tidak ditemukan.', 404);
}

db_query('DELETE FROM pelanggaran_siswa WHERE siswa_id = ?', [$id]);

if (!empty($existing['foto'])) {
    $fotoPath = __DIR__ . '/../../assets/uploads/foto_siswa/' . $existing['foto'];
    if (file_exists($fotoPath)) {
        @unlink($fotoPath);
    }
}

$stmt = db_query('DELETE FROM siswa WHERE id = ?', [$id]);

if (!$stmt) {
    api_error('Gagal menghapus siswa.', 500);
}

api_success([], 'Siswa berhasil dihapus.');
