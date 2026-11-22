<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/lib/helpers.php';

requireLogin();

function isValidReportDate(string $date): bool
{
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
        return false;
    }

    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    return $dt !== false && $dt->format('Y-m-d') === $date;
}

/**
 * @return array{mulai: string, selesai: string}
 */
function defaultReportRange(PDO $pdo): array
{
    $selesai = date('Y-m-d');
    $mulai = date('Y-m-d', strtotime('-29 days'));

    $stmtCount = $pdo->prepare(
        'SELECT COUNT(*) FROM transaksi WHERE waktu_transaksi BETWEEN :mulai AND :selesai'
    );
    $stmtCount->execute(['mulai' => $mulai, 'selesai' => $selesai]);
    if ((int) $stmtCount->fetchColumn() > 0) {
        return ['mulai' => $mulai, 'selesai' => $selesai];
    }

    $bounds = $pdo->query(
        'SELECT MIN(waktu_transaksi) AS min_tgl, MAX(waktu_transaksi) AS max_tgl FROM transaksi'
    )->fetch();
    if (
        is_array($bounds)
        && is_string($bounds['min_tgl'] ?? null)
        && is_string($bounds['max_tgl'] ?? null)
        && $bounds['min_tgl'] !== ''
        && $bounds['max_tgl'] !== ''
    ) {
        return [
            'mulai'   => $bounds['min_tgl'],
            'selesai' => $bounds['max_tgl'],
        ];
    }

    return ['mulai' => $mulai, 'selesai' => $selesai];
}

/**
 * @param array<string, mixed> $query
 * @return array{mulai: string, selesai: string, error: ?string}
 */
function resolveReportFilter(PDO $pdo, array $query): array
{
    $defaults = defaultReportRange($pdo);
    $hasMulai = array_key_exists('mulai', $query);
    $hasSelesai = array_key_exists('selesai', $query);

    if (!$hasMulai && !$hasSelesai) {
        return [
            'mulai'   => $defaults['mulai'],
            'selesai' => $defaults['selesai'],
            'error'   => null,
        ];
    }

    $rawMulai = $hasMulai ? $query['mulai'] : $defaults['mulai'];
    $rawSelesai = $hasSelesai ? $query['selesai'] : $defaults['selesai'];

    if (!is_string($rawMulai) || !is_string($rawSelesai)) {
        return [
            'mulai'   => $defaults['mulai'],
            'selesai' => $defaults['selesai'],
            'error'   => 'Format tanggal tidak valid. Gunakan format YYYY-MM-DD.',
        ];
    }

    $mulai = trim($rawMulai);
    $selesai = trim($rawSelesai);

    if (!isValidReportDate($mulai) || !isValidReportDate($selesai)) {
        return [
            'mulai'   => isValidReportDate($mulai) ? $mulai : $defaults['mulai'],
            'selesai' => isValidReportDate($selesai) ? $selesai : $defaults['selesai'],
            'error'   => 'Rentang tanggal tidak valid. Pastikan format YYYY-MM-DD benar.',
        ];
    }

    if ($mulai > $selesai) {
        return [
            'mulai'   => $mulai,
            'selesai' => $selesai,
            'error'   => 'Tanggal mulai tidak boleh lebih besar daripada tanggal selesai.',
        ];
    }

    return [
        'mulai'   => $mulai,
        'selesai' => $selesai,
        'error'   => null,
    ];
}

/**
 * @return array{
 *     rekap: array<int, array{tanggal: string, jumlah_transaksi: int, total_harian: int}>,
 *     jumlah_pelanggan: int,
 *     jumlah_pendapatan: int,
 *     total_transaksi: int
 * }
 */
function fetchSalesReport(PDO $pdo, string $mulai, string $selesai): array
{
    $stmtDaily = $pdo->prepare(
        'SELECT waktu_transaksi AS tanggal,
                COUNT(*) AS jumlah_transaksi,
                COALESCE(SUM(total), 0) AS total_harian
         FROM transaksi
         WHERE waktu_transaksi BETWEEN :mulai AND :selesai
         GROUP BY waktu_transaksi
         ORDER BY waktu_transaksi ASC'
    );
    $stmtDaily->execute(['mulai' => $mulai, 'selesai' => $selesai]);
    $rawRows = $stmtDaily->fetchAll();

    $rekap = [];
    foreach ($rawRows as $row) {
        $rekap[] = [
            'tanggal'          => (string) $row['tanggal'],
            'jumlah_transaksi' => (int) $row['jumlah_transaksi'],
            'total_harian'     => (int) $row['total_harian'],
        ];
    }

    $stmtSummary = $pdo->prepare(
        'SELECT COUNT(DISTINCT pelanggan_id) AS jumlah_pelanggan,
                COALESCE(SUM(total), 0) AS jumlah_pendapatan,
                COUNT(*) AS total_transaksi
         FROM transaksi
         WHERE waktu_transaksi BETWEEN :mulai AND :selesai'
    );
    $stmtSummary->execute(['mulai' => $mulai, 'selesai' => $selesai]);
    $summary = $stmtSummary->fetch();

    return [
        'rekap'             => $rekap,
        'jumlah_pelanggan'  => (int) ($summary['jumlah_pelanggan'] ?? 0),
        'jumlah_pendapatan' => (int) ($summary['jumlah_pendapatan'] ?? 0),
        'total_transaksi'   => (int) ($summary['total_transaksi'] ?? 0),
    ];
}

$pdo = getPDO();
$mulai = date('Y-m-d', strtotime('-29 days'));
$selesai = date('Y-m-d');
$errorMsg = null;
$report = [
    'rekap'             => [],
    'jumlah_pelanggan'  => 0,
    'jumlah_pendapatan' => 0,
    'total_transaksi'   => 0,
];

try {
    $filter = resolveReportFilter($pdo, $_GET);
    $mulai = $filter['mulai'];
    $selesai = $filter['selesai'];
    $errorMsg = $filter['error'];

    if ($errorMsg === null) {
        $report = fetchSalesReport($pdo, $mulai, $selesai);
    }
} catch (PDOException $e) {
    error_log('laporan.php error: ' . $e->getMessage());
    $errorMsg = 'Gagal memuat data laporan.';
}

$pageTitle = 'Laporan Penjualan - Sistem Penjualan';
$activeMenu = 'laporan';
require __DIR__ . '/partials/header.php';
?>

<section class="card">
    <div class="page-header">
        <h1 class="page-title">Laporan Penjualan</h1>
    </div>

    <form method="get" action="laporan.php" class="filter-laporan no-print">
        <div class="filter-field">
            <label for="mulai">Dari Tanggal</label>
            <input type="date"
                   id="mulai"
                   name="mulai"
                   class="form-control"
                   value="<?= e($mulai) ?>"
                   required>
        </div>
        <div class="filter-field">
            <label for="selesai">Sampai Tanggal</label>
            <input type="date"
                   id="selesai"
                   name="selesai"
                   class="form-control"
                   value="<?= e($selesai) ?>"
                   required>
        </div>
        <button type="submit" class="btn btn-primary">Tampilkan</button>
        <a href="laporan.php" class="btn btn-secondary">Reset</a>
    </form>

    <?php if ($errorMsg !== null): ?>
        <div class="alert alert-danger" role="alert"><?= e($errorMsg) ?></div>
    <?php else: ?>
        <p class="muted">
            Periode: <strong><?= e($mulai) ?></strong> s/d <strong><?= e($selesai) ?></strong>
        </p>

        <div class="aksi-laporan no-print">
            <button type="button" class="btn btn-secondary" onclick="window.print()">Cetak</button>
            <a class="btn btn-success"
               href="<?= e('laporan_excel.php?' . http_build_query(['mulai' => $mulai, 'selesai' => $selesai])) ?>">
                Unduh Excel
            </a>
        </div>

        <h2 class="section-title">Grafik Penjualan Harian</h2>
        <div class="chart-box">
            <?= renderBarChartSvg($report['rekap']) ?>
        </div>

        <h2 class="section-title">Rekapitulasi Penjualan</h2>
        <?php if ($report['rekap'] === []): ?>
            <p class="pesan-kosong">Tidak ada transaksi pada rentang tanggal ini.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th class="kolom-angka">Jumlah Transaksi</th>
                            <th class="kolom-angka">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($report['rekap'] as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= e($row['tanggal']) ?></td>
                                <td class="kolom-angka"><?= (int) $row['jumlah_transaksi'] ?></td>
                                <td class="kolom-angka"><?= e(rupiah($row['total_harian'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="ringkasan-laporan">
            <div class="quick-card">
                <span class="quick-label">Jumlah Pelanggan</span>
                <span class="quick-value"><?= $report['jumlah_pelanggan'] ?></span>
            </div>
            <div class="quick-card">
                <span class="quick-label">Jumlah Pendapatan</span>
                <span class="quick-value"><?= e(rupiah($report['jumlah_pendapatan'])) ?></span>
            </div>
            <div class="quick-card">
                <span class="quick-label">Total Transaksi</span>
                <span class="quick-value"><?= $report['total_transaksi'] ?></span>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php
require __DIR__ . '/partials/footer.php';
