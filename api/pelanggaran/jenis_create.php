<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

require_role(['Admin']);

$input = get_json_input();

$kode = trim($input['kode'] ?? '');
$nama = trim($input['nama'] ?? '');
$kategori = trim($input['kategori'] ?? '');
$bobot_poin = (int) ($input['bobot_poin'] ?? 0);
$deskripsi = trim($input['deskripsi'] ?? '');
$konsekuensi = trim($input['konsekuensi'] ?? '');

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

$existing = db_fetch('SELECT id FROM jenis_pelanggaran WHERE kode = ? LIMIT 1', [$kode], 'row');
if ($existing) {
    api_error('Kode pelanggaran sudah terdaftar.');
}

$stmt = db_query(
    'INSERT INTO jenis_pelanggaran (kode, nama, kategori, bobot_poin, deskripsi, konsekuensi) VALUES (?, ?, ?, ?, ?, ?)',
    [
        $kode,
        $nama,
        $kategori,
        $bobot_poin,
        $deskripsi !== '' ? $deskripsi : null,
        $konsekuensi !== '' ? $konsekuensi : null
    ]
);

if (!$stmt) {
    api_error('Gagal menyimpan jenis pelanggaran.', 500);
}

$newId = db_last_id();

api_success(['id' => $newId], 'Jenis pelanggaran berhasil ditambahkan.');
