<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$tables = schema();
$counts = [];
foreach ($tables as $tableKey => $config) {
    $counts[$tableKey] = (int) $pdo->query('SELECT COUNT(*) FROM `' . $config['table'] . '`')->fetchColumn();
}

$pageTitle = 'Dashboard';

require __DIR__ . '/partials/header.php';
?>
<h2 class="page-title">Dashboard</h2>
<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th scope="col">Tabel</th>
                <th scope="col">Jumlah Data</th>
                <th scope="col">Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($tables as $tableKey => $config): ?>
                <tr>
                    <td><?= e($config['title']) ?></td>
                    <td><?= $counts[$tableKey] ?> data</td>
                    <td>
                        <a class="btn btn-primary btn-sm" href="tabel.php?t=<?= e($tableKey) ?>">Lihat Data</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
