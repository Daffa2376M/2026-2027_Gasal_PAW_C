<?php
declare(strict_types=1);

/**
 * Mengembalikan koneksi PDO singleton ke database tp8_penjualan.
 * Konfigurasi mendukung override via environment variable TP8_DB_*.
 */
function getPDO(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('TP8_DB_HOST') !== false ? (string) getenv('TP8_DB_HOST') : '127.0.0.1';
    $port = getenv('TP8_DB_PORT') !== false ? (string) getenv('TP8_DB_PORT') : '3306';
    $name = getenv('TP8_DB_NAME') !== false ? (string) getenv('TP8_DB_NAME') : 'tp8_penjualan';
    $user = getenv('TP8_DB_USER') !== false ? (string) getenv('TP8_DB_USER') : 'root';
    $pass = getenv('TP8_DB_PASS') !== false ? (string) getenv('TP8_DB_PASS') : '';

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        error_log('Database connection error: ' . $e->getMessage());
        if (!headers_sent()) {
            http_response_code(500);
        }
        exit('Koneksi database gagal. Pastikan MariaDB berjalan dan database tp8_penjualan sudah diimpor.');
    }

    return $pdo;
}
