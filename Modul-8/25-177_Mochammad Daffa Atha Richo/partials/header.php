<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

$pageTitle = $pageTitle ?? 'Sistem Penjualan';
if (!isset($activeMenu)) {
    $script = basename((string) ($_SERVER['PHP_SELF'] ?? ''));
    $activeMenu = match ($script) {
        'index.php' => 'home',
        'master.php', 'master_form.php', 'master_hapus.php' => 'master',
        'transaksi.php' => 'transaksi',
        'laporan.php' => 'laporan',
        default => '',
    };
}

$user = currentUser();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php if ($user !== null): ?>
    <header class="navbar no-print">
        <div class="navbar-inner">
            <a class="navbar-brand" href="index.php">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span>Sistem Penjualan</span>
            </a>

            <nav class="navbar-nav" aria-label="Navigasi utama">
                <a class="nav-link<?= $activeMenu === 'home' ? ' active' : '' ?>" href="index.php">Home</a>

                <?php if ($user['level'] === 1): ?>
                    <div class="dropdown" data-dropdown>
                        <button type="button"
                                class="nav-link dropdown-toggle<?= $activeMenu === 'master' ? ' active' : '' ?>"
                                aria-expanded="false"
                                aria-haspopup="true">
                            Data Master <span class="caret" aria-hidden="true">&#9662;</span>
                        </button>
                        <div class="dropdown-menu" role="menu">
                            <a class="dropdown-item" role="menuitem" href="master.php?t=barang">Data Barang</a>
                            <a class="dropdown-item" role="menuitem" href="master.php?t=supplier">Data Supplier</a>
                            <a class="dropdown-item" role="menuitem" href="master.php?t=pelanggan">Data Pelanggan</a>
                            <a class="dropdown-item" role="menuitem" href="master.php?t=user">Data User</a>
                        </div>
                    </div>
                <?php endif; ?>

                <a class="nav-link<?= $activeMenu === 'transaksi' ? ' active' : '' ?>" href="transaksi.php">Transaksi</a>
                <a class="nav-link<?= $activeMenu === 'laporan' ? ' active' : '' ?>" href="laporan.php">Laporan</a>
            </nav>

            <div class="navbar-right">
                <div class="dropdown dropdown-right" data-dropdown>
                    <button type="button"
                            class="nav-link dropdown-toggle user-toggle"
                            aria-expanded="false"
                            aria-haspopup="true">
                        <?= e($user['nama']) ?> <span class="caret" aria-hidden="true">&#9662;</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" role="menu">
                        <div class="dropdown-header">
                            <?= e($user['level'] === 1 ? 'Owner (Level 1)' : 'Kasir (Level 2)') ?>
                        </div>
                        <a class="dropdown-item dropdown-item-danger" role="menuitem" href="logout.php">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </header>
<?php endif; ?>

<main class="container">
<?php if ($flash !== null): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" role="alert">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>
