<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/crud.php';

requireOwner();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $rawTable = $_POST['t'] ?? $_GET['t'] ?? '';
    $config = getMasterSchema($rawTable);
    $backUrl = $config !== null ? 'master.php?t=' . urlencode((string) $config['key']) : 'index.php';
    $backLabel = $config !== null ? 'Kembali ke ' . (string) $config['title'] : 'Kembali ke Home';

    if (!headers_sent()) {
        header('Allow: POST');
    }

    renderMasterErrorPage(
        405,
        'Metode Tidak Diizinkan',
        'Permintaan penghapusan data harus menggunakan metode POST.',
        $backUrl,
        $backLabel
    );
}

$tableKey = is_string($_POST['t'] ?? null)
    ? $_POST['t']
    : (is_string($_GET['t'] ?? null) ? $_GET['t'] : '');
$config = getMasterSchema($tableKey);
if ($config === null) {
    renderMasterErrorPage(
        404,
        'Data Master Tidak Ditemukan',
        'Tabel data master yang diminta tidak ditemukan atau tidak valid.'
    );
}

$listUrl = 'master.php?t=' . urlencode((string) $config['key']);
$id = parsePositiveIntId($_POST['id'] ?? null);
if ($id === null) {
    setFlash('danger', 'Parameter ID tidak valid.');
    redirect($listUrl);
}

$me = currentUser();
if ($config['key'] === 'user' && $me !== null && $id === $me['id_user']) {
    setFlash('danger', 'Anda tidak dapat menghapus akun user yang sedang login.');
    redirect($listUrl);
}

try {
    $deleted = deleteMasterRow(getPDO(), $config, $id);
    if ($deleted > 0) {
        setFlash('success', 'Data ' . (string) $config['name'] . ' berhasil dihapus.');
    } else {
        setFlash('danger', 'Data ' . (string) $config['name'] . ' tidak ditemukan.');
    }
} catch (PDOException $e) {
    setFlash('danger', describeCrudDbError($e));
}

redirect($listUrl);
