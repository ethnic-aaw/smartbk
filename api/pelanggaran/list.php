<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed', 405);
}

require_auth();

$siswa_id = (int) ($_GET['siswa_id'] ?? 0);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 20);
$perPage = min(100, max(1, $perPage));

$where = ['1 = 1'];
$params = [];

if ($siswa_id > 0) {
    $where[] = 'p.siswa_id = ?';
    $params[] = $siswa_id;
}

$whereSql = implode(' AND ', $where);

$total = db_fetch("SELECT COUNT(*) AS c FROM pelanggaran_siswa p WHERE $whereSql", $params, 'row');
$total = (int) ($total['c'] ?? 0);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listParams = array_merge($params, [$perPage, $offset]);

$list = db_fetch(
    "SELECT p.*, 
            s.nama AS siswa_nama, s.nipd, s.foto,
            k.nama_kelas,
            j.nama AS jenis_pelanggaran, j.bobot_poin, j.kategori, j.kode,
            u.nama AS pelapor
     FROM pelanggaran_siswa p
     JOIN siswa s ON s.id = p.siswa_id
     LEFT JOIN kelas k ON k.id = s.kelas_id
     JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
     LEFT JOIN users u ON u.id = p.pelapor_id
     WHERE $whereSql
     ORDER BY p.tanggal DESC, p.created_at DESC
     LIMIT ? OFFSET ?",
    $listParams
);

api_success([
    'data' => $list ?: [],
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages
    ]
]);
