<?php
// Query laporan dipakai halaman laporan dan ekspor Excel supaya angka keduanya selalu sama.
function tanggalValid($tanggal) {
    $d = DateTime::createFromFormat('Y-m-d', $tanggal);
    return $d && $d->format('Y-m-d') === $tanggal;
}

function laporanPenjualan($conn, $mulai, $selesai) {
    $stmt = $conn->prepare(
        "SELECT DATE(tanggal) AS tgl, SUM(total) AS total_harian
         FROM transaksi
         WHERE tanggal BETWEEN ? AND ?
         GROUP BY DATE(tanggal)
         ORDER BY tgl ASC"
    );
    $stmt->bind_param("ss", $mulai, $selesai);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $stmtPelanggan = $conn->prepare(
        "SELECT COUNT(DISTINCT pelanggan) AS jml FROM transaksi WHERE tanggal BETWEEN ? AND ?"
    );
    $stmtPelanggan->bind_param("ss", $mulai, $selesai);
    $stmtPelanggan->execute();

    return [
        'rows' => $rows,
        'total_pendapatan' => array_sum(array_column($rows, 'total_harian')),
        'total_pelanggan' => (int)$stmtPelanggan->get_result()->fetch_assoc()['jml'],
    ];
}
