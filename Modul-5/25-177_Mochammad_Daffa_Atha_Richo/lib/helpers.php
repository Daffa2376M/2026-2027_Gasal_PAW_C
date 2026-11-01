<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rupiah(mixed $value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    return 'Rp ' . number_format((float) $value, 0, ',', '.');
}

function tanggal(mixed $value): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    $time = strtotime((string) $value);

    return $time === false ? (string) $value : date('d-m-Y H:i', $time);
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function takeFlash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $messages;
}

function fieldLabel(array $config, string $column): string
{
    return $config['fields'][$column]['label'] ?? $column;
}

function displayValue(array $config, string $column, mixed $value): string
{
    $type = $config['fields'][$column]['type'] ?? 'text';

    return match ($type) {
        'money' => rupiah($value),
        'datetime' => tanggal($value),
        default => (string) ($value ?? ''),
    };
}

function renderInput(
    PDO $db,
    array $config,
    string $column,
    mixed $value,
    bool $editing
): string {
    $field = $config['fields'][$column];
    $type = $field['type'] ?? 'text';
    $required = !empty($field['required']) && !($editing && !empty($field['keep_if_empty']));

    $attributes = ' id="f-' . e($column) . '" name="' . e($column) . '"';
    if ($required) {
        $attributes .= ' required';
    }

    if ($type === 'select') {
        $html = '<select' . $attributes . '>';
        $html .= '<option value="">-- Pilih --</option>';
        foreach (fieldOptions($db, $field) as $optionValue => $optionText) {
            $selected = (string) $optionValue === (string) $value ? ' selected' : '';
            $html .= '<option value="' . e($optionValue) . '"' . $selected . '>'
                . e($optionText) . '</option>';
        }

        return $html . '</select>';
    }

    $value = (string) ($value ?? '');

    return match ($type) {
        'password' => '<input type="password" maxlength="'
            . e($field['max'] ?? 255) . '" autocomplete="new-password"' . $attributes . '>',
        'datetime' => '<input type="datetime-local" value="'
            . e($value === '' ? '' : str_replace(' ', 'T', substr($value, 0, 16))) . '"' . $attributes . '>',
        'money' => '<input type="number" step="0.01" min="' . e($field['min'] ?? 0)
            . '" value="' . e($value) . '"' . $attributes . '>',
        'number' => '<input type="number" step="1" min="' . e($field['min'] ?? 0)
            . '" value="' . e($value) . '"' . $attributes . '>',
        default => '<input type="text" maxlength="'
            . e($field['max'] ?? 255) . '" placeholder="' . e($field['placeholder'] ?? '')
            . '" value="' . e($value) . '"' . $attributes . '>',
    };
}
