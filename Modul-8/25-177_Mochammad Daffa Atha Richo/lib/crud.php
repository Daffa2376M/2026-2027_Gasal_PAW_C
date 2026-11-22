<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * Mengembalikan seluruh definisi skema Data Master.
 *
 * @return array<string, array<string, mixed>>
 */
function getMasterSchemas(): array
{
    static $schemas = null;
    if ($schemas === null) {
        $schemas = require __DIR__ . '/../config/schema.php';
    }

    return $schemas;
}

/**
 * Mengembalikan konfigurasi skema tabel berdasarkan key URL (?t=), atau null jika tidak valid.
 *
 * @return array<string, mixed>|null
 */
function getMasterSchema(mixed $tableKey): ?array
{
    if (!is_string($tableKey)) {
        return null;
    }

    $schemas = getMasterSchemas();
    return $schemas[$tableKey] ?? null;
}

/**
 * Memvalidasi parameter ID bilangan bulat positif (1..4294967295).
 */
function parsePositiveIntId(mixed $raw): ?int
{
    if (is_int($raw)) {
        return ($raw > 0 && $raw <= 4294967295) ? $raw : null;
    }

    if (!is_string($raw)) {
        return null;
    }

    $trimmed = trim($raw);
    if ($trimmed === '' || !ctype_digit($trimmed)) {
        return null;
    }

    $parsed = filter_var($trimmed, FILTER_VALIDATE_INT, [
        'options' => [
            'min_range' => 1,
            'max_range' => 4294967295,
        ],
    ]);

    return $parsed === false ? null : (int) $parsed;
}

/**
 * Menentukan apakah field ditampilkan pada form input Create/Edit.
 *
 * @param array<string, mixed> $field
 */
function isFormField(array $field): bool
{
    return empty($field['auto']) && ($field['form'] ?? true);
}

/**
 * Mengembalikan daftar key kolom yang dirender pada form.
 *
 * @param array<string, mixed> $config
 * @return array<int, string>
 */
function formFields(array $config): array
{
    $keys = [];
    foreach ($config['fields'] as $column => $field) {
        if (isFormField($field)) {
            $keys[] = $column;
        }
    }

    return $keys;
}

/**
 * Mengembalikan label kolom yang ramah baca.
 *
 * @param array<string, mixed> $config
 */
function fieldLabel(array $config, string $column): string
{
    return (string) ($config['fields'][$column]['label'] ?? $column);
}

/**
 * Mengambil pasangan nilai => teks untuk field bertipe select.
 *
 * @param array<string, mixed> $field
 * @return array<string, string>
 */
function fieldOptions(PDO $pdo, array $field): array
{
    if (isset($field['options']) && is_array($field['options'])) {
        $out = [];
        foreach ($field['options'] as $k => $v) {
            $out[(string) $k] = (string) $v;
        }
        return $out;
    }

    if (isset($field['options_sql']) && is_string($field['options_sql'])) {
        $rows = $pdo->query($field['options_sql'])->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[(string) ($row['nilai'] ?? '')] = (string) ($row['teks'] ?? '');
        }
        return $out;
    }

    return [];
}

/**
 * Memformat nilai sel tabel daftar Data Master untuk ditampilkan di HTML.
 *
 * @param array<string, mixed> $config
 */
function displayValue(array $config, string $column, mixed $value): string
{
    $field = $config['fields'][$column] ?? [];
    $type = (string) ($field['type'] ?? 'text');

    if ($type === 'money') {
        return rupiah($value);
    }

    if (isset($field['options']) && is_array($field['options'])) {
        $needle = (string) ($value ?? '');
        foreach ($field['options'] as $optKey => $optLabel) {
            if ((string) $optKey === $needle) {
                return (string) $optLabel;
            }
        }
    }

    return (string) ($value ?? '');
}

/**
 * Merender elemen input HTML5 sesuai definisi field skema.
 *
 * @param array<string, mixed> $config
 */
function renderInput(PDO $pdo, array $config, string $column, mixed $value, bool $editing): string
{
    $field = $config['fields'][$column] ?? [];
    $type = (string) ($field['type'] ?? 'text');
    $inputId = 'f-' . $column;
    $required = !empty($field['required']) && !($editing && !empty($field['keep_if_empty']));
    $reqAttr = $required ? ' required' : '';
    $placeholder = isset($field['placeholder']) && is_string($field['placeholder'])
        ? ' placeholder="' . e($field['placeholder']) . '"'
        : '';
    $maxAttr = isset($field['max']) ? ' maxlength="' . (int) $field['max'] . '"' : '';

    if ($type === 'select') {
        $options = fieldOptions($pdo, $field);
        $currentVal = (string) ($value ?? '');
        $html = '<select id="' . e($inputId) . '" name="' . e($column) . '" class="form-control"' . $reqAttr . '>';
        $html .= '<option value="">-- Pilih ' . e((string) ($field['label'] ?? $column)) . ' --</option>';
        foreach ($options as $optVal => $optLabel) {
            $selected = ((string) $optVal === $currentVal) ? ' selected' : '';
            $html .= '<option value="' . e((string) $optVal) . '"' . $selected . '>' . e((string) $optLabel) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    if ($type === 'textarea') {
        return '<textarea id="' . e($inputId) . '" name="' . e($column) . '" rows="3" class="form-control"'
            . $maxAttr . $placeholder . $reqAttr . '>' . e((string) ($value ?? '')) . '</textarea>';
    }

    if ($type === 'password') {
        // Password hash/value tidak pernah dirender kembali ke attribute value HTML.
        return '<input type="password" id="' . e($inputId) . '" name="' . e($column) . '" class="form-control"'
            . $maxAttr . $placeholder . $reqAttr . ' autocomplete="new-password">';
    }

    if ($type === 'money' || $type === 'number') {
        $min = (int) ($field['min'] ?? 0);
        $maxInt = (int) ($field['max_int'] ?? 2147483647);
        return '<input type="number" id="' . e($inputId) . '" name="' . e($column) . '" class="form-control"'
            . ' step="1" min="' . $min . '" max="' . $maxInt . '"'
            . ' value="' . e((string) ($value ?? '')) . '"' . $reqAttr . '>';
    }

    return '<input type="text" id="' . e($inputId) . '" name="' . e($column) . '" class="form-control"'
        . $maxAttr . $placeholder . ' value="' . e((string) ($value ?? '')) . '"' . $reqAttr . '>';
}

/**
 * Mengambil dan menormalkan input form dengan penjagaan tipe is_string().
 *
 * @param array<string, mixed> $config
 * @param array<string, mixed> $source
 * @return array<string, string>
 */
function collectInput(array $config, array $source): array
{
    $collected = [];
    foreach (formFields($config) as $column) {
        $field = $config['fields'][$column];
        $raw = $source[$column] ?? '';
        if (!is_string($raw)) {
            $collected[$column] = '';
            continue;
        }

        // Password tidak di-trim agar konsisten dengan login.php.
        $collected[$column] = !empty($field['no_trim']) ? $raw : trim($raw);
    }

    return $collected;
}

/**
 * Memvalidasi input form sesuai aturan skema, integritas FK, dan keunikan kolom.
 *
 * @param array<string, mixed> $config
 * @param array<string, string> $input
 * @return array{0: array<string, mixed>, 1: array<string, string>}
 */
function validateInput(PDO $pdo, array $config, array $input, bool $editing, ?int $currentId = null): array
{
    $data = [];
    $errors = [];
    $table = (string) $config['table'];
    $pk = (string) $config['primary'];

    foreach (formFields($config) as $column) {
        $field = $config['fields'][$column];
        $label = fieldLabel($config, $column);
        $type = (string) ($field['type'] ?? 'text');
        $value = $input[$column] ?? '';

        if ($editing && !empty($field['keep_if_empty']) && $value === '') {
            $data[$column] = null;
            continue;
        }

        if (!empty($field['required']) && $value === '') {
            $errors[$column] = $label . ' wajib diisi.';
            continue;
        }

        if ($value === '') {
            $data[$column] = '';
            continue;
        }

        if ($type === 'money' || $type === 'number') {
            $min = (int) ($field['min'] ?? 0);
            $maxInt = (int) ($field['max_int'] ?? 2147483647);
            if (!ctype_digit($value)) {
                $errors[$column] = $label . ' harus berupa bilangan bulat non-negatif.';
                continue;
            }
            $normalized = ltrim($value, '0');
            $numStr = $normalized === '' ? '0' : $normalized;
            if (strlen($numStr) > 10 || (int) $numStr > $maxInt || (int) $numStr < $min) {
                $errors[$column] = $label . ' harus bernilai antara ' . $min . ' hingga ' . number_format($maxInt, 0, ',', '.') . '.';
                continue;
            }
            $data[$column] = (int) $numStr;
            continue;
        }

        if ($type === 'select') {
            $options = fieldOptions($pdo, $field);
            $validKeys = array_map('strval', array_keys($options));
            if (!in_array($value, $validKeys, true)) {
                $errors[$column] = 'Pilihan ' . strtolower($label) . ' tidak valid.';
                continue;
            }
            $data[$column] = (($field['value_type'] ?? 'string') === 'int') ? (int) $value : $value;
            continue;
        }

        if (isset($field['max'])) {
            $maxLen = (int) $field['max'];
            $len = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
            if ($len > $maxLen) {
                $errors[$column] = $label . ' maksimal ' . $maxLen . ' karakter.';
                continue;
            }
        }

        if (!empty($field['unique'])) {
            $sql = 'SELECT 1 FROM `' . $table . '` WHERE `' . $column . '` = :val';
            $params = ['val' => $value];
            if ($editing && $currentId !== null) {
                $sql .= ' AND `' . $pk . '` <> :id';
                $params['id'] = $currentId;
            }
            $sql .= ' LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if ($stmt->fetchColumn() !== false) {
                $errors[$column] = $label . ' sudah digunakan.';
                continue;
            }
        }

        if (!empty($field['hash'])) {
            $data[$column] = password_hash($value, PASSWORD_DEFAULT);
            continue;
        }

        $data[$column] = $value;
    }

    return [$data, $errors];
}

/**
 * Mengambil seluruh baris untuk tampilan daftar Data Master.
 *
 * @param array<string, mixed> $config
 * @return array<int, array<string, mixed>>
 */
function findMasterRows(PDO $pdo, array $config): array
{
    return $pdo->query((string) $config['list']['sql'])->fetchAll();
}

/**
 * Mengambil satu baris Data Master berdasarkan primary key.
 *
 * @param array<string, mixed> $config
 * @return array<string, mixed>|null
 */
function findMasterRow(PDO $pdo, array $config, int $id): ?array
{
    $table = (string) $config['table'];
    $pk = (string) $config['primary'];
    $stmt = $pdo->prepare('SELECT * FROM `' . $table . '` WHERE `' . $pk . '` = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

/**
 * Menyisipkan baris baru ke tabel Data Master.
 *
 * @param array<string, mixed> $config
 * @param array<string, mixed> $data
 */
function insertMasterRow(PDO $pdo, array $config, array $data): int
{
    $table = (string) $config['table'];
    $columns = array_keys($data);
    $quotedCols = array_map(static fn (string $c): string => '`' . $c . '`', $columns);
    $placeholders = array_fill(0, count($columns), '?');

    $sql = 'INSERT INTO `' . $table . '` (' . implode(', ', $quotedCols) . ') VALUES (' . implode(', ', $placeholders) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));

    return (int) $pdo->lastInsertId();
}

/**
 * Memperbarui baris Data Master yang ada.
 * Kolom dengan keep_if_empty bernilai null tidak ikut di-UPDATE.
 *
 * @param array<string, mixed> $config
 * @param array<string, mixed> $data
 */
function updateMasterRow(PDO $pdo, array $config, int $id, array $data): int
{
    $table = (string) $config['table'];
    $pk = (string) $config['primary'];
    $sets = [];
    $values = [];

    foreach ($data as $column => $value) {
        $field = $config['fields'][$column] ?? [];
        if ($value === null && !empty($field['keep_if_empty'])) {
            continue;
        }
        $sets[] = '`' . $column . '` = ?';
        $values[] = $value;
    }

    if ($sets === []) {
        return 0;
    }

    $values[] = $id;
    $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE `' . $pk . '` = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    return $stmt->rowCount();
}

/**
 * Menghapus satu baris Data Master berdasarkan primary key.
 *
 * @param array<string, mixed> $config
 */
function deleteMasterRow(PDO $pdo, array $config, int $id): int
{
    $table = (string) $config['table'];
    $pk = (string) $config['primary'];
    $stmt = $pdo->prepare('DELETE FROM `' . $table . '` WHERE `' . $pk . '` = :id');
    $stmt->execute(['id' => $id]);

    return $stmt->rowCount();
}

/**
 * Menerjemahkan PDOException menjadi pesan berbahasa Indonesia yang ramah pengguna
 * tanpa membocorkan detail query SQL ke browser.
 */
function describeCrudDbError(PDOException $e): string
{
    error_log('[TP8 CRUD] ' . $e->getMessage());

    $driverCode = $e->errorInfo[1] ?? null;
    if ($driverCode === 1062) {
        return 'Data gagal disimpan karena terdapat nilai unik yang sudah digunakan.';
    }
    if ($driverCode === 1451) {
        return 'Data tidak dapat dihapus karena masih digunakan oleh data barang atau transaksi.';
    }
    if ($driverCode === 1452) {
        return 'Data referensi yang dipilih tidak ditemukan.';
    }
    if ((string) $e->getCode() === '23000') {
        return 'Operasi database gagal karena melanggar batasan integritas data.';
    }

    return 'Operasi database gagal. Silakan periksa kembali data Anda.';
}

/**
 * Merender halaman error HTML yang konsisten dengan layout aplikasi dan menghentikan eksekusi.
 */
function renderMasterErrorPage(
    int $statusCode,
    string $title,
    string $message,
    string $backUrl = 'index.php',
    string $backLabel = 'Kembali ke Home'
): never {
    if (!headers_sent()) {
        http_response_code($statusCode);
    }

    $pageTitle = $title . ' - Sistem Penjualan';
    $activeMenu = 'master';
    require __DIR__ . '/../partials/header.php';
    ?>
    <section class="card error-box" role="alert">
        <p class="error-code"><?= e((string) $statusCode) ?></p>
        <h1 class="page-title"><?= e($title) ?></h1>
        <p class="error-message"><?= e($message) ?></p>
        <div class="error-actions">
            <a href="<?= e($backUrl) ?>" class="btn btn-primary"><?= e($backLabel) ?></a>
        </div>
    </section>
    <?php
    require __DIR__ . '/../partials/footer.php';
    exit;
}
