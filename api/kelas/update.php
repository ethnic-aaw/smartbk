<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
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

$input = get_json_input();

$nama_kelas = trim($input['nama_kelas'] ?? $existing['nama_kelas']);
$tingkat = trim($input['tingkat'] ?? $existing['tingkat']);
$wali_kelas_id = isset($input['wali_kelas_id']) ? (int) $input['wali_kelas_id'] : (int) $existing['wali_kelas_id'];
$tahun_ajaran = trim($input['tahun_ajaran'] ?? $existing['tahun_ajaran']);

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
    'UPDATE kelas SET nama_kelas = ?, tingkat = ?, wali_kelas_id = ?, tahun_ajaran = ? WHERE id = ?',
    [
        $nama_kelas,
        $tingkat,
        $wali_kelas_id > 0 ? $wali_kelas_id : null,
        $tahun_ajaran,
        $id
    ]
);

if (!$stmt) {
    api_error('Gagal mengupdate data kelas.', 500);
}

api_success(['id' => $id], 'Kelas berhasil diperbarui.');
