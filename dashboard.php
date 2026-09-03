<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Dashboard';
$activeMenu = 'dashboard';

require_once __DIR__ . '/includes/header.php';
?>

<div id="react-dashboard" data-base="<?= htmlspecialchars(rtrim(APP_BASE, '/')) ?>" style="min-height: 80vh;"></div>
<script>window.APP_BASE = document.getElementById('react-dashboard').dataset.base || "";</script>

<link rel="stylesheet" href="<?= rtrim(APP_BASE, '/') ?>/orbit/dist/dash/assets/dashboard.css">
<script src="<?= rtrim(APP_BASE, '/') ?>/orbit/dist/dash/dashboard.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
