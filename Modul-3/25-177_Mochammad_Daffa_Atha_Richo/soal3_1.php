<?php
$height = array("Andy" => "176", "Barry" => "165", "Charlie" => "170");

$height["David"] = "180";
$height["Ethan"] = "172";
$height["Frank"] = "168";
$height["George"] = "175";
$height["Harry"] = "182";

echo "height = (";
$keys = array_keys($height);
for ($i = 0; $i < count($keys); $i++) {
    echo "\"" . $keys[$i] . "\"=>\"" . $height[$keys[$i]] . "\"";
    if ($i < count($keys) - 1) echo ", ";
}
echo ")<br>";

$lastKey = end($keys);
echo "Nilai dengan indeks terakhir: " . $height[$lastKey] . "<br>";

unset($height["Ethan"]);

echo "height = (";
$keys = array_keys($height);
for ($i = 0; $i < count($keys); $i++) {
    echo "\"" . $keys[$i] . "\"=>\"" . $height[$keys[$i]] . "\"";
    if ($i < count($keys) - 1) echo ", ";
}
echo ")<br>";

$lastKey = end($keys);
echo "Nilai dengan indeks terakhir setelah dihapus: " . $height[$lastKey];
?>
