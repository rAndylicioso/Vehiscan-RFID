<?php
// Check homeowners table structure for registration compatibility
require_once __DIR__ . '/db.php';
header('Content-Type: text/plain');

try {
    echo "=== HOMEOWNERS TABLE STRUCTURE ===\n\n";
    
    $columns = $pdo->query("SHOW COLUMNS FROM homeowners")->fetchAll(PDO::FETCH_ASSOC);
    
    $required = ['id', 'name', 'first_name', 'middle_name', 'last_name', 'suffix', 'email', 'contact_number', 'address', 'vehicle_type', 'color', 'plate_number', 'owner_img', 'car_img', 'account_status', 'created_at'];
    
    echo "Columns found:\n";
    foreach ($columns as $col) {
        echo "  ✓ " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n=== CHECKING REQUIRED COLUMNS ===\n\n";
    $missing = [];
    foreach ($required as $req) {
        $found = false;
        foreach ($columns as $col) {
            if ($col['Field'] === $req) {
                echo "  ✓ $req exists\n";
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "  ✗ $req MISSING!\n";
            $missing[] = $req;
        }
    }
    
    echo "\n=== CHECKING homeowner_auth TABLE ===\n\n";
    
    $tables = $pdo->query("SHOW TABLES LIKE 'homeowner_auth'")->fetchAll();
    if (empty($tables)) {
        echo "  ✗ homeowner_auth table does NOT exist!\n";
        echo "\n=== SOLUTION ===\n";
        echo "Run: http://localhost/Vehiscan-RFID/run_migrations.php\n";
    } else {
        echo "  ✓ homeowner_auth table exists\n";
        $authCols = $pdo->query("SHOW COLUMNS FROM homeowner_auth")->fetchAll(PDO::FETCH_ASSOC);
        echo "\n  Columns:\n";
        foreach ($authCols as $col) {
            echo "    • " . $col['Field'] . "\n";
        }
    }
    
    if (!empty($missing)) {
        echo "\n=== MISSING COLUMNS DETECTED ===\n";
        echo "The following columns need to be added:\n";
        foreach ($missing as $m) {
            echo "  - $m\n";
        }
    } else {
        echo "\n✅ All required columns exist!\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
