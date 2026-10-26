<?php
if (file_exists(__DIR__ . '/soal-1.php')) {
    require_once __DIR__ . '/soal-1.php';
} else {
    require_once __DIR__ . '/validate.inc';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (validateName($_POST, 'surname')) {
        echo 'Data OK!';
    } else {
        echo 'Data invalid!';
    }
} else {
    echo 'Silakan kirim form melalui metode POST.';
}
?>
