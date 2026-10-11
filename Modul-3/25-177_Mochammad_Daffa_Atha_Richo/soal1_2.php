<?php
$fruits = array("Avocado", "Blueberry", "Cherry", "Durian", "Elderberry", "Fig", "Grape", "Honeydew");

unset($fruits[1]);

echo "Data Blueberry dihapus.<br>";

echo "fruits = (";
$newIndex = 0;
$total = count($fruits);
foreach ($fruits as $fruit) {
    echo "\"" . $fruit . "\"";
    if ($newIndex < $total - 1) echo ", ";
    $newIndex++;
}
echo ")<br>";

$last = max(array_keys($fruits));
echo "Nilai dengan indeks tertinggi: " . $fruits[$last];
?>
