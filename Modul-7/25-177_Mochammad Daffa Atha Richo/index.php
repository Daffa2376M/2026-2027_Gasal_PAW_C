<?php
ob_start();
require_once __DIR__ . '/conn.php';
$url = isset($_GET['url']) && $_GET['url'] !== '' ? $_GET['url'] : '/';

$judulHalaman = [
    '/' => 'Beranda',
    '/transaksi' => 'Daftar Transaksi',
    '/transaksi/add' => 'Tambah Transaksi',
    '/transaksi/edit' => 'Ubah Transaksi',
    '/transaksi/laporan' => 'Laporan Penjualan',
    '/transaksiDetail' => 'Detail Transaksi',
    '/transaksiDetail/add' => 'Tambah Detail Transaksi',
    '/transaksiDetail/edit' => 'Ubah Detail Transaksi',
    '/barang' => 'Daftar Barang',
    '/barang/add' => 'Tambah Barang',
    '/barang/edit' => 'Ubah Barang',
];
$judul = ($judulHalaman[$url] ?? 'Halaman Tidak Ditemukan') . ' - Aplikasi Penjualan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($judul) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav aria-label="Navigasi utama">
            <a href="?url=/"<?= $url === '/' ? ' aria-current="page"' : '' ?>>Home</a> |
            <a href="?url=/transaksi"<?= $url === '/transaksi' || strpos($url, '/transaksi/') === 0 && strpos($url, '/transaksi/laporan') !== 0 ? ' aria-current="page"' : '' ?>>Transaksi</a> |
            <a href="?url=/transaksi/laporan"<?= $url === '/transaksi/laporan' ? ' aria-current="page"' : '' ?>>Laporan</a> |
            <a href="?url=/transaksiDetail"<?= strpos($url, '/transaksiDetail') === 0 ? ' aria-current="page"' : '' ?>>Detail Transaksi</a> |
            <a href="?url=/barang"<?= strpos($url, '/barang') === 0 ? ' aria-current="page"' : '' ?>>Barang</a>
        </nav>
    </header>
    <main>
		<?php
			require __DIR__ . '/proses.php';
		?>
    </main>
</body>
</html>
