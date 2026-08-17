<?php
require_once __DIR__ . '/includes/session.php';
session_unset();
session_destroy();

require_once __DIR__ . '/config/app.php';
header('Location: ' . APP_BASE . 'login.php');
exit;
