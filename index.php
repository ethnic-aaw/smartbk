<?php
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/session.php';

if (!empty($_SESSION['user'])) {
    header('Location: ' . APP_BASE . 'dashboard.php');
} else {
    header('Location: ' . APP_BASE . 'login.php');
}
exit;
