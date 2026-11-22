<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/lib/helpers.php';

requireLogin();

while (ob_get_level() > 0) {
    ob_end_clean();
}

function isValidExcelReportDate(string $date): bool
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
function defaultExcelReportRange(PDO $pdo): array
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
function resolveExcelReportFilter(PDO $pdo, array $query): array
{
    $defaults = defaultExcelReportRange($pdo);
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

    if (!isValidExcelReportDate($mulai) || !isValidExcelReportDate($selesai)) {
        return [
            'mulai'   => isValidExcelReportDate($mulai) ? $mulai : $defaults['mulai'],
            'selesai' => isValidExcelReportDate($selesai) ? $selesai : $defaults['selesai'],
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
function fetchExcelSalesReport(PDO $pdo, string $mulai, string $selesai): array
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

try {
    $filter = resolveExcelReportFilter($pdo, $_GET);
} catch (PDOException $e) {
    error_log('laporan_excel.php filter error: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Gagal memuat data laporan.';
    exit;
}

if ($filter['error'] !== null) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo $filter['error'];
    exit;
}

try {
    $report = fetchExcelSalesReport($pdo, $filter['mulai'], $filter['selesai']);
} catch (PDOException $e) {
    error_log('laporan_excel.php query error: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Gagal memuat data laporan.';
    exit;
}

$mulai = str_replace(["\r", "\n", '"'], '', $filter['mulai']);
$selesai = str_replace(["\r", "\n", '"'], '', $filter['selesai']);

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="laporan_penjualan_' . $mulai . '_sd_' . $selesai . '.xls"');

echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan <?= e($mulai) ?> s/d <?= e($selesai) ?></title>
</head>
<body>
<table border="1" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th colspan="4">Laporan Penjualan</th>
        </tr>
        <tr>
            <th colspan="4">Periode <?= e($mulai) ?> s/d <?= e($selesai) ?></th>
        </tr>
        <tr>
            <th>No</th>
            <th>Tanggal</th>
            <th>Jumlah Transaksi</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($report['rekap'] === []): ?>
            <tr>
                <td colspan="4">Tidak ada transaksi pada rentang tanggal ini.</td>
            </tr>
        <?php else: ?>
            <?php $no = 1; ?>
            <?php foreach ($report['rekap'] as $row): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= e($row['tanggal']) ?></td>
                    <td><?= (int) $row['jumlah_transaksi'] ?></td>
                    <td><?= (int) $row['total_harian'] ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="3" align="left">Jumlah Pelanggan</th>
            <th><?= (int) $report['jumlah_pelanggan'] ?></th>
        </tr>
        <tr>
            <th colspan="3" align="left">Total Transaksi</th>
            <th><?= (int) $report['total_transaksi'] ?></th>
        </tr>
        <tr>
            <th colspan="3" align="left">Jumlah Pendapatan (Rp)</th>
            <th><?= (int) $report['jumlah_pendapatan'] ?></th>
        </tr>
    </tfoot>
</table>
</body>
</html>
