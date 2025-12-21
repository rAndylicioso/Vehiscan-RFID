<?php
require_once 'c:/xampp/htdocs/Vehiscan-RFID/db.php';
try {
    $stmt = $pdo->query("DESCRIBE recent_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in recent_logs table:" . PHP_EOL;
    foreach($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")" . PHP_EOL;
    }
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
