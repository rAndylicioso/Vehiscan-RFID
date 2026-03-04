<?php
require_once __DIR__ . '/../db.php';

echo "=== Checking visitor_passes table ===\n";

// Check if table exists
$stmt = $pdo->query("SHOW TABLES LIKE 'visitor_passes'");
$exists = $stmt->fetch();
echo "Table exists: " . ($exists ? "YES" : "NO") . "\n";

if ($exists) {
    // Show columns
    echo "\n=== Columns ===\n";
    $cols = $pdo->query("DESCRIBE visitor_passes")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  {$col['Field']} ({$col['Type']}) " . ($col['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
    
    // Try the exact query from fetch_visitors.php
    echo "\n=== Testing homeowners query ===\n";
    try {
        $homeowners = $pdo->query("SELECT id, name FROM homeowners ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        echo "Homeowners count: " . count($homeowners) . "\n";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== Testing pending passes query ===\n";
    try {
        $pending = $pdo->query("
            SELECT vp.*, h.name as homeowner_name
            FROM visitor_passes vp
            JOIN homeowners h ON vp.homeowner_id = h.id
            WHERE vp.status = 'pending'
            ORDER BY vp.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        echo "Pending passes: " . count($pending) . "\n";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== Testing all passes query ===\n";
    try {
        $all = $pdo->query("
            SELECT vp.*, h.name as homeowner_name
            FROM visitor_passes vp
            JOIN homeowners h ON vp.homeowner_id = h.id
            ORDER BY vp.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        echo "All passes: " . count($all) . "\n";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "\n=== Checking homeowners table ===\n";
    $stmt2 = $pdo->query("SHOW TABLES LIKE 'homeowners'");
    echo "homeowners table exists: " . ($stmt2->fetch() ? "YES" : "NO") . "\n";
    
    echo "\nvisitor_passes table does NOT exist. Need to create it.\n";
    echo "\n=== All tables in database ===\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_NUM);
    foreach ($tables as $t) {
        echo "  - " . $t[0] . "\n";
    }
}
