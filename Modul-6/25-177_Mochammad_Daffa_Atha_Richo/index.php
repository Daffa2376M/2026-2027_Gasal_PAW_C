<?php
require 'koneksi.php';

function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function rupiah($value) { return 'Rp ' . number_format((int)$value, 0, ',', '.'); }

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$pesan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    try {
        if ($aksi === 'detail') {
            $id = filter_input(INPUT_POST, 'transaksi_id', FILTER_VALIDATE_INT) ?: 0;
            $barangId = filter_input(INPUT_POST, 'barang_id', FILTER_VALIDATE_INT) ?: 0;
            $qty = filter_input(INPUT_POST, 'qty', FILTER_VALIDATE_INT) ?: 0;
            if ($id < 1 || $barangId < 1 || $qty < 1) throw new RuntimeException('Pilih barang dan isi jumlah yang valid.');
            $conn->begin_transaction();
            $transaksi = $conn->execute_query('SELECT id FROM transaksi WHERE id = ? FOR UPDATE', [$id])->fetch_assoc();
            $barang = $conn->execute_query('SELECT harga FROM barang WHERE id = ? FOR UPDATE', [$barangId])->fetch_assoc();
            if (!$transaksi || !$barang) throw new RuntimeException('Transaksi atau barang tidak ditemukan.');
            if ($conn->execute_query('SELECT 1 FROM transaksi_detail WHERE transaksi_id = ? AND barang_id = ?', [$id, $barangId])->fetch_assoc()) {
                throw new RuntimeException('Barang sudah ada dalam transaksi ini.');
            }
            $conn->execute_query('INSERT INTO transaksi_detail (transaksi_id, barang_id, harga, qty) VALUES (?, ?, ?, ?)', [$id, $barangId, $barang['harga'], $qty]);
            $conn->execute_query('UPDATE transaksi SET total = (SELECT COALESCE(SUM(harga * qty), 0) FROM transaksi_detail WHERE transaksi_id = ?) WHERE id = ?', [$id, $id]);
            $conn->commit();
            $pesan = 'Detail transaksi berhasil disimpan.';
        } elseif ($aksi === 'hapus_barang') {
            $barangId = filter_input(INPUT_POST, 'barang_id', FILTER_VALIDATE_INT) ?: 0;
            $conn->begin_transaction();
            $barang = $conn->execute_query('SELECT id FROM barang WHERE id = ? FOR UPDATE', [$barangId])->fetch_assoc();
            if (!$barang) throw new RuntimeException('Barang tidak ditemukan.');
            if ($conn->execute_query('SELECT 1 FROM transaksi_detail WHERE barang_id = ? LIMIT 1', [$barangId])->fetch_assoc()) {
                throw new RuntimeException('Barang masih digunakan pada transaksi dan tidak dapat dihapus.');
            }
            $conn->execute_query('DELETE FROM barang WHERE id = ?', [$barangId]);
            $conn->commit();
            $pesan = 'Barang berhasil dihapus.';
        } elseif ($aksi === 'tambah_transaksi') {
            $tanggal = $_POST['tanggal'] ?? '';
            $pelanggan = $_POST['pelanggan_id'] ?? '';
            $keterangan = trim($_POST['keterangan'] ?? '');
            if (!$tanggal || !$pelanggan) throw new RuntimeException('Tanggal dan pelanggan wajib diisi.');
            $conn->execute_query('INSERT INTO transaksi (waktu_transaksi, pelanggan_id, keterangan) VALUES (?, ?, ?)', [$tanggal, $pelanggan, $keterangan]);
            $id = $conn->insert_id;
            $pesan = 'Transaksi berhasil ditambahkan.';
        }
    } catch (Throwable $e) {
        if ($aksi === 'detail' || $aksi === 'hapus_barang') $conn->rollback();
        $pesan = $e instanceof RuntimeException && !($e instanceof mysqli_sql_exception) ? $e->getMessage() : 'Operasi gagal. Periksa data yang dimasukkan.';
    }
}
$transaksi = $conn->query('SELECT t.*, p.nama FROM transaksi t JOIN pelanggan p ON p.id = t.pelanggan_id ORDER BY t.id DESC')->fetch_all(MYSQLI_ASSOC);
$pelanggan = $conn->query('SELECT id, nama FROM pelanggan ORDER BY nama')->fetch_all(MYSQLI_ASSOC);
$semuaBarang = $conn->query('SELECT id, kode_barang, nama_barang, harga FROM barang ORDER BY nama_barang')->fetch_all(MYSQLI_ASSOC);
$dipilih = $id ? $conn->execute_query('SELECT t.*, p.nama FROM transaksi t JOIN pelanggan p ON p.id = t.pelanggan_id WHERE t.id = ?', [$id])->fetch_assoc() : null;
$detail = $dipilih ? $conn->execute_query('SELECT d.*, b.nama_barang FROM transaksi_detail d JOIN barang b ON b.id = d.barang_id WHERE d.transaksi_id = ? ORDER BY b.nama_barang', [$id])->fetch_all(MYSQLI_ASSOC) : [];
$tersedia = $dipilih ? $conn->execute_query('SELECT b.id, b.nama_barang, b.harga FROM barang b WHERE NOT EXISTS (SELECT 1 FROM transaksi_detail d WHERE d.transaksi_id = ? AND d.barang_id = b.id) ORDER BY b.nama_barang', [$id])->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Data Transaksi</title><link rel="stylesheet" href="style.css"></head>
<body><main class="container">
<h1>Data Transaksi</h1>
<?php if ($pesan): ?><p role="status"><strong><?= h($pesan) ?></strong></p><?php endif; ?>
<details><summary>Tambah transaksi</summary>
<form method="post"><input type="hidden" name="aksi" value="tambah_transaksi">
<label>Tanggal <input type="date" name="tanggal" required></label>
<label>Pelanggan <select name="pelanggan_id" required><option value="">Pilih pelanggan</option><?php foreach ($pelanggan as $p): ?><option value="<?= h($p['id']) ?>"><?= h($p['nama']) ?></option><?php endforeach; ?></select></label>
<label>Keterangan <input name="keterangan"></label><button>Simpan transaksi</button></form></details>
<table class="transaksi-table"><thead><tr><th>ID</th><th>Tanggal</th><th>Pelanggan</th><th>Total</th><th>Detail</th></tr></thead><tbody>
<?php foreach ($transaksi as $t): ?><tr><td><?= h($t['id']) ?></td><td><?= h($t['waktu_transaksi']) ?></td><td><?= h($t['nama']) ?></td><td><?= rupiah($t['total']) ?></td><td class="detail-cell"><a href="?id=<?= (int)$t['id'] ?>#detail">Lihat detail</a></td></tr><?php endforeach; ?>
<?php if (!$transaksi): ?><tr><td colspan="5">Belum ada transaksi.</td></tr><?php endif; ?></tbody></table>
<section id="detail"><h2>Detail transaksi</h2>
<?php if ($dipilih): ?><p>Transaksi #<?= h($dipilih['id']) ?> | <?= h($dipilih['nama']) ?> | <?= h($dipilih['waktu_transaksi']) ?> | Total <?= rupiah($dipilih['total']) ?></p>
<table><thead><tr><th>Barang</th><th>Harga</th><th>Qty</th><th>Subtotal</th></tr></thead><tbody><?php foreach ($detail as $d): ?><tr><td><?= h($d['nama_barang']) ?></td><td><?= rupiah($d['harga']) ?></td><td><?= h($d['qty']) ?></td><td><?= rupiah($d['harga'] * $d['qty']) ?></td></tr><?php endforeach; ?><?php if (!$detail): ?><tr><td colspan="4">Belum ada detail.</td></tr><?php endif; ?></tbody></table>
<?php if ($tersedia): ?><form method="post"><input type="hidden" name="aksi" value="detail"><input type="hidden" name="transaksi_id" value="<?= (int)$id ?>"><label>Barang <select name="barang_id" required><option value="">Pilih barang</option><?php foreach ($tersedia as $b): ?><option value="<?= (int)$b['id'] ?>"><?= h($b['nama_barang']) ?> (<?= rupiah($b['harga']) ?>)</option><?php endforeach; ?></select></label> <label>Jumlah <input type="number" name="qty" min="1" required></label> <button>Simpan detail</button></form><?php else: ?><p>Semua barang sudah tercatat dalam transaksi ini.</p><?php endif; ?>
<?php else: ?><p>Pilih transaksi untuk melihat dan menambah detail.</p><?php endif; ?></section>
<section><h2>Data barang</h2><table class="barang-table"><thead><tr><th>Kode</th><th>Barang</th><th>Harga</th><th>Aksi</th></tr></thead><tbody><?php foreach ($semuaBarang as $b): ?><tr><td><?= h($b['kode_barang']) ?></td><td><?= h($b['nama_barang']) ?></td><td><?= rupiah($b['harga']) ?></td><td><form method="post"><input type="hidden" name="aksi" value="hapus_barang"><input type="hidden" name="barang_id" value="<?= (int)$b['id'] ?>"><button onclick="return confirm('Hapus barang ini?')">Hapus</button></form></td></tr><?php endforeach; ?><?php if (!$semuaBarang): ?><tr><td colspan="4">Belum ada barang.</td></tr><?php endif; ?></tbody></table></section>
</main></body></html>
