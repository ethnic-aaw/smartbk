<?php
require_once __DIR__ . '/app.php';

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'smart_bk';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

$dbError = null;

try {
    $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    if ($mysqli->connect_errno) {
        throw new mysqli_sql_exception($mysqli->connect_error);
    }
    $mysqli->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    $mysqli = null;
    $dbError = 'Koneksi database belum tersedia. Siapkan server MySQL lalu impor sql/smart_bk.sql.';
}

function db_connect()
{
    global $mysqli;
    return $mysqli;
}

function db_is_ready()
{
    global $mysqli, $dbError;
    return isset($mysqli) && !$dbError && !$mysqli->connect_errno;
}

function db_error()
{
    global $dbError;
    return $dbError;
}

function db_query($sql, $params = [])
{
    global $mysqli;
    if (!db_is_ready()) {
        return false;
    }
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        return false;
    }
    if ($params) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        $refs = [];
        foreach ($params as $key => $value) {
            $refs[$key] = &$params[$key];
        }
        array_unshift($refs, $types);
        array_unshift($refs, $stmt);
        call_user_func_array('mysqli_stmt_bind_param', $refs);
    }
    $stmt->execute();
    return $stmt;
}

function db_fetch($sql, $params = [], $mode = 'assoc')
{
    global $mysqli;
    $stmt = db_query($sql, $params);
    if (!$stmt) {
        return false;
    }
    $result = $stmt->get_result();
    if (!$result) {
        return false;
    }
    if ($mode === 'row') {
        return $result->fetch_assoc();
    }
    return $result->fetch_all(MYSQLI_ASSOC);
}

function db_last_id()
{
    global $mysqli;
    return $mysqli ? (int) $mysqli->insert_id : 0;
}
