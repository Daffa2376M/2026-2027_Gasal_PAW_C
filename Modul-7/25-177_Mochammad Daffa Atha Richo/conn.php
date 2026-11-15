<?php
$servername = getenv('DB_HOST') ?: '127.0.0.1';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db = getenv('DB_NAME') ?: 'KampusDB';

if (is_file(__DIR__ . '/conn.local.php')) {
	require __DIR__ . '/conn.local.php';
}

try {
	$conn = mysqli_connect($servername, $username, $password, $db);
} catch (Exception $e) {
	die("Koneksi gagal " . $e->getMessage());
}
