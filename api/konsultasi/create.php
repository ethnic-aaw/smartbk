<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

$user = require_role(['Admin', 'Guru BK']);

$input = get_json_input();

$siswa_id = (int) ($input['siswa_id'] ?? 0);
$tanggal = trim($input['tanggal'] ?? date('Y-m-d'));
$permasalahan = trim($input['permasalahan'] ?? '');
$tindak_lanjut = trim($input['tindak_lanjut'] ?? '');

if ($siswa_id <= 0) {
    api_error('Siswa harus dipilih.');
}
$siswa = db_fetch('SELECT id FROM siswa WHERE id = ? LIMIT 1', [$siswa_id], 'row');
if (!$siswa) {
    api_error('Siswa tidak ditemukan.');
}
if ($tanggal === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    api_error('Format tanggal tidak valid (gunakan YYYY-MM-DD).');
}
if ($permasalahan === '') {
    api_error('Permasalahan wajib diisi.');
}

$stmt = db_query(
    'INSERT INTO konsultasi_siswa (siswa_id, tanggal, permasalahan, tindak_lanjut, konselor_id) VALUES (?, ?, ?, ?, ?)',
    [
        $siswa_id,
        $tanggal,
        $permasalahan,
        $tindak_lanjut !== '' ? $tindak_lanjut : null,
        (int) ($_SESSION['user']['id'] ?? 0) ?: null,
    ]
);

if (!$stmt) {
    api_error('Gagal menyimpan konsultasi.', 500);
}

$newId = db_last_id();

api_success(['id' => $newId], 'Konsultasi berhasil dicatat.');
