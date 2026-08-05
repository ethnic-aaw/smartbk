<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

require_role(['Admin']);

$input = get_json_input();

$nama_kelas = trim($input['nama_kelas'] ?? '');
$tingkat = trim($input['tingkat'] ?? '');
$wali_kelas_id = (int) ($input['wali_kelas_id'] ?? 0);
$tahun_ajaran = trim($input['tahun_ajaran'] ?? $_SESSION['tahun_ajaran']);

if ($nama_kelas === '') {
    api_error('Nama kelas wajib diisi.');
}
if ($tingkat === '') {
    api_error('Tingkat wajib diisi.');
}
if ($tahun_ajaran === '') {
    api_error('Tahun ajaran wajib diisi.');
}

if ($wali_kelas_id > 0) {
    $waliKelas = db_fetch('SELECT id FROM users WHERE id = ? AND role = ? LIMIT 1', [$wali_kelas_id, 'Wali Kelas'], 'row');
    if (!$waliKelas) {
        api_error('Wali kelas tidak ditemukan atau bukan role Wali Kelas.');
    }
}

$stmt = db_query(
    'INSERT INTO kelas (nama_kelas, tingkat, wali_kelas_id, tahun_ajaran) VALUES (?, ?, ?, ?)',
    [
        $nama_kelas,
        $tingkat,
        $wali_kelas_id > 0 ? $wali_kelas_id : null,
        $tahun_ajaran
    ]
);

if (!$stmt) {
    api_error('Gagal menyimpan data kelas.', 500);
}

$newId = db_last_id();

api_success(['id' => $newId], 'Kelas berhasil ditambahkan.');
