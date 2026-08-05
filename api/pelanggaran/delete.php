<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

require_role(['Admin']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    api_error('ID pelanggaran tidak valid.');
}

$existing = db_fetch('SELECT * FROM pelanggaran_siswa WHERE id = ? LIMIT 1', [$id], 'row');
if (!$existing) {
    api_error('Pelanggaran tidak ditemukan.', 404);
}

$stmt = db_query('DELETE FROM pelanggaran_siswa WHERE id = ?', [$id]);

if (!$stmt) {
    api_error('Gagal menghapus pelanggaran.', 500);
}

api_success([], 'Pelanggaran siswa berhasil dihapus.');
