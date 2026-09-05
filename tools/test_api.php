<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: text/plain');
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';
$output = ob_get_clean();
echo "Output before code: [" . strlen($output) . " bytes]\n";
if (strlen($output) > 0) {
    echo "CONTENT: " . substr($output, 0, 300) . "\n";
} else {
    echo "CLEAN\n";
}
echo "php_sapi: " . php_sapi_name() . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
