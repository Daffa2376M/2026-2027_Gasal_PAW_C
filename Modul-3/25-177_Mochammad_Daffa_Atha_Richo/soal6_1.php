<?php
echo "Array awal: (\"A\", \"B\")<br>";
$arr1 = array("A", "B");
array_push($arr1, "C");
echo "Hasil array_push: ";
foreach ($arr1 as $v) echo $v . " ";
echo "<br><br>";

echo "Array awal: (\"A\", \"B\") digabung dengan (\"C\")<br>";
$arr2 = array("A", "B");
$arr3 = array("C");
$merged = array_merge($arr2, $arr3);
echo "Hasil array_merge: ";
foreach ($merged as $v) echo $v . " ";
echo "<br><br>";

echo "Array awal: (\"x\" => 1, \"y\" => 2)<br>";
$arr4 = array("x" => 1, "y" => 2);
$values = array_values($arr4);
echo "Hasil array_values: ";
foreach ($values as $v) echo $v . " ";
echo "<br><br>";

echo "Mencari \"B\" pada array (\"A\", \"B\", \"C\")<br>";
$arr5 = array("A", "B", "C");
echo "Hasil array_search: " . array_search("B", $arr5);
echo "<br><br>";

echo "Array awal: (0, 1, false, 2, \"\", 3, \"array\")<br>";
$arr6 = array(0, 1, false, 2, "", 3, "array");
$filtered = array_filter($arr6);
echo "Hasil array_filter: ";
foreach ($filtered as $v) echo $v . " ";
echo "<br><br>";

echo "Array awal: (3, 1, 2)<br>";
$arr7 = array(3, 1, 2);
$sorted = $arr7;
sort($sorted);
echo "Hasil sort: ";
foreach ($sorted as $v) echo $v . " ";
echo "<br>";
$rsorted = $arr7;
rsort($rsorted);
echo "Hasil rsort: ";
foreach ($rsorted as $v) echo $v . " ";
echo "<br><br>";

echo "Array awal: (\"Peter\"=>35, \"Ben\"=>37, \"Joe\"=>43)<br>";
$age = array("Peter" => "35", "Ben" => "37", "Joe" => "43");
$asorted = $age;
asort($asorted);
echo "Hasil asort: ";
foreach ($asorted as $k => $v) echo $k . "=> " . $v . ", ";
echo "<br>";
$ksorted = $age;
ksort($ksorted);
echo "Hasil ksort: ";
foreach ($ksorted as $k => $v) echo $k . "=> " . $v . ", ";
echo "<br>";
$arsorted = $age;
arsort($arsorted);
echo "Hasil arsort: ";
foreach ($arsorted as $k => $v) echo $k . "=> " . $v . ", ";
echo "<br>";
$krsorted = $age;
krsort($krsorted);
echo "Hasil krsort: ";
foreach ($krsorted as $k => $v) echo $k . "=> " . $v . ", ";
echo "<br>";
?>
