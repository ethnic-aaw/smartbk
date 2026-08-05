<?php
if (!defined('APP_BASE')) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = strpos($scriptName, '/smartbk/') !== false ? '/smartbk/' : '/';
    define('APP_BASE', $basePath);
}
