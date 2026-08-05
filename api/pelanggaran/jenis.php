<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed', 405);
}

require_auth();

$list = db_fetch('SELECT * FROM jenis_pelanggaran ORDER BY bobot_poin DESC, kode ASC');

api_success([
    'data' => $list ?: []
]);
