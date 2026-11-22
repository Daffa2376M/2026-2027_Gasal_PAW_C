<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/crud.php';

requireOwner();

$tableKey = is_string($_GET['t'] ?? null) ? $_GET['t'] : '';
$config = getMasterSchema($tableKey);
if ($config === null) {
    renderMasterErrorPage(
        404,
        'Data Master Tidak Ditemukan',
        'Tabel data master yang diminta tidak ditemukan atau tidak valid.'
    );
}

$pdo = getPDO();
$rows = findMasterRows($pdo, $config);
$me = currentUser();
$pk = (string) $config['primary'];
$columns = $config['list']['columns'];

$pageTitle = $config['title'] . ' - Sistem Penjualan';
$activeMenu = 'master';
require __DIR__ . '/partials/header.php';
?>

<section class="card">
    <div class="page-header">
        <div>
            <h1 class="page-title"><?= e((string) $config['title']) ?></h1>
            <p class="muted" style="margin: 4px 0 0;">
                Kelola data <?= e((string) $config['name']) ?> pada sistem (khusus Owner Level 1).
            </p>
        </div>
        <a href="master_form.php?t=<?= e(urlencode($tableKey)) ?>" class="btn btn-success">
            + Tambah <?= e((string) $config['short']) ?>
        </a>
    </div>

    <?php if ($rows === []): ?>
        <p class="pesan-kosong">Belum ada data <?= e((string) $config['name']) ?>.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 52px;">No</th>
                        <?php foreach ($columns as $col): ?>
                            <?php $align = ($config['fields'][$col]['align'] ?? '') === 'right' ? ' class="kolom-angka"' : ''; ?>
                            <th<?= $align ?>><?= e(fieldLabel($config, $col)) ?></th>
                        <?php endforeach; ?>
                        <th style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $idx => $row): ?>
                        <?php $rowId = (int) ($row[$pk] ?? 0); ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <?php foreach ($columns as $col): ?>
                                <?php $align = ($config['fields'][$col]['align'] ?? '') === 'right' ? ' class="kolom-angka"' : ''; ?>
                                <td<?= $align ?>><?= e(displayValue($config, $col, $row[$col] ?? null)) ?></td>
                            <?php endforeach; ?>
                            <td>
                                <div class="actions">
                                    <a href="master_form.php?t=<?= e(urlencode($tableKey)) ?>&amp;id=<?= $rowId ?>"
                                       class="btn btn-warning btn-sm">Edit</a>

                                    <?php if ($tableKey === 'user' && $me !== null && $rowId === $me['id_user']): ?>
                                        <button type="button"
                                                class="btn btn-secondary btn-sm"
                                                disabled
                                                title="Akun yang sedang login tidak dapat dihapus">Akun Aktif</button>
                                    <?php else: ?>
                                        <form method="post"
                                              action="master_hapus.php"
                                              class="inline"
                                              onsubmit="return confirm('Yakin ingin menghapus data <?= e((string) $config['name']) ?> ini?');">
                                            <input type="hidden" name="t" value="<?= e($tableKey) ?>">
                                            <input type="hidden" name="id" value="<?= $rowId ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
