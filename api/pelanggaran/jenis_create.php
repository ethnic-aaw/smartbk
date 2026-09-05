<?php
require_once __DIR__ . '/../index.php';
require_once __DIR__ . '/../../src/Validators.php';

use SmartBK\Validators;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

require_role(['Admin']);

$input = get_json_input();

// Field-level validation via central validator (uniqueness checked below).
$errors = Validators::validateJenisPelanggaran($input);
if (!empty($errors)) {
    api_error(reset($errors));
}

$kode = trim($input['kode'] ?? '');
$nama = trim($input['nama'] ?? '');
$komponen = trim($input['komponen'] ?? '');
$kategori = trim($input['kategori'] ?? '');
$bobot_poin = (int) ($input['bobot_poin'] ?? 0);
$deskripsi = trim($input['deskripsi'] ?? '');
$konsekuensi = trim($input['konsekuensi'] ?? '');

$existing = db_fetch('SELECT id FROM jenis_pelanggaran WHERE kode = ? LIMIT 1', [$kode], 'row');
if ($existing) {
    api_error('Kode pelanggaran sudah terdaftar.');
}

$stmt = db_query(
    'INSERT INTO jenis_pelanggaran (kode, nama, komponen, kategori, bobot_poin, deskripsi, konsekuensi) VALUES (?, ?, ?, ?, ?, ?, ?)',
    [
        $kode,
        $nama,
        $komponen,
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
