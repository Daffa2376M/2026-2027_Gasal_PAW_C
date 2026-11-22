<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/lib/helpers.php';

requireLogin();

$pdo = getPDO();

function txPostString(string $key): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : '';
}

function txParsePositiveInt(mixed $raw, int $max = 2147483647): int
{
    if (is_int($raw)) {
        return ($raw >= 1 && $raw <= $max) ? $raw : 0;
    }

    if (!is_string($raw)) {
        return 0;
    }

    $trimmed = trim($raw);
    if ($trimmed === '' || strlen($trimmed) > 10 || preg_match('/^\d+$/', $trimmed) !== 1) {
        return 0;
    }

    $val = (int) $trimmed;
    return ($val >= 1 && $val <= $max) ? $val : 0;
}

function txIsValidDateYmd(string $val): bool
{
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val) !== 1) {
        return false;
    }

    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $val);
    return $dt !== false && $dt->format('Y-m-d') === $val;
}

function txRecomputeTotal(PDO $pdo, int $transaksiId): void
{
    $stmtTotal = $pdo->prepare(
        'UPDATE transaksi
         SET total = (
             SELECT COALESCE(SUM(harga * qty), 0)
             FROM transaksi_detail
             WHERE transaksi_id = :tid_sub
         )
         WHERE id = :tid_where'
    );
    $stmtTotal->execute([
        'tid_sub'   => $transaksiId,
        'tid_where' => $transaksiId,
    ]);

    $stmtPay = $pdo->prepare(
        'UPDATE pembayaran
         SET total = (
             SELECT total
             FROM transaksi
             WHERE id = :tid_sub
         )
         WHERE transaksi_id = :tid_where'
    );
    $stmtPay->execute([
        'tid_sub'   => $transaksiId,
        'tid_where' => $transaksiId,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = txPostString('aksi');

    if ($aksi === 'tambah_transaksi') {
        $waktu = txPostString('waktu_transaksi');
        $pelangganId = txParsePositiveInt($_POST['pelanggan_id'] ?? null);
        $keterangan = txPostString('keterangan');

        if (!txIsValidDateYmd($waktu)) {
            setFlash('danger', 'Tanggal transaksi tidak valid (gunakan format YYYY-MM-DD).');
            redirect('transaksi.php');
        }

        if ($pelangganId <= 0) {
            setFlash('danger', 'Pelanggan wajib dipilih dengan ID yang valid.');
            redirect('transaksi.php');
        }

        if (mb_strlen($keterangan, 'UTF-8') > 500) {
            setFlash('danger', 'Keterangan maksimal 500 karakter.');
            redirect('transaksi.php');
        }

        try {
            $stmtPel = $pdo->prepare('SELECT id FROM pelanggan WHERE id = :id LIMIT 1');
            $stmtPel->execute(['id' => $pelangganId]);
            if ($stmtPel->fetch() === false) {
                setFlash('danger', 'Pelanggan tidak valid atau tidak ditemukan.');
                redirect('transaksi.php');
            }

            $stmtIns = $pdo->prepare(
                'INSERT INTO transaksi (waktu_transaksi, pelanggan_id, keterangan, total)
                 VALUES (:waktu, :pelanggan_id, :keterangan, 0)'
            );
            $stmtIns->execute([
                'waktu'        => $waktu,
                'pelanggan_id' => $pelangganId,
                'keterangan'   => $keterangan,
            ]);

            $newId = (int) $pdo->lastInsertId();
            setFlash('success', 'Transaksi #' . $newId . ' berhasil dibuat. Silakan tambahkan detail barang.');
            redirect('transaksi.php?id=' . $newId);
        } catch (PDOException $e) {
            error_log('transaksi.php tambah_transaksi error: ' . $e->getMessage());
            setFlash('danger', 'Gagal membuat transaksi baru.');
            redirect('transaksi.php');
        }
    }

    if ($aksi === 'hapus_transaksi') {
        $transaksiId = txParsePositiveInt($_POST['transaksi_id'] ?? ($_POST['id'] ?? null));
        if ($transaksiId <= 0) {
            setFlash('danger', 'ID transaksi tidak valid.');
            redirect('transaksi.php');
        }

        try {
            $pdo->beginTransaction();

            $stmtLock = $pdo->prepare('SELECT id FROM transaksi WHERE id = :id FOR UPDATE');
            $stmtLock->execute(['id' => $transaksiId]);
            if ($stmtLock->fetch() === false) {
                $pdo->rollBack();
                setFlash('warning', 'Transaksi tidak ditemukan atau sudah dihapus.');
                redirect('transaksi.php');
            }

            $stmtDelPay = $pdo->prepare('DELETE FROM pembayaran WHERE transaksi_id = :id');
            $stmtDelPay->execute(['id' => $transaksiId]);

            $stmtDelDet = $pdo->prepare('DELETE FROM transaksi_detail WHERE transaksi_id = :id');
            $stmtDelDet->execute(['id' => $transaksiId]);

            $stmtDelTrx = $pdo->prepare('DELETE FROM transaksi WHERE id = :id');
            $stmtDelTrx->execute(['id' => $transaksiId]);

            $pdo->commit();
            setFlash('success', 'Transaksi #' . $transaksiId . ' berhasil dihapus.');
            redirect('transaksi.php');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('transaksi.php hapus_transaksi error: ' . $e->getMessage());
            setFlash('danger', 'Gagal menghapus transaksi.');
            redirect('transaksi.php');
        }
    }

    if ($aksi === 'tambah_detail') {
        $transaksiId = txParsePositiveInt($_POST['transaksi_id'] ?? null);
        if ($transaksiId <= 0) {
            setFlash('danger', 'Transaksi tidak valid.');
            redirect('transaksi.php');
        }

        $barangId = txParsePositiveInt($_POST['barang_id'] ?? null);
        if ($barangId <= 0) {
            setFlash('danger', 'Barang wajib dipilih dengan ID yang valid.');
            redirect('transaksi.php?id=' . $transaksiId);
        }

        $rawQty = $_POST['qty'] ?? null;
        if (is_int($rawQty)) {
            $qtyInt = $rawQty;
        } elseif (is_string($rawQty) && preg_match('/^-?\d+$/', trim($rawQty)) === 1) {
            $trimmedQty = trim($rawQty);
            if (strlen(ltrim($trimmedQty, '-')) > 10) {
                $qtyInt = str_starts_with($trimmedQty, '-') ? -1 : 1000001;
            } else {
                $qtyInt = (int) $trimmedQty;
            }
        } else {
            $qtyInt = 0;
        }

        if ($qtyInt <= 0) {
            setFlash('danger', 'Jumlah (qty) harus lebih dari 0.');
            redirect('transaksi.php?id=' . $transaksiId);
        }

        if ($qtyInt > 1000000) {
            setFlash('danger', 'Jumlah (qty) melebihi batas maksimal (1.000.000).');
            redirect('transaksi.php?id=' . $transaksiId);
        }

        try {
            $pdo->beginTransaction();

            $stmtTrx = $pdo->prepare('SELECT id FROM transaksi WHERE id = :tid FOR UPDATE');
            $stmtTrx->execute(['tid' => $transaksiId]);
            if ($stmtTrx->fetch() === false) {
                $pdo->rollBack();
                setFlash('danger', 'Transaksi tidak ditemukan.');
                redirect('transaksi.php');
            }

            $stmtBrg = $pdo->prepare('SELECT id, harga, stok FROM barang WHERE id = :bid FOR UPDATE');
            $stmtBrg->execute(['bid' => $barangId]);
            $barang = $stmtBrg->fetch();
            if ($barang === false) {
                $pdo->rollBack();
                setFlash('danger', 'Barang tidak ditemukan.');
                redirect('transaksi.php?id=' . $transaksiId);
            }

            $stmtDup = $pdo->prepare(
                'SELECT 1 FROM transaksi_detail WHERE transaksi_id = :tid AND barang_id = :bid LIMIT 1'
            );
            $stmtDup->execute(['tid' => $transaksiId, 'bid' => $barangId]);
            if ($stmtDup->fetchColumn() !== false) {
                $pdo->rollBack();
                setFlash('warning', 'Barang sudah ada dalam transaksi ini.');
                redirect('transaksi.php?id=' . $transaksiId);
            }

            $harga = (int) $barang['harga'];
            $lineSubtotal = (float) $harga * (float) $qtyInt;
            $stmtSum = $pdo->prepare(
                'SELECT COALESCE(SUM(harga * qty), 0) FROM transaksi_detail WHERE transaksi_id = :tid'
            );
            $stmtSum->execute(['tid' => $transaksiId]);
            $currentSum = (float) $stmtSum->fetchColumn();

            if ($harga < 0 || $lineSubtotal > 2147483647.0 || ($currentSum + $lineSubtotal) > 2147483647.0) {
                $pdo->rollBack();
                setFlash('danger', 'Total transaksi melebihi batas maksimal.');
                redirect('transaksi.php?id=' . $transaksiId);
            }

            $stmtInsDet = $pdo->prepare(
                'INSERT INTO transaksi_detail (transaksi_id, barang_id, harga, qty)
                 VALUES (:tid, :bid, :harga, :qty)'
            );
            $stmtInsDet->execute([
                'tid'   => $transaksiId,
                'bid'   => $barangId,
                'harga' => $harga,
                'qty'   => $qtyInt,
            ]);

            txRecomputeTotal($pdo, $transaksiId);

            $pdo->commit();
            setFlash('success', 'Item barang berhasil ditambahkan ke transaksi #' . $transaksiId . '.');
            redirect('transaksi.php?id=' . $transaksiId);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('transaksi.php tambah_detail error: ' . $e->getMessage());
            setFlash('danger', 'Gagal menyimpan detail transaksi.');
            redirect('transaksi.php?id=' . $transaksiId);
        }
    }

    if ($aksi === 'hapus_detail') {
        $transaksiId = txParsePositiveInt($_POST['transaksi_id'] ?? null);
        $barangId = txParsePositiveInt($_POST['hapus_barang_id'] ?? ($_POST['barang_id'] ?? null));

        if ($transaksiId <= 0 || $barangId <= 0) {
            setFlash('danger', 'Data detail transaksi tidak valid.');
            redirect($transaksiId > 0 ? 'transaksi.php?id=' . $transaksiId : 'transaksi.php');
        }

        try {
            $pdo->beginTransaction();

            $stmtTrx = $pdo->prepare('SELECT id FROM transaksi WHERE id = :tid FOR UPDATE');
            $stmtTrx->execute(['tid' => $transaksiId]);
            if ($stmtTrx->fetch() === false) {
                $pdo->rollBack();
                setFlash('danger', 'Transaksi tidak ditemukan.');
                redirect('transaksi.php');
            }

            $stmtDel = $pdo->prepare(
                'DELETE FROM transaksi_detail WHERE transaksi_id = :tid AND barang_id = :bid'
            );
            $stmtDel->execute(['tid' => $transaksiId, 'bid' => $barangId]);
            if ($stmtDel->rowCount() === 0) {
                $pdo->rollBack();
                setFlash('warning', 'Detail item tidak ditemukan.');
                redirect('transaksi.php?id=' . $transaksiId);
            }

            txRecomputeTotal($pdo, $transaksiId);

            $pdo->commit();
            setFlash('success', 'Item detail berhasil dihapus.');
            redirect('transaksi.php?id=' . $transaksiId);
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('transaksi.php hapus_detail error: ' . $e->getMessage());
            setFlash('danger', 'Gagal menghapus detail transaksi.');
            redirect('transaksi.php?id=' . $transaksiId);
        }
    }

    setFlash('danger', 'Aksi tidak dikenali.');
    redirect('transaksi.php');
}

$hasIdParam = array_key_exists('id', $_GET);
$selectedId = txParsePositiveInt($_GET['id'] ?? null);

$selectedTransaksi = null;
$detailList = [];
$barangList = [];
$notFoundNotice = '';
$pelangganList = [];
$transaksiList = [];
$dbLoadError = '';

try {
    if ($selectedId > 0) {
        $stmtOne = $pdo->prepare(
            'SELECT t.id, t.waktu_transaksi, t.keterangan, t.total, t.pelanggan_id,
                    p.nama AS pelanggan_nama
             FROM transaksi t
             INNER JOIN pelanggan p ON p.id = t.pelanggan_id
             WHERE t.id = :id
             LIMIT 1'
        );
        $stmtOne->execute(['id' => $selectedId]);
        $row = $stmtOne->fetch();
        if ($row !== false) {
            $selectedTransaksi = $row;
        }
    }

    if ($hasIdParam && $selectedTransaksi === null) {
        $notFoundNotice = 'Transaksi tidak ditemukan.';
    }

    if ($selectedTransaksi !== null) {
        $stmtDet = $pdo->prepare(
            'SELECT d.transaksi_id, d.barang_id, d.harga, d.qty,
                    (d.harga * d.qty) AS subtotal,
                    b.kode_barang, b.nama_barang
             FROM transaksi_detail d
             INNER JOIN barang b ON b.id = d.barang_id
             WHERE d.transaksi_id = :tid
             ORDER BY b.nama_barang ASC'
        );
        $stmtDet->execute(['tid' => $selectedId]);
        $detailList = $stmtDet->fetchAll();

        $barangList = $pdo->query(
            'SELECT id, kode_barang, nama_barang, harga, stok FROM barang ORDER BY nama_barang ASC'
        )->fetchAll();
    }

    $pelangganList = $pdo->query('SELECT id, nama FROM pelanggan ORDER BY nama ASC')->fetchAll();
    $transaksiList = $pdo->query(
        'SELECT t.id, t.waktu_transaksi, t.keterangan, t.total, p.nama AS pelanggan_nama
         FROM transaksi t
         INNER JOIN pelanggan p ON p.id = t.pelanggan_id
         ORDER BY t.waktu_transaksi DESC, t.id DESC'
    )->fetchAll();
} catch (PDOException $e) {
    error_log('transaksi.php load error: ' . $e->getMessage());
    $dbLoadError = 'Gagal memuat data transaksi.';
}

$pageTitle = 'Transaksi - Sistem Penjualan';
$activeMenu = 'transaksi';
require __DIR__ . '/partials/header.php';
?>

<?php if ($dbLoadError !== ''): ?>
    <div class="alert alert-danger" role="alert"><?= e($dbLoadError) ?></div>
<?php endif; ?>

<?php if ($notFoundNotice !== ''): ?>
    <div class="alert alert-warning" role="alert"><?= e($notFoundNotice) ?></div>
<?php endif; ?>

<?php if ($selectedTransaksi !== null): ?>
    <section class="card">
        <div class="page-header">
            <div>
                <h1 class="page-title">Detail Transaksi #<?= (int) $selectedTransaksi['id'] ?></h1>
                <p class="muted" style="margin: 4px 0 0;">
                    Tanggal: <strong><?= e($selectedTransaksi['waktu_transaksi']) ?></strong>
                    &bull;
                    Pelanggan: <strong><?= e($selectedTransaksi['pelanggan_nama']) ?></strong>
                    &bull;
                    Keterangan: <?= e(($selectedTransaksi['keterangan'] !== null && $selectedTransaksi['keterangan'] !== '') ? $selectedTransaksi['keterangan'] : '-') ?>
                    &bull;
                    Total: <strong><?= e(rupiah($selectedTransaksi['total'])) ?></strong>
                </p>
            </div>
            <div class="actions">
                <a href="transaksi.php" class="btn btn-secondary btn-sm">Tutup Detail</a>
                <a href="laporan.php" class="btn btn-secondary btn-sm">Laporan Penjualan</a>
            </div>
        </div>

        <h2 class="section-title">Daftar Item Barang</h2>
        <?php if ($detailList === []): ?>
            <p class="pesan-kosong">Belum ada item barang pada transaksi ini.</p>
        <?php else: ?>
            <div class="table-wrap" style="margin-bottom: 18px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th class="kolom-angka">Harga</th>
                            <th class="kolom-angka">Qty</th>
                            <th class="kolom-angka">Subtotal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; ?>
                        <?php foreach ($detailList as $det): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= e($det['kode_barang']) ?></td>
                                <td><?= e($det['nama_barang']) ?></td>
                                <td class="kolom-angka"><?= e(rupiah($det['harga'])) ?></td>
                                <td class="kolom-angka"><?= (int) $det['qty'] ?></td>
                                <td class="kolom-angka"><?= e(rupiah($det['subtotal'])) ?></td>
                                <td class="actions">
                                    <form method="post"
                                          action="transaksi.php?id=<?= (int) $selectedTransaksi['id'] ?>"
                                          class="inline"
                                          onsubmit="return confirm('Hapus item ini dari transaksi?');">
                                        <input type="hidden" name="aksi" value="hapus_detail">
                                        <input type="hidden" name="transaksi_id" value="<?= (int) $selectedTransaksi['id'] ?>">
                                        <input type="hidden" name="hapus_barang_id" value="<?= (int) $det['barang_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="5" class="text-right"><strong>Total Transaksi</strong></td>
                            <td class="kolom-angka"><strong><?= e(rupiah($selectedTransaksi['total'])) ?></strong></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <h2 class="section-title">Tambah Item Barang</h2>
        <?php if ($barangList === []): ?>
            <p class="pesan-kosong">Data barang belum tersedia.</p>
        <?php else: ?>
            <form method="post" action="transaksi.php?id=<?= (int) $selectedTransaksi['id'] ?>">
                <input type="hidden" name="aksi" value="tambah_detail">
                <input type="hidden" name="transaksi_id" value="<?= (int) $selectedTransaksi['id'] ?>">

                <div class="form-row">
                    <label for="barang_id">Barang</label>
                    <div>
                        <select id="barang_id" name="barang_id" class="form-control" required>
                            <option value="">-- Pilih Barang --</option>
                            <?php foreach ($barangList as $b): ?>
                                <option value="<?= (int) $b['id'] ?>">
                                    <?= e($b['kode_barang'] . ' - ' . $b['nama_barang'] . ' (' . rupiah($b['harga']) . ' | Stok: ' . (int) $b['stok'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <label for="qty">Jumlah (Qty)</label>
                    <div>
                        <input type="number"
                               id="qty"
                               name="qty"
                               class="form-control"
                               min="1"
                               max="1000000"
                               value="1"
                               required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Tambah Item</button>
                </div>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="card">
    <div class="page-header">
        <?php if ($selectedTransaksi === null): ?>
            <h1 class="page-title">Transaksi Penjualan</h1>
        <?php else: ?>
            <h2 class="section-title" style="margin: 0;">Buat Transaksi Baru</h2>
        <?php endif; ?>
        <a href="laporan.php" class="btn btn-secondary btn-sm">Laporan Penjualan</a>
    </div>

    <?php if ($selectedTransaksi === null): ?>
        <h2 class="section-title">Buat Transaksi Baru</h2>
    <?php endif; ?>

    <form method="post" action="transaksi.php">
        <input type="hidden" name="aksi" value="tambah_transaksi">

        <div class="form-row">
            <label for="waktu_transaksi">Tanggal Transaksi</label>
            <div>
                <input type="date"
                       id="waktu_transaksi"
                       name="waktu_transaksi"
                       class="form-control"
                       value="<?= e(date('Y-m-d')) ?>"
                       required>
            </div>
        </div>

        <div class="form-row">
            <label for="pelanggan_id">Pelanggan</label>
            <div>
                <select id="pelanggan_id" name="pelanggan_id" class="form-control" required>
                    <option value="">-- Pilih Pelanggan --</option>
                    <?php foreach ($pelangganList as $pel): ?>
                        <option value="<?= (int) $pel['id'] ?>"><?= e($pel['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-row">
            <label for="keterangan">Keterangan</label>
            <div>
                <input type="text"
                       id="keterangan"
                       name="keterangan"
                       class="form-control"
                       maxlength="500"
                       placeholder="Catatan transaksi (opsional)">
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Buat Transaksi</button>
        </div>
    </form>
</section>

<section class="card">
    <h2 class="section-title">Daftar Transaksi</h2>
    <?php if ($transaksiList === []): ?>
        <p class="pesan-kosong">Belum ada data transaksi.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Keterangan</th>
                        <th class="kolom-angka">Total</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transaksiList as $trx): ?>
                        <tr>
                            <td>#<?= (int) $trx['id'] ?></td>
                            <td><?= e($trx['waktu_transaksi']) ?></td>
                            <td><?= e($trx['pelanggan_nama']) ?></td>
                            <td><?= e(($trx['keterangan'] !== null && $trx['keterangan'] !== '') ? $trx['keterangan'] : '-') ?></td>
                            <td class="kolom-angka"><?= e(rupiah($trx['total'])) ?></td>
                            <td class="actions">
                                <a class="btn btn-primary btn-sm" href="transaksi.php?id=<?= (int) $trx['id'] ?>">Detail</a>
                                <form method="post"
                                      action="transaksi.php"
                                      class="inline"
                                      onsubmit="return confirm('Hapus transaksi #<?= (int) $trx['id'] ?> beserta detailnya?');">
                                    <input type="hidden" name="aksi" value="hapus_transaksi">
                                    <input type="hidden" name="transaksi_id" value="<?= (int) $trx['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php
require __DIR__ . '/partials/footer.php';
