<?php
header('Content-Type: application/json; charset=utf-8');
// Restrict CORS to same-origin only. Wildcard removed so the session cookie
// is never exposed to attacker-controlled origins.
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '');
if ($origin === '' || $origin === $host) {
    if ($origin !== '') {
        header('Access-Control-Allow-Origin: ' . $origin);
    }
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
    header('Vary: Origin');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';

function api_response($data, $code = 200)
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function api_error($message, $code = 400)
{
    api_response(['success' => false, 'error' => $message], $code);
}

function api_success($data = [], $message = '')
{
    $response = ['success' => true];
    if ($message !== '') {
        $response['message'] = $message;
    }
    if (!empty($data)) {
        $response['data'] = $data;
    }
    api_response($response);
}

function require_auth()
{
    if (empty($_SESSION['user']) || empty($_SESSION['tahun_ajaran'])) {
        api_error('Unauthorized. Please login first.', 401);
    }
    return $_SESSION['user'];
}

function require_role($allowedRoles = [])
{
    $user = require_auth();
    if (!empty($allowedRoles) && !in_array($user['role'], $allowedRoles, true)) {
        api_error('Forbidden. Insufficient permissions.', 403);
    }
    return $user;
}

function get_json_input()
{
    $input = file_get_contents('php://input');
    if ($input === false || $input === '') {
        return [];
    }
    $data = json_decode($input, true);
    return is_array($data) ? $data : [];
}

// Hanya tampilkan info endpoint ketika api/index.php diakses langsung
// (bukan ketika di-require oleh file endpoint lain)
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    api_response([
        'name' => 'Smart BK API',
        'version' => '1.0',
        'endpoints' => [
            'POST /api/auth/login.php' => 'Login',
            'POST /api/auth/logout.php' => 'Logout',
            'GET /api/auth/check.php' => 'Check auth status',
            'GET /api/dashboard/stats.php' => 'Dashboard statistics',
            'GET /api/siswa/list.php' => 'List siswa',
            'GET /api/siswa/detail.php?id={id}' => 'Detail siswa',
            'POST /api/siswa/create.php' => 'Create siswa',
            'PUT /api/siswa/update.php?id={id}' => 'Update siswa',
            'DELETE /api/siswa/delete.php?id={id}' => 'Delete siswa',
            'GET /api/user/list.php' => 'List users',
            'POST /api/user/create.php' => 'Create user',
            'PUT /api/user/update.php?id={id}' => 'Update user',
            'DELETE /api/user/delete.php?id={id}' => 'Delete user',
            'GET /api/kelas/list.php' => 'List kelas',
            'POST /api/kelas/create.php' => 'Create kelas',
            'PUT /api/kelas/update.php?id={id}' => 'Update kelas',
            'DELETE /api/kelas/delete.php?id={id}' => 'Delete kelas',
            'GET /api/pelanggaran/jenis.php' => 'List jenis pelanggaran',
            'POST /api/pelanggaran/jenis_create.php' => 'Create jenis pelanggaran',
            'PUT /api/pelanggaran/jenis_update.php?id={id}' => 'Update jenis pelanggaran',
            'DELETE /api/pelanggaran/jenis_delete.php?id={id}' => 'Delete jenis pelanggaran',
            'GET /api/pelanggaran/list.php' => 'List pelanggaran siswa',
            'POST /api/pelanggaran/create.php' => 'Create pelanggaran',
            'DELETE /api/pelanggaran/delete.php?id={id}' => 'Delete pelanggaran',
            'GET /api/buku_tamu/list.php' => 'List buku tamu',
            'POST /api/buku_tamu/create.php' => 'Create buku tamu',
            'PUT /api/buku_tamu/update.php?id={id}' => 'Update buku tamu',
            'DELETE /api/buku_tamu/delete.php?id={id}' => 'Delete buku tamu',
            'GET /api/konsultasi/list.php' => 'List konseling',
            'POST /api/konsultasi/create.php' => 'Create konseling',
            'PUT /api/konsultasi/update.php?id={id}' => 'Update konseling',
            'DELETE /api/konsultasi/delete.php?id={id}' => 'Delete konseling'
        ]
    ]);
}
