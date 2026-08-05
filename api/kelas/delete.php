<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

require_role(['Admin']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    api_error('ID kelas tidak valid.');
}

$existing = db_fetch('SELECT * FROM kelas WHERE id = ? LIMIT 1', [$id], 'row');
if (!$existing) {
    api_error('Kelas tidak ditemukan.', 404);
}

db_query('UPDATE siswa SET kelas_id = NULL WHERE kelas_id = ?', [$id]);

$stmt = db_query('DELETE FROM kelas WHERE id = ?', [$id]);

if (!$stmt) {
    api_error('Gagal menghapus kelas.', 500);
}

api_success([], 'Kelas berhasil dihapus.');
