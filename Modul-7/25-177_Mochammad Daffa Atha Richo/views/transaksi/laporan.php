<?php
require_once __DIR__ . '/../../actions/transaksi/laporanData.php';

$mulai = $_GET['mulai'] ?? date('Y-m-d', strtotime('-29 days'));
$selesai = $_GET['selesai'] ?? date('Y-m-d');
$pesanError = null;
$laporan = null;

if (!tanggalValid($mulai) || !tanggalValid($selesai) || $mulai > $selesai) {
    $pesanError = 'Rentang tanggal tidak valid.';
} else {
    try {
        $laporan = laporanPenjualan($conn, $mulai, $selesai);
    } catch (Exception $e) {
        $pesanError = 'Laporan gagal dimuat. Periksa koneksi database.';
    }
}
?>
<h1>Laporan Penjualan</h1>

<form method="GET" class="no-print filter-laporan">
    <input type="hidden" name="url" value="/transaksi/laporan">
    <label>Dari
        <input type="date" name="mulai" value="<?= htmlspecialchars($mulai) ?>" required>
    </label>
    <label>Sampai
        <input type="date" name="selesai" value="<?= htmlspecialchars($selesai) ?>" required>
    </label>
    <button type="submit">Tampilkan</button>
</form>

<?php if ($pesanError): ?>
    <p class="pesan-error" role="alert"><?= htmlspecialchars($pesanError) ?></p>
<?php else: ?>
    <p class="periode-laporan">Periode <?= htmlspecialchars($mulai) ?> sampai <?= htmlspecialchars($selesai) ?></p>
    <div class="aksi-laporan no-print">
        <button type="button" onclick="window.print()">Cetak</button>
        <a href="?url=/transaksi/laporan/excel&amp;mulai=<?= urlencode($mulai) ?>&amp;selesai=<?= urlencode($selesai) ?>">Unduh Excel</a>
    </div>

    <?php if (!$laporan['rows']): ?>
        <p class="pesan-kosong">Tidak ada transaksi pada rentang tanggal ini.</p>
    <?php else: ?>
        <h2>Grafik Penjualan Harian</h2>
        <div class="kotak-grafik">
            <canvas id="grafikPenjualan" role="img" aria-label="Grafik total penjualan harian. Angka lengkap tersedia di tabel rekapitulasi."></canvas>
        </div>

        <h2>Rekapitulasi</h2>
        <div class="gulir-tabel">
            <table>
                <thead>
                    <tr><th>No</th><th class="kolom-angka">Total Pendapatan</th><th>Tanggal Transaksi</th></tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($laporan['rows'] as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="kolom-angka">Rp <?= number_format($row['total_harian'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($row['tgl']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="ringkasan-laporan">
            <p>Jumlah pelanggan unik: <strong><?= $laporan['total_pelanggan'] ?></strong></p>
            <p>Total pendapatan keseluruhan: <strong>Rp <?= number_format($laporan['total_pendapatan'], 0, ',', '.') ?></strong></p>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            const canvas = document.getElementById('grafikPenjualan');
            if (window.Chart) {
                new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode(array_column($laporan['rows'], 'tgl'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                        datasets: [{
                            label: 'Total penjualan (Rp)',
                            data: <?= json_encode(array_map('intval', array_column($laporan['rows'], 'total_harian'))) ?>,
                            backgroundColor: '#0369a1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        scales: { y: { beginAtZero: true } }
                    }
                });
            } else {
                canvas.insertAdjacentHTML('afterend', '<p class="pesan-error" role="alert">Grafik gagal dimuat. Data tetap tersedia di tabel.</p>');
                canvas.remove();
            }
        </script>
    <?php endif; ?>
<?php endif; ?>
