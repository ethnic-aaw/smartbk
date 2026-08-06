<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed', 405);
}

require_auth();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    api_error('ID siswa tidak valid.');
}

$siswa = db_fetch(
    'SELECT s.*, k.nama_kelas, k.tahun_ajaran,
            COALESCE((SELECT SUM(j.bobot_poin)
                      FROM pelanggaran_siswa p
                      JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
                      WHERE p.siswa_id = s.id), 0) AS total_poin
     FROM siswa s
     LEFT JOIN kelas k ON k.id = s.kelas_id
     WHERE s.id = ?
     LIMIT 1',
    [$id],
    'row'
);

if (!$siswa) {
    api_error('Siswa tidak ditemukan.', 404);
}

$scopeKelasId = current_kelas_scope();
if ($scopeKelasId && (int) $siswa['kelas_id'] !== $scopeKelasId) {
    api_error('Anda hanya bisa melihat siswa di kelas Anda.', 403);
}

$riwayat = db_fetch(
    'SELECT p.*, j.nama AS jenis_pelanggaran, j.bobot_poin, j.kategori,
            u.nama AS pelapor
     FROM pelanggaran_siswa p
     JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
     LEFT JOIN users u ON u.id = p.pelapor_id
     WHERE p.siswa_id = ?
     ORDER BY p.tanggal DESC, p.created_at DESC',
    [$id]
);

api_success([
    'siswa' => $siswa,
    'riwayat_pelanggaran' => $riwayat ?: []
]);
