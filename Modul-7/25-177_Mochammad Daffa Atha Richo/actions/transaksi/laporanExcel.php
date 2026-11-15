<?php
require_once __DIR__ . '/laporanData.php';

while (ob_get_level() > 0) {
    ob_end_clean();
}

$mulai = $_GET['mulai'] ?? '';
$selesai = $_GET['selesai'] ?? '';

if (!tanggalValid($mulai) || !tanggalValid($selesai) || $mulai > $selesai) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Rentang tanggal tidak valid.';
    exit;
}

try {
    $laporan = laporanPenjualan($conn, $mulai, $selesai);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Laporan gagal dibuat.';
    exit;
}

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="laporan-penjualan_' . $mulai . '_' . $selesai . '.xls"');
header('Cache-Control: no-store');
// BOM UTF-8 agar Excel membaca huruf beraksen dengan benar.
echo "\xEF\xBB\xBF";
?>
<table border="1">
    <tr><th colspan="3">Laporan Penjualan</th></tr>
    <tr><td colspan="3">Periode <?= htmlspecialchars($mulai) ?> sampai <?= htmlspecialchars($selesai) ?></td></tr>
    <tr>
        <th>No</th>
        <th>Total Pendapatan</th>
        <th>Tanggal Transaksi</th>
    </tr>
    <?php $no = 1; foreach ($laporan['rows'] as $row): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= (int)$row['total_harian'] ?></td>
            <td><?= htmlspecialchars($row['tgl']) ?></td>
        </tr>
    <?php endforeach; ?>
    <tr>
        <td colspan="2">Total Pendapatan (Rp)</td>
        <td><?= $laporan['total_pendapatan'] ?></td>
    </tr>
    <tr>
        <td colspan="2">Pelanggan Unik</td>
        <td><?= $laporan['total_pelanggan'] ?></td>
    </tr>
</table>
