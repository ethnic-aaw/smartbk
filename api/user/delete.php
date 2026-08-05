<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

require_role(['Admin']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    api_error('ID user tidak valid.');
}

$existing = db_fetch('SELECT * FROM users WHERE id = ? LIMIT 1', [$id], 'row');
if (!$existing) {
    api_error('User tidak ditemukan.', 404);
}

if ($id === $_SESSION['user']['id']) {
    api_error('Tidak bisa menghapus akun sendiri.');
}

$stmt = db_query('DELETE FROM users WHERE id = ?', [$id]);

if (!$stmt) {
    api_error('Gagal menghapus user.', 500);
}

api_success([], 'User berhasil dihapus.');
