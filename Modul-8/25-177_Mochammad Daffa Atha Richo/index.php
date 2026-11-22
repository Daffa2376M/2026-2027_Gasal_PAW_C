<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/lib/helpers.php';

requireLogin();

$user = currentUser();
assert($user !== null);

$pdo = getPDO();
$isOwner = ($user['level'] === 1);
$roleLabel = $isOwner ? 'Owner' : 'Kasir';

$totalBarang = (int) $pdo->query('SELECT COUNT(*) FROM barang')->fetchColumn();
$totalPelanggan = (int) $pdo->query('SELECT COUNT(*) FROM pelanggan')->fetchColumn();
$totalTransaksi = (int) $pdo->query('SELECT COUNT(*) FROM transaksi')->fetchColumn();

$totalSupplier = 0;
$totalUser = 0;
if ($isOwner) {
    $totalSupplier = (int) $pdo->query('SELECT COUNT(*) FROM supplier')->fetchColumn();
    $totalUser = (int) $pdo->query('SELECT COUNT(*) FROM `user`')->fetchColumn();
}

$recentTransaksi = $pdo->query(
    'SELECT t.id, t.waktu_transaksi, p.nama AS nama_pelanggan, t.keterangan, t.total
     FROM transaksi t
     INNER JOIN pelanggan p ON p.id = t.pelanggan_id
     ORDER BY t.waktu_transaksi DESC, t.id DESC
     LIMIT 5'
)->fetchAll();

$pageTitle = 'Home - Sistem Penjualan';
$activeMenu = 'home';
require __DIR__ . '/partials/header.php';
?>

<section class="card">
    <div class="welcome-header">
        <div>
            <h1 class="page-title">Selamat Datang, <?= e($user['nama']) ?></h1>
            <p class="welcome-meta">
                Username: <strong><?= e($user['username']) ?></strong>
                &bull;
                Hak Akses: Level <?= (int) $user['level'] ?> (<?= e($roleLabel) ?>)
            </p>
        </div>
        <span class="badge <?= $isOwner ? 'badge-owner' : 'badge-kasir' ?>">
            <?= e($roleLabel) ?>
        </span>
    </div>
</section>

<section class="quick-grid" aria-label="Ringkasan dan Navigasi Cepat">
    <?php if ($isOwner): ?>
        <a class="quick-card" href="master.php?t=barang">
            <span class="quick-label">Data Barang</span>
            <span class="quick-value"><?= $totalBarang ?></span>
            <span class="quick-link">Kelola Data Barang &rarr;</span>
        </a>
        <a class="quick-card" href="master.php?t=supplier">
            <span class="quick-label">Data Supplier</span>
            <span class="quick-value"><?= $totalSupplier ?></span>
            <span class="quick-link">Kelola Data Supplier &rarr;</span>
        </a>
        <a class="quick-card" href="master.php?t=pelanggan">
            <span class="quick-label">Data Pelanggan</span>
            <span class="quick-value"><?= $totalPelanggan ?></span>
            <span class="quick-link">Kelola Data Pelanggan &rarr;</span>
        </a>
        <a class="quick-card" href="master.php?t=user">
            <span class="quick-label">Data User</span>
            <span class="quick-value"><?= $totalUser ?></span>
            <span class="quick-link">Kelola Data User &rarr;</span>
        </a>
    <?php endif; ?>

    <a class="quick-card" href="transaksi.php">
        <span class="quick-label">Transaksi</span>
        <span class="quick-value"><?= $totalTransaksi ?></span>
        <span class="quick-link">Buka Halaman Transaksi &rarr;</span>
    </a>

    <a class="quick-card" href="laporan.php">
        <span class="quick-label">Laporan Penjualan</span>
        <span class="quick-value"><?= $totalPelanggan ?> Pelanggan</span>
        <span class="quick-link">Lihat Laporan &amp; Grafik &rarr;</span>
    </a>
</section>

<section class="card">
    <h2 class="section-title">Transaksi Terbaru</h2>
    <?php if ($recentTransaksi === []): ?>
        <p class="pesan-kosong">Belum ada data transaksi.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Keterangan</th>
                        <th class="kolom-angka">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransaksi as $trx): ?>
                        <tr>
                            <td>#<?= (int) $trx['id'] ?></td>
                            <td><?= e($trx['waktu_transaksi']) ?></td>
                            <td><?= e($trx['nama_pelanggan']) ?></td>
                            <td><?= e($trx['keterangan'] ?? '-') ?></td>
                            <td class="kolom-angka"><?= e(rupiah($trx['total'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php
require __DIR__ . '/partials/footer.php';
