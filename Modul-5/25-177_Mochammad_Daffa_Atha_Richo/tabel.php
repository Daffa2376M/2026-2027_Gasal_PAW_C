<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$tableKey = $_GET['t'] ?? '';
$config = tableConfig($tableKey);
$rows = $pdo->query($config['list']['sql'])->fetchAll();

$pageTitle = $config['title'];
$confirm = 'Anda yakin akan menghapus ' . $config['name'] . ' ini?';

require __DIR__ . '/partials/header.php';
?>
<div class="page-header">
    <h2 class="page-title"><?= e($config['title']) ?></h2>
    <a class="btn btn-success" href="tambah.php?t=<?= e($tableKey) ?>">Tambah Data</a>
</div>

<div class="table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th scope="col">No</th>
                <?php foreach ($config['list']['columns'] as $column): ?>
                    <th scope="col"><?= e(fieldLabel($config, $column)) ?></th>
                <?php endforeach; ?>
                <th scope="col">Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$rows): ?>
                <tr>
                    <td colspan="<?= count($config['list']['columns']) + 2 ?>">Belum ada data.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($rows as $index => $row): ?>
                <tr>
                    <td><?= $index + 1 ?></td>
                    <?php foreach ($config['list']['columns'] as $column): ?>
                        <td><?= e(displayValue($config, $column, $row[$column] ?? null)) ?></td>
                    <?php endforeach; ?>
                    <td class="actions">
                        <a class="btn btn-warning btn-sm"
                           href="edit.php?t=<?= e($tableKey) ?>&amp;<?= e(http_build_query(pkQuery($config, $row))) ?>">Edit</a>
                        <form class="inline" method="post" action="hapus.php?t=<?= e($tableKey) ?>"
                              onsubmit="return confirm('<?= e($confirm) ?>');">
                            <?php foreach ($config['primary'] as $column): ?>
                                <input type="hidden" name="<?= e($column) ?>" value="<?= e($row[$column] ?? '') ?>">
                            <?php endforeach; ?>
                            <button class="btn btn-danger btn-sm" type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
