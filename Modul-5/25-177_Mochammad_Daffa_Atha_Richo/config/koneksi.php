<?php
declare(strict_types=1);

/*
 * Pengaturan koneksi database.
 * Nilai default cocok untuk MySQL/MariaDB lokal (XAMPP: root tanpa password).
 * Bisa diubah lewat environment variable tanpa menyentuh file ini.
 */
$host = getenv('TP5_DB_HOST') ?: '127.0.0.1';
$port = getenv('TP5_DB_PORT') ?: '3306';
$name = getenv('TP5_DB_NAME') ?: 'tp5';
$user = getenv('TP5_DB_USER') ?: 'root';
$pass = getenv('TP5_DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $exception) {
    http_response_code(500);
    exit(
        'Gagal terhubung ke database "' . $name . '". ' .
        'Impor database/tp5.sql terlebih dahulu, lalu periksa pengaturan di config/koneksi.php. ' .
        'Detail: ' . $exception->getMessage()
    );
}
