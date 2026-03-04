<?php
error_reporting(0);
require_once __DIR__ . '/../db.php';

$tables = ['homeowners', 'homeowner_vehicles', 'vehicles'];
foreach ($tables as $table) {
    echo "\n=== $table ===\n";
    try {
        $s = $pdo->query("DESCRIBE $table");
        $rows = $s->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            echo "  " . $r['Field'] . " - " . $r['Type'] . ($r['Key'] === 'PRI' ? ' [PK]' : '') . "\n";
        }
    } catch (Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\n=== rfid_binding_sessions row count ===\n";
$s = $pdo->query("SELECT COUNT(*) as cnt FROM rfid_binding_sessions");
echo "  " . $s->fetch()['cnt'] . " rows\n";

echo "\n=== rfid_scan_log row count ===\n";
$s = $pdo->query("SELECT COUNT(*) as cnt FROM rfid_scan_log");
echo "  " . $s->fetch()['cnt'] . " rows\n";

echo "\n=== rfid_api_keys row count ===\n";
$s = $pdo->query("SELECT COUNT(*) as cnt FROM rfid_api_keys");
echo "  " . $s->fetch()['cnt'] . " rows\n";
