<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$tableKey = $_GET['t'] ?? '';
$config = tableConfig($tableKey);
$editing = false;
$errors = [];
$dbError = null;
$values = array_fill_keys(formFields($config), '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = collectInput($config);
    [$data, $errors] = validateInput($config, $values, false);

    if (!$errors) {
        try {
            insertRow($pdo, $config, $data);
            flash('success', 'Data ' . $config['name'] . ' berhasil ditambahkan.');
            redirect('tabel.php?t=' . urlencode($tableKey));
        } catch (PDOException $exception) {
            $dbError = describeDbError($exception);
        }
    }
}

$pageTitle = 'Tambah ' . $config['title'] . ' Baru';
$action = 'tambah.php?t=' . urlencode($tableKey);
$submitLabel = 'Simpan';
$cancelUrl = 'tabel.php?t=' . urlencode($tableKey);

require __DIR__ . '/partials/header.php';
?>
<div class="form-page">
    <h2 class="page-title">Tambah <?= e($config['title']) ?> Baru</h2>

    <?php if ($dbError !== null): ?>
        <div class="alert alert-danger"><strong>Gagal:</strong> <?= e($dbError) ?></div>
    <?php endif; ?>

    <?php require __DIR__ . '/partials/form.php'; ?>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
