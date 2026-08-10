<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

require_role(['Admin']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    api_error('ID siswa tidak valid.');
}

$existing = db_fetch('SELECT * FROM siswa WHERE id = ? LIMIT 1', [$id], 'row');
if (!$existing) {
    api_error('Siswa tidak ditemukan.', 404);
}

$input = get_json_input();

$nipd = trim($input['nipd'] ?? $existing['nipd']);
$nama = trim($input['nama'] ?? $existing['nama']);
$jenis_kelamin = trim($input['jenis_kelamin'] ?? $existing['jenis_kelamin']);
$kelas_id = isset($input['kelas_id']) ? (int) $input['kelas_id'] : (int) $existing['kelas_id'];
$tempat_lahir = trim($input['tempat_lahir'] ?? $existing['tempat_lahir']);
$tanggal_lahir = trim($input['tanggal_lahir'] ?? $existing['tanggal_lahir']);
$nama_orang_tua = trim($input['nama_orang_tua'] ?? $existing['nama_orang_tua']);
$no_hp_orang_tua = trim($input['no_hp_orang_tua'] ?? $existing['no_hp_orang_tua']);
$nama_ayah = trim($input['nama_ayah'] ?? $existing['nama_ayah']);
$no_hp_ayah = trim($input['no_hp_ayah'] ?? $existing['no_hp_ayah']);
$pekerjaan_ayah = trim($input['pekerjaan_ayah'] ?? $existing['pekerjaan_ayah']);
$nama_ibu = trim($input['nama_ibu'] ?? $existing['nama_ibu']);
$no_hp_ibu = trim($input['no_hp_ibu'] ?? $existing['no_hp_ibu']);
$pekerjaan_ibu = trim($input['pekerjaan_ibu'] ?? $existing['pekerjaan_ibu']);
$nama_wali = trim($input['nama_wali'] ?? $existing['nama_wali']);
$alamat_orang_tua = trim($input['alamat_orang_tua'] ?? $existing['alamat_orang_tua']);
$alamat = trim($input['alamat'] ?? $existing['alamat']);
$status = trim($input['status'] ?? $existing['status']);

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

if ($nipd !== $existing['nipd']) {
    $dup = db_fetch('SELECT id FROM siswa WHERE nipd = ? AND id != ? LIMIT 1', [$nipd, $id], 'row');
    if ($dup) {
        api_error('NIPD/NIS sudah digunakan siswa lain.');
    }
}

if ($kelas_id > 0) {
    $kelas = db_fetch('SELECT id FROM kelas WHERE id = ? LIMIT 1', [$kelas_id], 'row');
    if (!$kelas) {
        api_error('Kelas tidak ditemukan.');
    }
}

$stmt = db_query(
    'UPDATE siswa SET nipd = ?, nama = ?, jenis_kelamin = ?, kelas_id = ?, tempat_lahir = ?, tanggal_lahir = ?, nama_orang_tua = ?, no_hp_orang_tua = ?, nama_ayah = ?, no_hp_ayah = ?, pekerjaan_ayah = ?, nama_ibu = ?, no_hp_ibu = ?, pekerjaan_ibu = ?, nama_wali = ?, alamat_orang_tua = ?, alamat = ?, status = ? WHERE id = ?',
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
        $status,
        $id
    ]
);

if (!$stmt) {
    api_error('Gagal mengupdate data siswa.', 500);
}

api_success(['id' => $id], 'Siswa berhasil diperbarui.');
