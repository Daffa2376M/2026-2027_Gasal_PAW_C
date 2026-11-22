<?php
declare(strict_types=1);

/**
 * Escape HTML untuk mencegah XSS.
 * Menerima mixed agar nilai int/float/null dari PDO tidak memicu TypeError.
 */
function e(mixed $val): string
{
    return htmlspecialchars((string) ($val ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Format angka menjadi mata uang Rupiah.
 */
function rupiah(int|float|string|null $n): string
{
    if ($n === null || $n === '') {
        return '-';
    }

    return 'Rp ' . number_format((float) $n, 0, ',', '.');
}

/**
 * Redirect aman ke URL tujuan dan menghentikan eksekusi.
 */
function redirect(string $url): never
{
    $cleanUrl = str_replace(["\r", "\n"], '', $url);
    header('Location: ' . $cleanUrl);
    exit;
}

/**
 * Menyimpan pesan flash satu-kali baca ke dalam session.
 */
function setFlash(string $type, string $msg): void
{
    if (session_status() === PHP_SESSION_NONE) {
        if (!function_exists('ensureSession')) {
            require_once __DIR__ . '/../config/auth.php';
        }
        ensureSession();
    }

    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $msg,
    ];
}

/**
 * Mengambil dan menghapus pesan flash dari session.
 *
 * @return array{type: string, message: string}|null
 */
function getFlash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        if (!function_exists('ensureSession')) {
            require_once __DIR__ . '/../config/auth.php';
        }
        ensureSession();
    }

    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    if (is_array($flash) && isset($flash['type'], $flash['message'])) {
        return [
            'type'    => (string) $flash['type'],
            'message' => (string) $flash['message'],
        ];
    }

    return null;
}

/**
 * Merender grafik batang native inline SVG untuk laporan penjualan harian.
 * Mendukung key 'tanggal' maupun fallback 'tgl', serta 'total_harian'.
 *
 * @param array<int, array<string, mixed>> $rows
 */
function renderBarChartSvg(array $rows): string
{
    if ($rows === []) {
        return '<p class="pesan-kosong">Tidak ada data untuk ditampilkan pada grafik.</p>';
    }

    $items = [];
    $maxVal = 0.0;
    foreach ($rows as $row) {
        $label = (string) ($row['tanggal'] ?? $row['tgl'] ?? '');
        $val = (float) ($row['total_harian'] ?? 0);
        if ($val < 0) {
            $val = 0.0;
        }
        if ($val > $maxVal) {
            $maxVal = $val;
        }
        $items[] = [
            'label' => $label,
            'val'   => $val,
        ];
    }

    $scaleMax = $maxVal > 0.0 ? $maxVal : 1.0;

    $width = 800.0;
    $height = 320.0;
    $padLeft = 95.0;
    $padRight = 24.0;
    $padTop = 26.0;
    $padBottom = 64.0;
    $plotW = $width - $padLeft - $padRight;
    $plotH = $height - $padTop - $padBottom;

    $out = [];
    $out[] = sprintf(
        '<svg viewBox="0 0 %.0f %.0f" width="100%%" height="300" class="chart-svg" role="img" aria-label="Grafik Penjualan Harian" xmlns="http://www.w3.org/2000/svg">',
        $width,
        $height
    );
    $out[] = '<rect x="0" y="0" width="800" height="320" fill="#ffffff" rx="6" />';

    // Gridlines & Y-axis labels (0%, 25%, 50%, 75%, 100%)
    $ticks = 4;
    for ($t = 0; $t <= $ticks; $t++) {
        $ratio = $t / $ticks;
        $y = $padTop + $plotH - ($ratio * $plotH);
        $tickVal = $maxVal > 0.0 ? ($ratio * $scaleMax) : 0.0;
        $stroke = $t === 0 ? '#64748b' : '#e2e8f0';
        $strokeW = $t === 0 ? '1.5' : '1';
        $out[] = sprintf(
            '<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="%s" stroke-width="%s" />',
            $padLeft,
            $y,
            $padLeft + $plotW,
            $y,
            $stroke,
            $strokeW
        );
        $out[] = sprintf(
            '<text x="%.1f" y="%.1f" text-anchor="end" font-size="11" fill="#475569" dominant-baseline="middle">%s</text>',
            $padLeft - 8.0,
            $y,
            e(rupiah($tickVal))
        );
    }

    // Y-axis line
    $out[] = sprintf(
        '<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#64748b" stroke-width="1.5" />',
        $padLeft,
        $padTop,
        $padLeft,
        $padTop + $plotH
    );

    $n = count($items);
    $slotW = $plotW / $n;
    $barW = max(8.0, min(56.0, $slotW * 0.62));
    $rotateX = $n > 8;

    foreach ($items as $i => $item) {
        $val = $item['val'];
        $label = $item['label'];
        $barH = $maxVal > 0.0 ? ($val / $scaleMax) * $plotH : 0.0;
        if ($val > 0.0 && $barH < 2.0) {
            $barH = 2.0;
        }
        $x = $padLeft + ($i * $slotW) + (($slotW - $barW) / 2.0);
        $y = $padTop + $plotH - $barH;
        $centerX = $x + ($barW / 2.0);

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            $shortDate = mb_strlen($label, 'UTF-8') >= 10
                ? mb_substr($label, 5, 5, 'UTF-8')
                : $label;
        } else {
            $shortDate = strlen($label) >= 10 ? substr($label, 5, 5) : $label;
        }
        $tooltip = ($label !== '' ? $label : '-') . ': ' . rupiah($val);

        $out[] = '<g class="chart-bar-group">';
        $out[] = '<title>' . e($tooltip) . '</title>';
        if ($barH > 0.0) {
            $out[] = sprintf(
                '<rect x="%.2f" y="%.2f" width="%.2f" height="%.2f" rx="3" fill="#1b63a8" />',
                $x,
                $y,
                $barW,
                $barH
            );
        } else {
            $out[] = sprintf(
                '<rect x="%.2f" y="%.2f" width="%.2f" height="2" fill="#cbd5e1" />',
                $x,
                $padTop + $plotH - 2.0,
                $barW
            );
        }

        if ($n <= 14) {
            $valText = $val >= 1000000
                ? number_format($val / 1000000, 1, ',', '.') . ' jt'
                : ($val >= 1000 ? number_format($val / 1000, 0, ',', '.') . ' rb' : number_format($val, 0, ',', '.'));
            $out[] = sprintf(
                '<text x="%.2f" y="%.2f" text-anchor="middle" font-size="10.5" font-weight="600" fill="#1e293b">%s</text>',
                $centerX,
                max($padTop - 6.0, $y - 6.0),
                e($valText)
            );
        }

        $labelY = $padTop + $plotH + 16.0;
        if ($rotateX) {
            $out[] = sprintf(
                '<text x="%.2f" y="%.2f" text-anchor="end" font-size="11" fill="#334155" transform="rotate(-25 %.2f %.2f)">%s</text>',
                $centerX + 4.0,
                $labelY + 2.0,
                $centerX + 4.0,
                $labelY + 2.0,
                e($shortDate)
            );
        } else {
            $out[] = sprintf(
                '<text x="%.2f" y="%.2f" text-anchor="middle" font-size="11" fill="#334155">%s</text>',
                $centerX,
                $labelY,
                e($label)
            );
        }

        $out[] = '</g>';
    }

    $out[] = '</svg>';

    return implode("\n", $out);
}
