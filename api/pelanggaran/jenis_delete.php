<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

require_role(['Admin']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    api_error('ID jenis pelanggaran tidak valid.');
}

$existing = db_fetch('SELECT * FROM jenis_pelanggaran WHERE id = ? LIMIT 1', [$id], 'row');
if (!$existing) {
    api_error('Jenis pelanggaran tidak ditemukan.', 404);
}

$used = db_fetch('SELECT id FROM pelanggaran_siswa WHERE jenis_pelanggaran_id = ? LIMIT 1', [$id], 'row');
if ($used) {
    api_error('Jenis pelanggaran ini sudah digunakan dan tidak bisa dihapus.');
}

$stmt = db_query('DELETE FROM jenis_pelanggaran WHERE id = ?', [$id]);

if (!$stmt) {
    api_error('Gagal menghapus jenis pelanggaran.', 500);
}

api_success([], 'Jenis pelanggaran berhasil dihapus.');
