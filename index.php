<?php
require_once __DIR__ . '/config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['user'])) {
    header('Location: ' . APP_BASE . 'dashboard.php');
} else {
    header('Location: ' . APP_BASE . 'login.php');
}
exit;
