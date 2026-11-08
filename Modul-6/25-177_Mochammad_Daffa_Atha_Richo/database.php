<?php
require 'koneksi.php';

if ($conn->query('SHOW TABLES')->num_rows !== 0) {
    exit("Database tidak kosong. Inisialisasi dibatalkan agar data tidak tertimpa.\n");
}
try {
    $conn->multi_query(file_get_contents(__DIR__ . '/schema.sql'));
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Skema dan data contoh berhasil dibuat.\n";
} catch (Throwable $e) {
    exit("Inisialisasi gagal: " . $e->getMessage() . "\n");
}
