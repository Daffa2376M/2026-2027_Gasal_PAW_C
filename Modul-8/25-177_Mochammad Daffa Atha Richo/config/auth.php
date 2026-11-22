<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/helpers.php';

if (!function_exists('ensureSession')) {
/**
 * Memulai session PHP native secara aman dengan cookie HttpOnly dan SameSite=Lax.
 */
function ensureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (!headers_sent()) {
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');

        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

/**
 * Memeriksa apakah pengguna memiliki session login yang valid.
 */
function isLoggedIn(): bool
{
    ensureSession();

    return ($_SESSION['login'] ?? false) === true
        && isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['nama'], $_SESSION['level'])
        && (int) $_SESSION['user_id'] > 0
        && in_array((int) $_SESSION['level'], [1, 2], true);
}

/**
 * Mengembalikan data user yang sedang login, atau null jika belum login.
 *
 * @return array{id_user: int, username: string, nama: string, level: int}|null
 */
function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id_user'  => (int) $_SESSION['user_id'],
        'username' => (string) $_SESSION['username'],
        'nama'     => (string) $_SESSION['nama'],
        'level'    => (int) $_SESSION['level'],
    ];
}

/**
 * Melindungi halaman agar hanya dapat diakses oleh pengguna yang sudah login.
 * Mengirim header anti-cache agar halaman tidak disajikan kembali dari cache browser setelah logout.
 */
function requireLogin(): void
{
    ensureSession();

    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    }

    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

/**
 * Mencegah pengguna yang sudah login mengakses halaman login kembali.
 */
function requireGuest(): void
{
    ensureSession();

    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }

    if (isLoggedIn()) {
        redirect('index.php');
    }
}

/**
 * Membatasi akses halaman hanya untuk pengguna level 1 (Owner).
 * Mengembalikan HTTP 403 jika user sudah login tetapi bukan level 1.
 */
function requireOwner(): void
{
    requireLogin();

    $user = currentUser();
    if ($user === null || $user['level'] !== 1) {
        if (!headers_sent()) {
            http_response_code(403);
        }

        $pageTitle = 'Akses Ditolak - Sistem Penjualan';
        $activeMenu = '';
        require __DIR__ . '/../partials/header.php';
        ?>
        <section class="card error-box" role="alert">
            <p class="error-code">403</p>
            <h1 class="page-title">Akses Ditolak</h1>
            <p class="error-message">
                Maaf, halaman ini hanya dapat diakses oleh pengguna dengan level <strong>Owner (Level 1)</strong>.
            </p>
            <div class="error-actions">
                <a href="index.php" class="btn btn-primary">Kembali ke Home</a>
            </div>
        </section>
        <?php
        require __DIR__ . '/../partials/footer.php';
        exit;
    }
}
}

ensureSession();
