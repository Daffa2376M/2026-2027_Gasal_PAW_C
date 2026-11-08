<?php
/** @var PDO $pdo */
/** @var array $config */
/** @var array $values */
/** @var array $errors */
/** @var string $action */
/** @var string $submitLabel */
/** @var string $cancelUrl */
/** @var bool $editing */
?>
<?php if ($errors): ?>
    <div class="alert alert-danger">Periksa kembali data yang diisi.</div>
<?php endif; ?>
<form method="post" action="<?= e($action) ?>" class="data-form">
    <?php foreach ($config['fields'] as $column => $field): ?>
        <?php if (!isFormField($field)) { continue; } ?>
        <div class="form-row">
            <label for="f-<?= e($column) ?>"><?= e($field['label']) ?></label>
            <div class="form-field">
                <?= renderInput($pdo, $config, $column, $values[$column] ?? '', $editing) ?>
                <?php if (isset($errors[$column])): ?>
                    <div class="field-error"><?= e($errors[$column]) ?></div>
                <?php endif; ?>
                <?php if ($editing && !empty($field['keep_if_empty'])): ?>
                    <div class="field-help">Kosongkan bila password tidak ingin diubah.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <div class="form-actions">
        <button class="btn btn-success" type="submit"><?= e($submitLabel) ?></button>
        <a class="btn btn-danger" href="<?= e($cancelUrl) ?>">Batal</a>
    </div>
</form>
