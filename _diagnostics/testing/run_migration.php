<?php
/**
 * Simple Migration Runner - Add Homeowner Authentication
 * This script adds username and password_hash columns to homeowners table
 */

require_once __DIR__ . '/../db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "===========================================\n";
echo " VehiScan Database Migration\n";
echo " Adding Homeowner Authentication Support\n";
echo "===========================================\n\n";

try {
    // Step 1: Check if already migrated
    echo "[1/6] Checking current state...\n";
    $check = $pdo->query("SHOW COLUMNS FROM homeowners LIKE 'username'");
    if ($check->fetch()) {
        echo "✓ Migration already applied!\n";
        echo "✓ Database is ready.\n\n";
        
        // Show current state
        $count = $pdo->query("SELECT COUNT(*) FROM homeowners")->fetchColumn();
        echo "Total homeowners: $count\n";
        
        if ($count > 0) {
            echo "\nSample homeowners:\n";
            $stmt = $pdo->query("SELECT id, name, username FROM homeowners LIMIT 5");
            while ($row = $stmt->fetch()) {
                echo "  - ID: {$row['id']} | Name: {$row['name']} | Username: {$row['username']}\n";
            }
        }
        exit(0);
    }
    
    echo "✓ Columns not found - migration needed\n\n";
    
    // Step 2: Add username column
    echo "[2/6] Adding username column...\n";
    $pdo->exec("ALTER TABLE homeowners ADD COLUMN username VARCHAR(50) UNIQUE DEFAULT NULL AFTER address");
    echo "✓ Username column added\n\n";
    
    // Step 3: Add password_hash column
    echo "[3/6] Adding password_hash column...\n";
    $pdo->exec("ALTER TABLE homeowners ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL AFTER username");
    echo "✓ Password_hash column added\n\n";
    
    // Step 4: Create index
    echo "[4/6] Creating index for username lookups...\n";
    $pdo->exec("CREATE INDEX idx_homeowners_username ON homeowners (username)");
    echo "✓ Index created\n\n";
    
    // Step 5: Update existing records
    echo "[5/6] Checking for existing homeowners...\n";
    $count = $pdo->query("SELECT COUNT(*) FROM homeowners")->fetchColumn();
    
    if ($count > 0) {
        echo "Found $count existing homeowner(s)\n";
        echo "Assigning temporary credentials...\n";
        $pdo->exec("
            UPDATE homeowners 
            SET username = CONCAT('homeowner_', id),
                password_hash = '$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
            WHERE username IS NULL
        ");
        echo "✓ Temporary credentials assigned\n";
        echo "  Default username format: homeowner_[ID]\n";
        echo "  Default password: password\n\n";
    } else {
        echo "No existing homeowners - skipping\n\n";
    }
    
    // Step 6: Make columns required
    echo "[6/6] Setting columns as required...\n";
    $pdo->exec("ALTER TABLE homeowners MODIFY COLUMN username VARCHAR(50) NOT NULL");
    $pdo->exec("ALTER TABLE homeowners MODIFY COLUMN password_hash VARCHAR(255) NOT NULL");
    echo "✓ Columns configured as NOT NULL\n\n";
    
    // Success summary
    echo "===========================================\n";
    echo " ✓ MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "===========================================\n\n";
    
    echo "What's next:\n";
    echo "1. Test registration: /homeowners/homeowner_registration.php\n";
    echo "2. Check database status: /_testing/check_homeowner_columns.php\n";
    
    if ($count > 0) {
        echo "\nExisting homeowners can now login with:\n";
        $stmt = $pdo->query("SELECT id, name, username FROM homeowners");
        while ($row = $stmt->fetch()) {
            echo "  - Username: {$row['username']} (Password: password)\n";
        }
    }
    
    echo "\n";
    
} catch (PDOException $e) {
    echo "\n";
    echo "===========================================\n";
    echo " ✗ MIGRATION FAILED\n";
    echo "===========================================\n\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Possible solutions:\n";
    echo "1. Check database connection in db.php\n";
    echo "2. Verify MySQL user has ALTER privileges\n";
    echo "3. Try running the SQL manually in phpMyAdmin\n\n";
    echo "Manual SQL:\n";
    echo "ALTER TABLE homeowners ADD COLUMN username VARCHAR(50) UNIQUE DEFAULT NULL;\n";
    echo "ALTER TABLE homeowners ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL;\n";
    echo "CREATE INDEX idx_homeowners_username ON homeowners (username);\n";
    exit(1);
}
?>
