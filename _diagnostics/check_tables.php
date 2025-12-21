<?php
require_once __DIR__ . '/../db.php';

$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Database tables:\n";
foreach ($tables as $table) {
    echo "- $table\n";
}
