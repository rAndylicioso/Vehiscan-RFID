<?php
require_once __DIR__ . '/../db.php';

echo "Recent Logs Table Columns:\n";
$stmt = $pdo->query("SHOW COLUMNS FROM recent_logs");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

echo "\nSample Log Data:\n";
$sample = $pdo->query("SELECT * FROM recent_logs LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($sample) {
    foreach ($sample as $key => $value) {
        echo "- $key: " . (is_null($value) ? 'NULL' : $value) . "\n";
    }
} else {
    echo "No logs found\n";
}

echo "\nTotal Logs: " . $pdo->query("SELECT COUNT(*) FROM recent_logs")->fetchColumn() . "\n";
echo "Logs Today: " . $pdo->query("SELECT COUNT(*) FROM recent_logs WHERE DATE(log_time) = CURDATE()")->fetchColumn() . "\n";
