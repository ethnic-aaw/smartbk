<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed', 405);
}

require_auth();

$search = trim($_GET['q'] ?? '');
$kelasFilter = (int) ($_GET['kelas'] ?? 0);
$tahunFilter = trim($_GET['tahun'] ?? $_SESSION['tahun_ajaran']);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 15);
$perPage = min(100, max(1, $perPage));

$where = ['1 = 1'];
$params = [];

if ($search !== '') {
    $where[] = '(s.nama LIKE ? OR s.nipd LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}
if ($kelasFilter > 0) {
    $where[] = 's.kelas_id = ?';
    $params[] = $kelasFilter;
}
if ($tahunFilter !== '') {
    $where[] = 'k.tahun_ajaran = ?';
    $params[] = $tahunFilter;
}

$whereSql = implode(' AND ', $where);

$total = db_fetch("SELECT COUNT(*) AS c FROM siswa s LEFT JOIN kelas k ON k.id = s.kelas_id WHERE $whereSql", $params, 'row');
$total = (int) ($total['c'] ?? 0);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listParams = array_merge($params, [$perPage, $offset]);
$siswaList = db_fetch(
    "SELECT s.*, k.nama_kelas, k.tahun_ajaran,
        COALESCE((SELECT SUM(j.bobot_poin)
                  FROM pelanggaran_siswa p
                  JOIN jenis_pelanggaran j ON j.id = p.jenis_pelanggaran_id
                  WHERE p.siswa_id = s.id), 0) AS total_poin
     FROM siswa s
     LEFT JOIN kelas k ON k.id = s.kelas_id
     WHERE $whereSql
     ORDER BY s.nama ASC
     LIMIT ? OFFSET ?",
    $listParams
);

api_success([
    'data' => $siswaList ?: [],
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => $totalPages
    ]
]);
