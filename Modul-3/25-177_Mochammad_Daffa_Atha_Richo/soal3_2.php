<?php
$weight = array("Andy" => "70", "Barry" => "65", "Charlie" => "75");

echo "weight = (";
$keys = array_keys($weight);
for ($i = 0; $i < count($keys); $i++) {
    echo "\"" . $keys[$i] . "\"=>\"" . $weight[$keys[$i]] . "\"";
    if ($i < count($keys) - 1) echo ", ";
}
echo ")<br>";

echo "Data kedua: " . $weight[$keys[1]];
?>
