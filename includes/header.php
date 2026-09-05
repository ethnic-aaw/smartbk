<?php
if (!defined('APP_BASE')) {
    require_once __DIR__ . '/../config/app.php';
}

if (!isset($pageTitle)) {
    $pageTitle = 'Smart BK';
}
if (!isset($activeMenu)) {
    $activeMenu = 'dashboard';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | Smart BK</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="manifest" href="<?= rtrim(APP_BASE, '/') ?>/manifest.webmanifest">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" href="<?= rtrim(APP_BASE, '/') ?>/assets/icons/icon-192.png">
    <link rel="stylesheet" href="<?= rtrim(APP_BASE, '/') ?>/assets/css/style.css?v=2">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">SB</div>
            <div>
                <h1>Smart BK</h1>
                <p>Pencatatan Pelanggaran Siswa</p>
            </div>
        </div>

        <nav class="nav-links">
            <?php
            if (!function_exists('svg_icon')) {
                require_once __DIR__ . '/functions.php';
            }
            $menuItems = [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'url' => rtrim(APP_BASE, '/') . '/dashboard.php', 'icon' => svg_icon('<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>')],
                ['key' => 'siswa', 'label' => 'Master Siswa', 'url' => rtrim(APP_BASE, '/') . '/siswa/index.php', 'icon' => svg_icon('<path d="M22 9L12 4 2 9l10 5 10-5z"></path><path d="M6 11v4c0 1.5 2.7 3 6 3s6-1.5 6-3v-4"></path>')],
                ['key' => 'user', 'label' => 'Master User', 'url' => rtrim(APP_BASE, '/') . '/user/index.php', 'icon' => svg_icon('<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>'), 'admin_only' => true],
                ['key' => 'kelas', 'label' => 'Master Kelas', 'url' => rtrim(APP_BASE, '/') . '/kelas/index.php', 'icon' => svg_icon('<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>'), 'admin_only' => true],
                ['key' => 'generate', 'label' => 'Generate Tahun Ajaran', 'url' => rtrim(APP_BASE, '/') . '/kelas/generate.php', 'icon' => svg_icon('<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>'), 'admin_only' => true],
                ['key' => 'pelanggaran_master', 'label' => 'Master Pelanggaran', 'url' => rtrim(APP_BASE, '/') . '/pelanggaran/master.php', 'icon' => svg_icon('<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>'), 'admin_only' => true],
                ['key' => 'pelanggaran_riwayat', 'label' => 'Riwayat Pelanggaran', 'url' => rtrim(APP_BASE, '/') . '/pelanggaran/riwayat.php', 'icon' => svg_icon('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>')],
                ['key' => 'buku_tamu', 'label' => 'Buku Tamu', 'url' => rtrim(APP_BASE, '/') . '/buku_tamu/index.php', 'icon' => svg_icon('<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>'), 'bk_only' => true],
                ['key' => 'konsultasi', 'label' => 'Konseling', 'url' => rtrim(APP_BASE, '/') . '/konsultasi/index.php', 'icon' => svg_icon('<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>'), 'bk_only' => true],
            ];
            $canManage = function_exists('can_see_all_data') ? can_see_all_data() : false;
            foreach ($menuItems as $item):
                $restricted = !empty($item['admin_only']) || !empty($item['bk_only']);
                if ($restricted && !$canManage) {
                    continue;
                }
            ?>
                <a href="<?= $item['url'] ?>" class="nav-item <?= $activeMenu === $item['key'] ? 'active' : '' ?>">
                    <span class="nav-icon"><?= $item['icon'] ?></span>
                    <span><?= $item['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="<?= rtrim(APP_BASE, '/') ?>/logout" class="nav-item logout-item">
                <span class="nav-icon"><?= svg_icon('<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>') ?></span>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <p class="eyebrow">Pencatatan Pelanggaran Siswa</p>
                <h2><?= htmlspecialchars($pageTitle) ?></h2>
            </div>
            <div class="topbar-actions">
                <div class="user-pill">
                    <div class="avatar"><?= strtoupper(substr($_SESSION['user']['name'], 0, 1)) ?></div>
                    <div>
                        <strong><?= htmlspecialchars($_SESSION['user']['name']) ?></strong>
                        <span><?= htmlspecialchars($_SESSION['user']['role']) ?><?php if (!empty($_SESSION['tahun_ajaran'])): ?> • <?= htmlspecialchars($_SESSION['tahun_ajaran']) ?><?php endif; ?></span>
                    </div>
                </div>
                <a href="<?= rtrim(APP_BASE, '/') ?>/logout" class="ghost-btn">Logout</a>
            </div>
        </header>

        <section class="page-content">
            <?php
            if (!empty($_SESSION['flash'])) {
                $flash = $_SESSION['flash'];
                unset($_SESSION['flash']);
                $flashType = $flash['type'] ?? 'success';
                $flashMsg = $flash['message'] ?? '';
                if ($flashMsg !== '') {
                    ?>
                    <div class="alert <?= htmlspecialchars($flashType) ?> flash-alert">
                        <span><?= htmlspecialchars($flashMsg) ?></span>
                        <button type="button" class="close" aria-label="Tutup">&times;</button>
                    </div>
                    <?php
                }
            }
            ?>
