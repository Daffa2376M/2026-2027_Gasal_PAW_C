<?php
if (file_exists(__DIR__ . '/soal-3.php')) {
    require_once __DIR__ . '/soal-3.php';
} else {
    require_once __DIR__ . '/validate.inc';
}

$errors = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateName($errors, $_POST, 'surname');

    if ($errors) {
        echo 'Errors:<br/>';
        foreach ($errors as $field => $error) {
            echo "$field $error</br>";
        }
    } else {
        echo 'Data OK!';
    }
} else {
    echo 'Silakan kirim form melalui metode POST.';
}
?>
