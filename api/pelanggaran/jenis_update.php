<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
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

$input = get_json_input();

$kode = trim($input['kode'] ?? $existing['kode']);
$nama = trim($input['nama'] ?? $existing['nama']);
$kategori = trim($input['kategori'] ?? $existing['kategori']);
$bobot_poin = isset($input['bobot_poin']) ? (int) $input['bobot_poin'] : (int) $existing['bobot_poin'];
$deskripsi = trim($input['deskripsi'] ?? $existing['deskripsi']);
$konsekuensi = trim($input['konsekuensi'] ?? $existing['konsekuensi']);

if ($kode === '') {
    api_error('Kode pelanggaran wajib diisi.');
}
if ($nama === '') {
    api_error('Nama pelanggaran wajib diisi.');
}
if (!in_array($kategori, ['Kedisiplinan', 'Tata Krama', 'Kekerasan', 'Narkoba', 'Lainnya'], true)) {
    api_error('Kategori tidak valid.');
}
if ($bobot_poin <= 0 || $bobot_poin > 100) {
    api_error('Bobot poin harus antara 1-100.');
}

if ($kode !== $existing['kode']) {
    $dup = db_fetch('SELECT id FROM jenis_pelanggaran WHERE kode = ? AND id != ? LIMIT 1', [$kode, $id], 'row');
    if ($dup) {
        api_error('Kode pelanggaran sudah digunakan.');
    }
}

$stmt = db_query(
    'UPDATE jenis_pelanggaran SET kode = ?, nama = ?, kategori = ?, bobot_poin = ?, deskripsi = ?, konsekuensi = ? WHERE id = ?',
    [
        $kode,
        $nama,
        $kategori,
        $bobot_poin,
        $deskripsi !== '' ? $deskripsi : null,
        $konsekuensi !== '' ? $konsekuensi : null,
        $id
    ]
);

if (!$stmt) {
    api_error('Gagal mengupdate jenis pelanggaran.', 500);
}

api_success(['id' => $id], 'Jenis pelanggaran berhasil diperbarui.');
