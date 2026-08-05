<?php
require_once __DIR__ . '/../index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_error('Method not allowed', 405);
}

$user = require_auth();

api_success([
    'user' => $user,
    'tahun_ajaran' => $_SESSION['tahun_ajaran'] ?? null
]);
