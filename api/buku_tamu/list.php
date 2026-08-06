<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed', 405);
}

require_role(['Admin', 'Guru BK']);

$search = trim($_GET['q'] ?? '');
$dari = trim($_GET['dari'] ?? '');
$sampai = trim($_GET['sampai'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 20);
$perPage = min(100, max(1, $perPage));

$where = ['1 = 1'];
$params = [];

if ($search !== '') {
    $where[] = '(t.nama_tamu LIKE ? OR t.keperluan LIKE ? OR t.tindak_lanjut LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($dari !== '') {
    $where[] = 't.tanggal >= ?';
    $params[] = $dari;
}
if ($sampai !== '') {
    $where[] = 't.tanggal <= ?';
    $params[] = $sampai;
}

$whereSql = implode(' AND ', $where);

$total = db_fetch("SELECT COUNT(*) AS c FROM buku_tamu t WHERE $whereSql", $params, 'row');
$total = (int) ($total['c'] ?? 0);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listParams = array_merge($params, [$perPage, $offset]);
$list = db_fetch(
    "SELECT t.*, u.nama AS pencatat
     FROM buku_tamu t
     LEFT JOIN users u ON u.id = t.pencatat_id
     WHERE $whereSql
     ORDER BY t.tanggal DESC, t.id DESC
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
