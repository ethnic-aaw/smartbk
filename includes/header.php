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
    <link rel="stylesheet" href="<?= rtrim(APP_BASE, '/') ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">SB</div>
            <div>
                <h1>Smart BK</h1>
                <p>Sistem BK sekolah</p>
            </div>
        </div>

        <nav class="nav-links">
            <?php
            $menuItems = [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'url' => rtrim(APP_BASE, '/') . '/dashboard.php', 'icon' => '◉'],
                ['key' => 'siswa', 'label' => 'Master Siswa', 'url' => rtrim(APP_BASE, '/') . '/siswa/index.php', 'icon' => '◌'],
                ['key' => 'user', 'label' => 'Master User', 'url' => rtrim(APP_BASE, '/') . '/user/index.php', 'icon' => '◌', 'admin_only' => true],
                ['key' => 'kelas', 'label' => 'Master Kelas', 'url' => rtrim(APP_BASE, '/') . '/kelas/index.php', 'icon' => '◌', 'admin_only' => true],
                ['key' => 'generate', 'label' => 'Generate Tahun Ajaran', 'url' => rtrim(APP_BASE, '/') . '/kelas/generate.php', 'icon' => '↗', 'admin_only' => true],
                ['key' => 'pelanggaran_master', 'label' => 'Master Pelanggaran', 'url' => rtrim(APP_BASE, '/') . '/pelanggaran/master.php', 'icon' => '◌', 'admin_only' => true],
                ['key' => 'pelanggaran_riwayat', 'label' => 'Riwayat Pelanggaran', 'url' => rtrim(APP_BASE, '/') . '/pelanggaran/riwayat.php', 'icon' => '◌'],
                ['key' => 'buku_tamu', 'label' => 'Buku Tamu', 'url' => rtrim(APP_BASE, '/') . '/buku_tamu/index.php', 'icon' => '☰', 'bk_only' => true],
                ['key' => 'konsultasi', 'label' => 'Konsultasi Siswa', 'url' => rtrim(APP_BASE, '/') . '/konsultasi/index.php', 'icon' => '✉', 'bk_only' => true],
            ];
            $userRole = $_SESSION['user']['role'] ?? '';
            foreach ($menuItems as $item):
                if (isset($item['admin_only']) && $item['admin_only'] && $userRole !== 'Admin') {
                    continue;
                }
                if (isset($item['bk_only']) && $item['bk_only'] && !in_array($userRole, ['Admin', 'Guru BK'], true)) {
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
            <a href="<?= rtrim(APP_BASE, '/') ?>/logout.php" class="nav-item logout-item">
                <span class="nav-icon">↺</span>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <p class="eyebrow">Sistem Informasi Bimbingan Konseling</p>
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
                <a href="<?= rtrim(APP_BASE, '/') ?>/logout.php" class="ghost-btn">Logout</a>
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
