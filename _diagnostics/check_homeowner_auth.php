<?php
require_once __DIR__ . '/../db.php';

echo "=== HOMEOWNER_AUTH TABLE STRUCTURE ===\n\n";
$stmt = $pdo->query('DESCRIBE homeowner_auth');
while ($row = $stmt->fetch()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
}

echo "\n=== SAMPLE DATA ===\n\n";
$stmt = $pdo->query('SELECT * FROM homeowner_auth LIMIT 3');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    print_r($row);
    echo "\n";
}
