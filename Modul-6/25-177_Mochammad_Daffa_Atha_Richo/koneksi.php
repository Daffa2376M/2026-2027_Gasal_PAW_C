<?php 
$servername = getenv('DB_HOST') ?: '127.0.0.1';
$username = getenv('DB_USER') ?: 'tp6_paw';
$password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : 'tp6lokal';
$db = getenv('DB_NAME') ?: 'tp6_penjualan';
$port = (int) (getenv('DB_PORT') ?: 3308);

try {
	$conn = mysqli_connect($servername, $username, $password, $db, $port);
} catch (mysqli_sql_exception $e) {
	error_log($e->getMessage());
	http_response_code(503);
	exit('Koneksi database gagal. Periksa layanan MySQL dan pengaturan koneksi.');
}
 ?>
