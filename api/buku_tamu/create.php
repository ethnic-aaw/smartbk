<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

require_role(['Admin', 'Guru BK']);

$input = get_json_input();

$tanggal = trim($input['tanggal'] ?? date('Y-m-d'));
$nama_tamu = trim($input['nama_tamu'] ?? '');
$keperluan = trim($input['keperluan'] ?? '');
$tindak_lanjut = trim($input['tindak_lanjut'] ?? '');

if ($tanggal === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
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
    'INSERT INTO buku_tamu (tanggal, nama_tamu, keperluan, tindak_lanjut, pencatat_id) VALUES (?, ?, ?, ?, ?)',
    [
        $tanggal,
        $nama_tamu,
        $keperluan,
        $tindak_lanjut !== '' ? $tindak_lanjut : null,
        (int) ($_SESSION['user']['id'] ?? 0) ?: null,
    ]
);

if (!$stmt) {
    api_error('Gagal menyimpan data tamu.', 500);
}

$newId = db_last_id();

api_success(['id' => $newId], 'Tamu berhasil dicatat.');
