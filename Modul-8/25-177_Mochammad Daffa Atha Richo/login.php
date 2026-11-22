<?php
declare(strict_types=1);

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/koneksi.php';
require_once __DIR__ . '/lib/helpers.php';

requireGuest();

$username = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawUser = $_POST['username'] ?? '';
    $rawPass = $_POST['password'] ?? '';
    $username = is_string($rawUser) ? trim($rawUser) : '';
    $password = is_string($rawPass) ? $rawPass : '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $pdo = getPDO();
        $stmt = $pdo->prepare(
            'SELECT id_user, username, password, nama, level FROM `user` WHERE username = :username LIMIT 1'
        );
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();

        if (
            $row !== false
            && password_verify($password, (string) $row['password'])
            && in_array((int) $row['level'], [1, 2], true)
        ) {
            session_regenerate_id(true);
            $_SESSION['login'] = true;
            $_SESSION['user_id'] = (int) $row['id_user'];
            $_SESSION['username'] = (string) $row['username'];
            $_SESSION['nama'] = (string) $row['nama'];
            $_SESSION['level'] = (int) $row['level'];

            redirect('index.php');
        }

        $error = 'Username atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Sistem Penjualan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-wrapper">
    <section class="login-card" aria-labelledby="login-heading">
        <div class="login-brand">
            <div class="login-brand-icon" aria-hidden="true">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <h1 id="login-heading">Sistem Penjualan</h1>
            <p>Silakan login untuk melanjutkan</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php" novalidate>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text"
                       id="username"
                       name="username"
                       class="form-control"
                       value="<?= e($username) ?>"
                       required
                       autofocus
                       autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password"
                       id="password"
                       name="password"
                       class="form-control"
                       required
                       autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
    </section>
</div>
</body>
</html>
