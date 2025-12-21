<?php
require_once __DIR__ . '/../db.php';

echo "Homeowners Table Columns:\n";
$stmt = $pdo->query("SHOW COLUMNS FROM homeowners");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\nSample Homeowner Data:\n";
$sample = $pdo->query("SELECT * FROM homeowners LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($sample) {
    foreach ($sample as $key => $value) {
        echo "- $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
    }
} else {
    echo "No homeowners found in database\n";
}

echo "\nTotal Homeowners: " . $pdo->query("SELECT COUNT(*) FROM homeowners")->fetchColumn() . "\n";
