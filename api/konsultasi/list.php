<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed', 405);
}

require_role(['Admin', 'Guru BK']);

$siswa_id = (int) ($_GET['siswa_id'] ?? 0);
$search = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 20);
$perPage = min(100, max(1, $perPage));

$where = ['1 = 1'];
$params = [];

if ($siswa_id > 0) {
    $where[] = 'k.siswa_id = ?';
    $params[] = $siswa_id;
}
if ($search !== '') {
    $where[] = '(s.nama LIKE ? OR k.permasalahan LIKE ? OR k.tindak_lanjut LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(' AND ', $where);

$total = db_fetch("SELECT COUNT(*) AS c FROM konsultasi_siswa k JOIN siswa s ON s.id = k.siswa_id WHERE $whereSql", $params, 'row');
$total = (int) ($total['c'] ?? 0);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listParams = array_merge($params, [$perPage, $offset]);
$list = db_fetch(
    "SELECT k.*, s.nama AS siswa_nama, s.nipd, kl.nama_kelas, u.nama AS konselor
     FROM konsultasi_siswa k
     JOIN siswa s ON s.id = k.siswa_id
     LEFT JOIN kelas kl ON kl.id = s.kelas_id
     LEFT JOIN users u ON u.id = k.konselor_id
     WHERE $whereSql
     ORDER BY k.tanggal DESC, k.id DESC
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
