<?php
$fruits = array("Avocado", "Blueberry", "Cherry");

$fruits[] = "Durian";
$fruits[] = "Elderberry";
$fruits[] = "Fig";
$fruits[] = "Grape";
$fruits[] = "Honeydew";

echo "fruits = (";
for ($i = 0; $i < count($fruits); $i++) {
    echo "\"" . $fruits[$i] . "\"";
    if ($i < count($fruits) - 1) echo ", ";
}
echo ")<br>";

$last = count($fruits) - 1;
echo "Nilai dengan indeks tertinggi: " . $fruits[$last];
?>
