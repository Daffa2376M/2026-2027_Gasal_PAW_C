<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$tableKey = $_GET['t'] ?? '';
$config = tableConfig($tableKey);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('danger', 'Penghapusan hanya bisa dilakukan lewat tombol Hapus.');
    redirect('tabel.php?t=' . urlencode($tableKey));
}

$keys = primaryValues($config, $_POST);

try {
    $deleted = deleteRow($pdo, $config, $keys);

    if ($deleted > 0) {
        flash('success', 'Data ' . $config['name'] . ' berhasil dihapus.');
    } else {
        flash('danger', 'Data ' . $config['name'] . ' tidak ditemukan.');
    }
} catch (PDOException $exception) {
    flash('danger', describeDbError($exception));
}

redirect('tabel.php?t=' . urlencode($tableKey));
