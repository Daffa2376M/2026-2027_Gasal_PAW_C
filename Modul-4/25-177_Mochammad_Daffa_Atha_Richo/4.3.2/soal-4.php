<?php
$errors = array();

if (isset($_POST['submit'])) {
    require 'validate.inc';

    validateName($errors, $_POST, 'surname');
    validateEmail($errors, $_POST, 'email');
    validatePassword($errors, $_POST, 'password', 6);
    validateRequired($errors, $_POST, 'address');
    validateRequired($errors, $_POST, 'state');
    validateRequired($errors, $_POST, 'gender');

    if ($errors) {
        echo '<h1>Invalid, correct the following errors:</h1>';
        foreach ($errors as $field => $error) {
            echo "• <strong>$field</strong>: $error<br/>";
        }
        include 'form_full.inc';
    } else {
        echo '<h2 style="color: green;">Form submitted successfully with no errors</h2>';
        echo '<p>Seluruh field masukan telah tervalidasi dengan benar.</p>';
    }
} else {
    include 'form_full.inc';
}
?>
