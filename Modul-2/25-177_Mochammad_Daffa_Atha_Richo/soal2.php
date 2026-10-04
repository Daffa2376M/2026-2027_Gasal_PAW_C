<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Soal2</title>
</head>
<body>
	<?php
	$matkul = array("PTI", "ALPRO", "DPW", "STRUKDAT", "JARKOM", "PAW", "PSBF", "RPL");

	function cekMatkul($m)
	{
		switch ($m) {
			case "PTI":
				echo "Saya suka PTI <br>";
				break;
			case "ALPRO":
				echo "Saya suka ALPRO <br>";
				break;
			case "DPW":
				echo "Saya suka DPW <br>";
				break;
			case "STRUKDAT":
				echo "Saya suka STRUKDAT <br>";
				break;
			case "JARKOM":
				echo "Saya suka JARKOM <br>";
				break;
			case "PAW":
				echo "Saya suka PAW <br>";
				break;
			default:
				echo "Saya tidak mengambil matkul $m <br>";
				break;
		}
	}

	foreach ($matkul as $m) {
		cekMatkul($m);
	}
	?>
</body>
</html>
