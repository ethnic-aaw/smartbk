<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

$input = get_json_input();
$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');
$tahunAjaran = trim($input['tahun_ajaran'] ?? '');

if ($username === '' || $password === '') {
    api_error('Username dan password wajib diisi.');
}

if ($tahunAjaran === '') {
    api_error('Pilih tahun ajaran.');
}

if (!db_is_ready()) {
    api_error(db_error(), 500);
}

if (is_login_locked()) {
    api_error('Terlalu banyak percobaan login. Coba lagi dalam 15 menit.', 429);
}

$row = db_fetch(
    'SELECT id, nama, username, password_hash, role FROM users WHERE username = ? AND status = ? LIMIT 1',
    [$username, 'Aktif'],
    'row'
);

if (!$row || !password_verify($password, $row['password_hash'])) {
    login_failed();
    api_error('Username atau password salah.', 401);
}

    login_succeeded();

    $_SESSION['user'] = [
        'id' => (int) $row['id'],
        'name' => $row['nama'],
        'role' => $row['role'],
        'username' => $row['username'],
    ];
$_SESSION['tahun_ajaran'] = $tahunAjaran;

api_success([
    'user' => $_SESSION['user'],
    'tahun_ajaran' => $tahunAjaran
], 'Login berhasil.');
