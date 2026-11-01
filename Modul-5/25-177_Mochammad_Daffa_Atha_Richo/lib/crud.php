<?php
declare(strict_types=1);

function schema(): array {
    static $schema = null;
    return $schema ??= require __DIR__ . '/../config/schema.php';
}

function tableConfig(string $tableKey): array {
    $schema = schema();
    if (isset($schema[$tableKey])) {
        return $schema[$tableKey];
    }
    http_response_code(404);
    exit('Tabel tidak ditemukan.');
}

function isFormField(array $field): bool {
    return empty($field['auto']) && ($field['form'] ?? true);
}

function formFields(array $config): array {
    return array_keys(array_filter($config['fields'], 'isFormField'));
}

function collectInput(array $config): array {
    $input = [];
    foreach ($config['fields'] as $column => $field) {
        if (isFormField($field)) {
            $input[$column] = is_string($_POST[$column] ?? null) ? trim($_POST[$column]) : '';
        }
    }
    return $input;
}

function fieldOptions(PDO $db, array $field): array {
    if (isset($field['options'])) {
        return $field['options'];
    }
    if (!isset($field['options_sql'])) {
        return [];
    }
    $options = [];
    foreach ($db->query($field['options_sql']) as $row) {
        $options[$row['nilai']] = $row['teks'];
    }
    return $options;
}

function validateInput(array $config, array $input, bool $editing): array {
    $values = [];
    $errors = [];
    foreach ($config['fields'] as $column => $field) {
        if (!isFormField($field)) {
            continue;
        }
        $label = $field['label'] ?? $column;
        $type = $field['type'] ?? 'text';
        $value = (string) ($input[$column] ?? '');
        $keep = $editing && !empty($field['keep_if_empty']);

        if ($value === '') {
            if (!empty($field['required']) && !$keep) {
                $errors[$column] = $label . ' wajib diisi.';
            }
            $values[$column] = null;
            continue;
        }
        if ($type === 'number') {
            if (!ctype_digit($value)) {
                $errors[$column] = $label . ' harus berupa angka bulat.';
            } elseif (isset($field['min']) && (int) $value < $field['min']) {
                $errors[$column] = $label . ' minimal ' . $field['min'] . '.';
            } else {
                $values[$column] = (int) $value;
            }
        } elseif ($type === 'money') {
            if (!preg_match('/^\d+(\.\d+)?$/', $value)) {
                $errors[$column] = $label . ' harus berupa angka.';
            } else {
                $values[$column] = $value;
            }
        } elseif ($type === 'datetime') {
            $time = DateTime::createFromFormat('!Y-m-d\TH:i', $value);
            if ($time === false || $time->format('Y-m-d\TH:i') !== $value) {
                $errors[$column] = $label . ' tidak valid.';
            } else {
                $values[$column] = $time->format('Y-m-d H:i:s');
            }
        } elseif ($type === 'select' && isset($field['options'])) {
            if (!array_key_exists($value, $field['options'])) {
                $errors[$column] = $label . ' tidak valid.';
            } else {
                $values[$column] = $value;
            }
        } elseif ($type === 'select') {
            if (!ctype_digit($value)) {
                $errors[$column] = $label . ' tidak valid.';
            } else {
                $values[$column] = $value;
            }
        } elseif (isset($field['max']) && mb_strlen($value) > $field['max']) {
            $errors[$column] = $label . ' maksimal ' . $field['max'] . ' karakter.';
        } else {
            $values[$column] = $value;
        }
    }
    return [$values, $errors];
}

function primaryValues(array $config, array $source): array {
    $keys = [];
    foreach ($config['primary'] as $column) {
        $value = $source[$column] ?? '';
        if (!is_string($value) || !ctype_digit($value)) {
            http_response_code(400);
            exit('Parameter ' . $column . ' tidak valid.');
        }
        $keys[$column] = $value;
    }
    return $keys;
}

function primaryWhere(array $config, array $keys): array {
    $conditions = array_map(fn (string $column): string => "`$column` = ?", $config['primary']);
    return [implode(' AND ', $conditions), array_values($keys)];
}

function findRow(PDO $db, array $config, array $keys): ?array {
    [$where, $params] = primaryWhere($config, $keys);
    $statement = $db->prepare('SELECT * FROM `' . $config['table'] . '` WHERE ' . $where . ' LIMIT 1');
    $statement->execute($params);
    return $statement->fetch() ?: null;
}

function insertRow(PDO $db, array $config, array $values): void {
    $columns = array_keys($values);
    $marks = implode(', ', array_fill(0, count($columns), '?'));
    $sql = 'INSERT INTO `' . $config['table'] . '` (`' . implode('`, `', $columns) . "`) VALUES ($marks)";
    $db->prepare($sql)->execute(array_values($values));
}

function updateRow(PDO $db, array $config, array $values, array $keys): void {
    $assignments = [];
    $params = [];
    foreach ($values as $column => $value) {
        if ($value === null && !empty($config['fields'][$column]['keep_if_empty'])) {
            continue;
        }
        $assignments[] = "`$column` = ?";
        $params[] = $value;
    }
    [$where, $keyParams] = primaryWhere($config, $keys);
    $sql = 'UPDATE `' . $config['table'] . '` SET ' . implode(', ', $assignments) . ' WHERE ' . $where;
    $db->prepare($sql)->execute([...$params, ...$keyParams]);
}

function deleteRow(PDO $db, array $config, array $keys): int {
    [$where, $params] = primaryWhere($config, $keys);
    $statement = $db->prepare('DELETE FROM `' . $config['table'] . '` WHERE ' . $where);
    $statement->execute($params);
    return $statement->rowCount();
}

function pkQuery(array $config, array $row): array {
    $query = [];
    foreach ($config['primary'] as $column) {
        $query[$column] = (string) ($row[$column] ?? '');
    }
    return $query;
}

function describeDbError(PDOException $exception): string {
    return match ($exception->errorInfo[1] ?? null) {
        1062 => 'Nilai unik sudah digunakan (misalnya username atau kode barang).',
        1451 => 'Data masih dipakai pada tabel lain, hapus data terkait lebih dahulu.',
        1452 => 'Data referensi tidak ditemukan.',
        default => 'Operasi database gagal: ' . $exception->getMessage(),
    };
}
