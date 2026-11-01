<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$tableKey = $_GET['t'] ?? '';
$config = tableConfig($tableKey);
$keys = primaryValues($config, $_GET);
$row = findRow($pdo, $config, $keys);

if ($row === null) {
    flash('danger', 'Data ' . $config['name'] . ' tidak ditemukan.');
    redirect('tabel.php?t=' . urlencode($tableKey));
}

$editing = true;
$errors = [];
$dbError = null;
$values = [];

foreach (formFields($config) as $column) {
    $values[$column] = (string) ($row[$column] ?? '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = collectInput($config);
    [$data, $errors] = validateInput($config, $values, true);

    if (!$errors) {
        try {
            updateRow($pdo, $config, $data, $keys);
            flash('success', 'Data ' . $config['name'] . ' berhasil diperbarui.');
            redirect('tabel.php?t=' . urlencode($tableKey));
        } catch (PDOException $exception) {
            $dbError = describeDbError($exception);
        }
    }
}

$pageTitle = 'Edit ' . $config['title'];
$action = 'edit.php?' . http_build_query(['t' => $tableKey] + $keys);
$submitLabel = 'Update';
$cancelUrl = 'tabel.php?t=' . urlencode($tableKey);

require __DIR__ . '/partials/header.php';
?>
<div class="form-page">
    <h2 class="page-title">Edit <?= e($config['title']) ?></h2>

    <?php if ($dbError !== null): ?>
        <div class="alert alert-danger"><strong>Gagal:</strong> <?= e($dbError) ?></div>
    <?php endif; ?>

    <?php require __DIR__ . '/partials/form.php'; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
