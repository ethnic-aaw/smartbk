<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed', 405);
}

require_role(['Admin']);

$tahunFilter = trim($_GET['tahun'] ?? $_SESSION['tahun_ajaran']);

$kelasList = db_fetch(
    'SELECT k.*, u.nama AS wali_kelas,
            (SELECT COUNT(*) FROM siswa s WHERE s.kelas_id = k.id) AS jumlah_siswa
     FROM kelas k
     LEFT JOIN users u ON u.id = k.wali_kelas_id
     WHERE k.tahun_ajaran = ?
     ORDER BY k.nama_kelas ASC',
    [$tahunFilter]
);

api_success([
    'data' => $kelasList ?: []
]);
