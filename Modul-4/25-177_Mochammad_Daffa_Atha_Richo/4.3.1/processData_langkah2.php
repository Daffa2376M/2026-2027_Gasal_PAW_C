<?php
require 'validate_langkah1.inc';

if (validateName($_POST, 'surname'))
    echo 'Data OK!';
else
    echo 'Data invalid!';
?>
