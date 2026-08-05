<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed', 405);
}

require_auth();

$roleFilter = trim($_GET['role'] ?? '');
$validRoles = ['Admin', 'Guru BK', 'Wali Kelas', 'Siswa'];

if ($roleFilter !== '' && !in_array($roleFilter, $validRoles, true)) {
    api_error('Role filter tidak valid.');
}

if ($roleFilter !== '') {
    $userList = db_fetch(
        'SELECT u.*, k.nama_kelas
         FROM users u
         LEFT JOIN kelas k ON k.id = u.kelas_id
         WHERE u.role = ?
         ORDER BY u.nama ASC',
        [$roleFilter]
    );
} else {
    $userList = db_fetch(
        'SELECT u.*, k.nama_kelas
         FROM users u
         LEFT JOIN kelas k ON k.id = u.kelas_id
         ORDER BY FIELD(u.role, "Admin", "Guru BK", "Wali Kelas", "Siswa"), u.nama ASC'
    );
}

api_success([
    'data' => $userList ?: []
]);
