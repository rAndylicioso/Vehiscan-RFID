<?php
require_once 'db.php';

echo "Homeowners Table Columns:\n";
echo str_repeat('-', 40) . "\n";
$stmt = $pdo->query('SHOW COLUMNS FROM homeowners');
while($col = $stmt->fetch()) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}
