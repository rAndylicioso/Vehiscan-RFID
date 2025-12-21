<?php
$pdo = new PDO('mysql:host=localhost;dbname=vehiscan_vdp;charset=utf8mb4', 'root', '');
$columns = $pdo->query("DESCRIBE homeowners")->fetchAll();
echo "<h3>Homeowners Table Columns:</h3><ul>";
foreach ($columns as $col) {
    echo "<li><strong>{$col['Field']}</strong> - {$col['Type']}</li>";
}
echo "</ul>";
?>
