<?php
$pageTitle ??= 'TP5';
$navTables = schema();
$navFlash = takeFlash();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
<header>
    <h1>TP5 - Modul Database</h1>
    <nav>
        <a href="index.php">Dashboard</a>
        <?php foreach ($navTables as $navSlug => $navTable): ?>
            <a href="tabel.php?t=<?= e($navSlug) ?>"><?= e($navTable['short']) ?></a>
        <?php endforeach; ?>
    </nav>
</header>
<main>
<?php foreach ($navFlash as $navMessage): ?>
    <div class="alert alert-<?= e($navMessage['type']) ?>"><?= e($navMessage['message']) ?></div>
<?php endforeach; ?>
