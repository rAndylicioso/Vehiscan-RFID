<?php
error_reporting(0);
require_once __DIR__ . '/../db.php';

$tables = ['rfid_binding_sessions', 'rfid_scan_log', 'rfid_api_keys', 'rfid_simulator'];
foreach ($tables as $table) {
    echo "\n=== $table ===\n";
    try {
        $s = $pdo->query("DESCRIBE $table");
        $rows = $s->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $extra = '';
            if ($r['Key'] === 'PRI') $extra .= ' [PK]';
            if ($r['Null'] === 'NO') $extra .= ' NOT NULL';
            if ($r['Default'] !== null) $extra .= ' DEFAULT ' . $r['Default'];
            if ($r['Extra']) $extra .= ' ' . $r['Extra'];
            echo "  " . $r['Field'] . " - " . $r['Type'] . $extra . "\n";
        }
    } catch (Exception $e) {
        echo "  TABLE NOT FOUND: " . $e->getMessage() . "\n";
    }
}

echo "\n=== vehicles (rfid columns) ===\n";
try {
    $s = $pdo->query("DESCRIBE vehicles");
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        if (strpos($r['Field'], 'rfid') !== false || $r['Field'] === 'id' || $r['Field'] === 'plate_number') {
            echo "  " . $r['Field'] . " - " . $r['Type'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== SHOW TABLES LIKE 'rfid%' ===\n";
$s = $pdo->query("SHOW TABLES LIKE 'rfid%'");
foreach ($s->fetchAll(PDO::FETCH_COLUMN) as $t) {
    echo "  $t\n";
}
