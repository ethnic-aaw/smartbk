<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

require_role(['Admin', 'Guru BK']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    api_error('ID buku tamu tidak valid.');
}

$existing = db_fetch('SELECT * FROM buku_tamu WHERE id = ? LIMIT 1', [$id], 'row');
if (!$existing) {
    api_error('Data tamu tidak ditemukan.', 404);
}

$input = get_json_input();

$tanggal = trim($input['tanggal'] ?? $existing['tanggal']);
$nama_tamu = trim($input['nama_tamu'] ?? $existing['nama_tamu']);
$keperluan = trim($input['keperluan'] ?? $existing['keperluan']);
$tindak_lanjut = trim($input['tindak_lanjut'] ?? $existing['tindak_lanjut']);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    api_error('Format tanggal tidak valid (gunakan YYYY-MM-DD).');
}
if ($nama_tamu === '') {
    api_error('Nama tamu wajib diisi.');
}
if (strlen($nama_tamu) > 150) {
    api_error('Nama tamu maksimal 150 karakter.');
}
if ($keperluan === '') {
    api_error('Keperluan wajib diisi.');
}

$stmt = db_query(
    'UPDATE buku_tamu SET tanggal = ?, nama_tamu = ?, keperluan = ?, tindak_lanjut = ? WHERE id = ?',
    [
        $tanggal,
        $nama_tamu,
        $keperluan,
        $tindak_lanjut !== '' ? $tindak_lanjut : null,
        $id,
    ]
);

if (!$stmt) {
    api_error('Gagal mengupdate data tamu.', 500);
}

api_success(['id' => $id], 'Data tamu berhasil diperbarui.');
