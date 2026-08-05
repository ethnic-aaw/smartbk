<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

require_role(['Admin']);

$input = get_json_input();

$nama = trim($input['nama'] ?? '');
$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');
$role = trim($input['role'] ?? '');
$kelas_id = (int) ($input['kelas_id'] ?? 0);
$status = trim($input['status'] ?? 'Aktif');

if ($nama === '') {
    api_error('Nama lengkap wajib diisi.');
}
if ($username === '') {
    api_error('Username wajib diisi.');
}
if ($password === '') {
    api_error('Password wajib diisi.');
}
if (strlen($password) < 8) {
    api_error('Password minimal 8 karakter.');
}
if (!in_array($role, ['Admin', 'Guru BK', 'Wali Kelas', 'Siswa'], true)) {
    api_error('Role tidak valid.');
}
if (!in_array($status, ['Aktif', 'Nonaktif'], true)) {
    api_error('Status tidak valid.');
}

if (in_array($role, ['Guru BK', 'Wali Kelas'], true)) {
    if (!filter_var($username, FILTER_VALIDATE_EMAIL) || !str_ends_with($username, '@belajar.id')) {
        api_error('Username untuk Guru harus menggunakan format email @belajar.id');
    }
}

$existing = db_fetch('SELECT id FROM users WHERE username = ? LIMIT 1', [$username], 'row');
if ($existing) {
    api_error('Username sudah terdaftar.');
}

if ($role === 'Wali Kelas' && $kelas_id > 0) {
    $kelas = db_fetch('SELECT id FROM kelas WHERE id = ? LIMIT 1', [$kelas_id], 'row');
    if (!$kelas) {
        api_error('Kelas tidak ditemukan.');
    }
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = db_query(
    'INSERT INTO users (nama, username, password_hash, role, kelas_id, status) VALUES (?, ?, ?, ?, ?, ?)',
    [
        $nama,
        $username,
        $passwordHash,
        $role,
        ($role === 'Wali Kelas' && $kelas_id > 0) ? $kelas_id : null,
        $status
    ]
);

if (!$stmt) {
    api_error('Gagal menyimpan data user.', 500);
}

$newId = db_last_id();

api_success(['id' => $newId], 'User berhasil ditambahkan.');
