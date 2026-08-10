<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

require_role(['Admin']);

$input = get_json_input();

$nipd = trim($input['nipd'] ?? '');
$nama = trim($input['nama'] ?? '');
$jenis_kelamin = trim($input['jenis_kelamin'] ?? '');
$kelas_id = (int) ($input['kelas_id'] ?? 0);
$tempat_lahir = trim($input['tempat_lahir'] ?? '');
$tanggal_lahir = trim($input['tanggal_lahir'] ?? '');
$nama_orang_tua = trim($input['nama_orang_tua'] ?? '');
$no_hp_orang_tua = trim($input['no_hp_orang_tua'] ?? '');
$nama_ayah = trim($input['nama_ayah'] ?? '');
$no_hp_ayah = trim($input['no_hp_ayah'] ?? '');
$pekerjaan_ayah = trim($input['pekerjaan_ayah'] ?? '');
$nama_ibu = trim($input['nama_ibu'] ?? '');
$no_hp_ibu = trim($input['no_hp_ibu'] ?? '');
$pekerjaan_ibu = trim($input['pekerjaan_ibu'] ?? '');
$nama_wali = trim($input['nama_wali'] ?? '');
$alamat_orang_tua = trim($input['alamat_orang_tua'] ?? '');
$alamat = trim($input['alamat'] ?? '');
$status = trim($input['status'] ?? 'Aktif');

if ($nipd === '') {
    api_error('NIPD/NIS wajib diisi.');
}
if ($nama === '') {
    api_error('Nama lengkap wajib diisi.');
}
if (!in_array($jenis_kelamin, ['L', 'P'], true)) {
    api_error('Jenis kelamin harus L atau P.');
}
if (!in_array($status, ['Aktif', 'Tidak Aktif', 'Pindah', 'Lulus'], true)) {
    api_error('Status tidak valid.');
}

$existing = db_fetch('SELECT id FROM siswa WHERE nipd = ? LIMIT 1', [$nipd], 'row');
if ($existing) {
    api_error('NIPD/NIS sudah terdaftar.');
}

if ($kelas_id > 0) {
    $kelas = db_fetch('SELECT id FROM kelas WHERE id = ? LIMIT 1', [$kelas_id], 'row');
    if (!$kelas) {
        api_error('Kelas tidak ditemukan.');
    }
}

$stmt = db_query(
    'INSERT INTO siswa (nipd, nama, jenis_kelamin, kelas_id, tempat_lahir, tanggal_lahir, nama_orang_tua, no_hp_orang_tua, nama_ayah, no_hp_ayah, pekerjaan_ayah, nama_ibu, no_hp_ibu, pekerjaan_ibu, nama_wali, alamat_orang_tua, alamat, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
    [
        $nipd, $nama, $jenis_kelamin, 
        $kelas_id > 0 ? $kelas_id : null,
        $tempat_lahir !== '' ? $tempat_lahir : null,
        $tanggal_lahir !== '' ? $tanggal_lahir : null,
        $nama_orang_tua !== '' ? $nama_orang_tua : null,
        $no_hp_orang_tua !== '' ? $no_hp_orang_tua : null,
        $nama_ayah !== '' ? $nama_ayah : null,
        $no_hp_ayah !== '' ? $no_hp_ayah : null,
        $pekerjaan_ayah !== '' ? $pekerjaan_ayah : null,
        $nama_ibu !== '' ? $nama_ibu : null,
        $no_hp_ibu !== '' ? $no_hp_ibu : null,
        $pekerjaan_ibu !== '' ? $pekerjaan_ibu : null,
        $nama_wali !== '' ? $nama_wali : null,
        $alamat_orang_tua !== '' ? $alamat_orang_tua : null,
        $alamat !== '' ? $alamat : null,
        $status
    ]
);

if (!$stmt) {
    api_error('Gagal menyimpan data siswa.', 500);
}

$newId = db_last_id();

api_success(['id' => $newId], 'Siswa berhasil ditambahkan.');
