<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
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

$input = get_json_input();

$nama = trim($input['nama'] ?? $existing['nama']);
$username = trim($input['username'] ?? $existing['username']);
$password = trim($input['password'] ?? '');
$role = trim($input['role'] ?? $existing['role']);
$kelas_id = isset($input['kelas_id']) ? (int) $input['kelas_id'] : (int) $existing['kelas_id'];
$status = trim($input['status'] ?? $existing['status']);

if ($nama === '') {
    api_error('Nama lengkap wajib diisi.');
}
if ($username === '') {
    api_error('Username wajib diisi.');
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

if ($username !== $existing['username']) {
    $dup = db_fetch('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1', [$username, $id], 'row');
    if ($dup) {
        api_error('Username sudah digunakan user lain.');
    }
}

if ($role === 'Wali Kelas' && $kelas_id > 0) {
    $kelas = db_fetch('SELECT id FROM kelas WHERE id = ? LIMIT 1', [$kelas_id], 'row');
    if (!$kelas) {
        api_error('Kelas tidak ditemukan.');
    }
}

if ($password !== '') {
    if (strlen($password) < 8) {
        api_error('Password minimal 8 karakter.');
    }
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = db_query(
        'UPDATE users SET nama = ?, username = ?, password_hash = ?, role = ?, kelas_id = ?, status = ? WHERE id = ?',
        [
            $nama,
            $username,
            $passwordHash,
            $role,
            ($role === 'Wali Kelas' && $kelas_id > 0) ? $kelas_id : null,
            $status,
            $id
        ]
    );
} else {
    $stmt = db_query(
        'UPDATE users SET nama = ?, username = ?, role = ?, kelas_id = ?, status = ? WHERE id = ?',
        [
            $nama,
            $username,
            $role,
            ($role === 'Wali Kelas' && $kelas_id > 0) ? $kelas_id : null,
            $status,
            $id
        ]
    );
}

if (!$stmt) {
    api_error('Gagal mengupdate data user.', 500);
}

api_success(['id' => $id], 'User berhasil diperbarui.');
