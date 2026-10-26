<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Soal 2 - Hasil Pemrosesan Form</title>
</head>
<body>
    <h2>Hasil Pemrosesan Form (Soal 2 - POST)</h2>
    <table border="1" cellpadding="6" cellspacing="0">
        <tr bgcolor="#f2f2f2">
            <th>Field</th>
            <th>Value</th>
        </tr>
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
            foreach ($_POST as $key => $value) {
                $display = ($key === 'password') ? str_repeat('*', strlen($value)) : $value;
                echo "<tr>";
                echo "<td>" . htmlspecialchars($key) . "</td>";
                echo "<td>" . nl2br(htmlspecialchars($display)) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='2'>Tidak ada data POST yang diterima.</td></tr>";
        }
        ?>
    </table>
</body>
</html>
