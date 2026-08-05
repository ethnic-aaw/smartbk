<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

require_role(['Admin', 'Guru BK']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    api_error('ID buku tamu tidak valid.');
}

$existing = db_fetch('SELECT * FROM buku_tamu WHERE id = ? LIMIT 1', [$id], 'row');
if (!$existing) {
    api_error('Data tamu tidak ditemukan.', 404);
}

$stmt = db_query('DELETE FROM buku_tamu WHERE id = ?', [$id]);

if (!$stmt) {
    api_error('Gagal menghapus data tamu.', 500);
}

api_success([], 'Data tamu berhasil dihapus.');
