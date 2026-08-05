<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

$user = require_role(['Admin', 'Guru BK']);

$input = get_json_input();

$siswa_id = (int) ($input['siswa_id'] ?? 0);
$jenis_pelanggaran_id = (int) ($input['jenis_pelanggaran_id'] ?? 0);
$tanggal = trim($input['tanggal'] ?? date('Y-m-d'));
$lokasi = trim($input['lokasi'] ?? '');
$keterangan = trim($input['keterangan'] ?? '');
$tindakan = trim($input['tindakan'] ?? '');

if ($siswa_id <= 0) {
    api_error('Siswa harus dipilih.');
}
if ($jenis_pelanggaran_id <= 0) {
    api_error('Jenis pelanggaran harus dipilih.');
}

$siswa = db_fetch('SELECT id FROM siswa WHERE id = ? LIMIT 1', [$siswa_id], 'row');
if (!$siswa) {
    api_error('Siswa tidak ditemukan.');
}

$jenis = db_fetch('SELECT id FROM jenis_pelanggaran WHERE id = ? LIMIT 1', [$jenis_pelanggaran_id], 'row');
if (!$jenis) {
    api_error('Jenis pelanggaran tidak ditemukan.');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    api_error('Format tanggal tidak valid (gunakan YYYY-MM-DD).');
}

$stmt = db_query(
    'INSERT INTO pelanggaran_siswa (siswa_id, jenis_pelanggaran_id, tanggal, lokasi, keterangan, tindakan, pelapor_id) VALUES (?, ?, ?, ?, ?, ?, ?)',
    [
        $siswa_id,
        $jenis_pelanggaran_id,
        $tanggal,
        $lokasi !== '' ? $lokasi : null,
        $keterangan !== '' ? $keterangan : null,
        $tindakan !== '' ? $tindakan : null,
        $user['id']
    ]
);

if (!$stmt) {
    api_error('Gagal menyimpan pelanggaran siswa.', 500);
}

$newId = db_last_id();

api_success(['id' => $newId], 'Pelanggaran siswa berhasil dicatat.');
