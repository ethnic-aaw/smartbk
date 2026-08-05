<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_error('Method not allowed', 405);
}

require_role(['Admin', 'Guru BK']);

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    api_error('ID konsultasi tidak valid.');
}

$existing = db_fetch('SELECT * FROM konsultasi_siswa WHERE id = ? LIMIT 1', [$id], 'row');
if (!$existing) {
    api_error('Konsultasi tidak ditemukan.', 404);
}

$input = get_json_input();

$tanggal = trim($input['tanggal'] ?? $existing['tanggal']);
$permasalahan = trim($input['permasalahan'] ?? $existing['permasalahan']);
$tindak_lanjut = trim($input['tindak_lanjut'] ?? $existing['tindak_lanjut']);

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    api_error('Format tanggal tidak valid (gunakan YYYY-MM-DD).');
}
if ($permasalahan === '') {
    api_error('Permasalahan wajib diisi.');
}

$stmt = db_query(
    'UPDATE konsultasi_siswa SET tanggal = ?, permasalahan = ?, tindak_lanjut = ? WHERE id = ?',
    [
        $tanggal,
        $permasalahan,
        $tindak_lanjut !== '' ? $tindak_lanjut : null,
        $id,
    ]
);

if (!$stmt) {
    api_error('Gagal mengupdate konsultasi.', 500);
}

api_success(['id' => $id], 'Konsultasi berhasil diperbarui.');
