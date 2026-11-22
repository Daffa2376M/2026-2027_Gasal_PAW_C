<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/crud.php';

requireOwner();

$tableKey = is_string($_GET['t'] ?? null) ? $_GET['t'] : '';
$config = getMasterSchema($tableKey);
if ($config === null) {
    renderMasterErrorPage(
        404,
        'Data Master Tidak Ditemukan',
        'Tabel data master yang diminta tidak ditemukan atau tidak valid.'
    );
}

$pdo = getPDO();
$listUrl = 'master.php?t=' . urlencode($tableKey);
$fields = formFields($config);
$me = currentUser();

$editing = false;
$id = null;
$values = array_fill_keys($fields, '');

if (array_key_exists('id', $_GET)) {
    $id = parsePositiveIntId($_GET['id']);
    if ($id === null) {
        renderMasterErrorPage(
            400,
            'Parameter ID Tidak Valid',
            'Parameter ID harus berupa bilangan bulat positif.',
            $listUrl,
            'Kembali ke ' . (string) $config['title']
        );
    }

    $existing = findMasterRow($pdo, $config, $id);
    if ($existing === null) {
        renderMasterErrorPage(
            404,
            'Data Tidak Ditemukan',
            'Data ' . (string) $config['name'] . ' dengan ID tersebut tidak ditemukan.',
            $listUrl,
            'Kembali ke ' . (string) $config['title']
        );
    }

    $editing = true;
    foreach ($fields as $col) {
        if (($config['fields'][$col]['type'] ?? 'text') === 'password') {
            $values[$col] = '';
        } else {
            $values[$col] = (string) ($existing[$col] ?? '');
        }
    }
}

$errors = [];
$dbError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = collectInput($config, $_POST);
    [$data, $errors] = validateInput($pdo, $config, $values, $editing, $id);

    // Mencegah owner menurunkan level akun miliknya sendiri yang sedang login.
    if (
        $editing
        && $tableKey === 'user'
        && $me !== null
        && $id === $me['id_user']
        && isset($data['level'])
        && (int) $data['level'] !== 1
    ) {
        $errors['level'] = 'Level akun yang sedang login tidak dapat diubah menjadi Kasir.';
    }

    if ($errors === []) {
        try {
            if ($editing && $id !== null) {
                updateMasterRow($pdo, $config, $id, $data);

                if ($tableKey === 'user' && $me !== null && $id === $me['id_user']) {
                    if (isset($data['username'])) {
                        $_SESSION['username'] = (string) $data['username'];
                    }
                    if (isset($data['nama'])) {
                        $_SESSION['nama'] = (string) $data['nama'];
                    }
                }

                setFlash('success', 'Data ' . (string) $config['name'] . ' berhasil diperbarui.');
            } else {
                insertMasterRow($pdo, $config, $data);
                setFlash('success', 'Data ' . (string) $config['name'] . ' berhasil ditambahkan.');
            }

            redirect($listUrl);
        } catch (PDOException $e) {
            $dbError = describeCrudDbError($e);
        }
    }

    // Pastikan password tidak pernah dipantulkan kembali ke DOM pada error validasi.
    if (array_key_exists('password', $values)) {
        $values['password'] = '';
    }
}

$heading = ($editing ? 'Edit ' : 'Tambah ') . (string) $config['title'];
$actionUrl = 'master_form.php?t=' . urlencode($tableKey) . ($editing && $id !== null ? '&id=' . $id : '');

$pageTitle = $heading . ' - Sistem Penjualan';
$activeMenu = 'master';
require __DIR__ . '/partials/header.php';
?>

<section class="card">
    <div class="page-header">
        <h1 class="page-title"><?= e($heading) ?></h1>
        <a href="<?= e($listUrl) ?>" class="btn btn-secondary">Kembali</a>
    </div>

    <?php if ($dbError !== ''): ?>
        <div class="alert alert-danger" role="alert"><?= e($dbError) ?></div>
    <?php elseif ($errors !== []): ?>
        <div class="alert alert-danger" role="alert">Periksa kembali data yang Anda isi.</div>
    <?php endif; ?>

    <form method="post" action="<?= e($actionUrl) ?>">
        <?php foreach ($fields as $column): ?>
            <?php $field = $config['fields'][$column]; ?>
            <div class="form-row">
                <label for="f-<?= e($column) ?>">
                    <?= e(fieldLabel($config, $column)) ?>
                    <?php if (!empty($field['required']) && !($editing && !empty($field['keep_if_empty']))): ?>
                        <span style="color: #dc2626;" aria-hidden="true">*</span>
                    <?php endif; ?>
                </label>
                <div>
                    <?= renderInput($pdo, $config, $column, $values[$column] ?? '', $editing) ?>

                    <?php if ($editing && !empty($field['keep_if_empty'])): ?>
                        <div class="field-help">Kosongkan jika tidak ingin mengubah password.</div>
                    <?php endif; ?>

                    <?php if (isset($errors[$column])): ?>
                        <div class="field-error"><?= e($errors[$column]) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-success">
                <?= $editing ? 'Update' : 'Simpan' ?>
            </button>
            <a href="<?= e($listUrl) ?>" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
