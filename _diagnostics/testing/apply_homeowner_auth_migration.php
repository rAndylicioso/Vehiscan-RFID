<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - Homeowner Authentication</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 30px; }
        .output {
            background: #1e293b;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .info { color: #3b82f6; }
        .step { color: #06b6d4; margin-top: 15px; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .btn:hover { background: #5568d3; }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .table th {
            background: #f1f5f9;
            font-weight: 600;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Homeowner Authentication Migration</h1>
            <p>Adding username and password support to the homeowners table</p>
        </div>
        <div class="content">
            <div class="output"><?php
require_once __DIR__ . '/../db.php';

try {
    echo "<span class='info'>🔍 Checking current database structure...</span>\n\n";
    
    // Check if columns already exist
    $checkStmt = $pdo->query("SHOW COLUMNS FROM homeowners LIKE 'username'");
    if ($checkStmt->fetch()) {
        echo "<span class='success'>✓ Migration already applied!</span>\n";
        echo "<span class='success'>✓ Username and password_hash columns exist.</span>\n\n";
        
        // Show current homeowners
        $homeowners = $pdo->query("SELECT id, name, plate_number, username FROM homeowners ORDER BY id")->fetchAll();
        if ($homeowners) {
            echo "<span class='info'>📋 Current homeowners ({count: " . count($homeowners) . "}):</span>\n";
            echo str_repeat("-", 80) . "\n";
            foreach ($homeowners as $h) {
                echo sprintf("ID: %-3s | %-20s | Plate: %-10s | Username: %s\n", 
                    $h['id'], 
                    substr($h['name'], 0, 20), 
                    $h['plate_number'], 
                    $h['username']
                );
            }
        }
        
        echo "\n<span class='success'>✓ Your database is ready!</span>\n";
        echo "<span class='info'>You can now create homeowner accounts with username/password.</span>\n";
        goto end;
    }
    
    echo "<span class='warning'>⚠ Migration needed. Starting...</span>\n\n";
    
    echo "<span class='step'>STEP 1: Adding username column...</span>\n";
    $pdo->exec("ALTER TABLE `homeowners` ADD COLUMN `username` VARCHAR(50) UNIQUE DEFAULT NULL AFTER `address`");
    echo "<span class='success'>✓ Username column added</span>\n\n";
    
    echo "<span class='step'>STEP 2: Adding password_hash column...</span>\n";
    $pdo->exec("ALTER TABLE `homeowners` ADD COLUMN `password_hash` VARCHAR(255) DEFAULT NULL AFTER `username`");
    echo "<span class='success'>✓ Password_hash column added</span>\n\n";
    
    echo "<span class='step'>STEP 3: Creating index for fast lookups...</span>\n";
    $pdo->exec("CREATE INDEX `idx_homeowners_username` ON `homeowners` (`username`)");
    echo "<span class='success'>✓ Index created</span>\n\n";
    
    echo "<span class='step'>STEP 4: Updating existing records...</span>\n";
    $countStmt = $pdo->query("SELECT COUNT(*) FROM homeowners");
    $existingCount = $countStmt->fetchColumn();
    
    if ($existingCount > 0) {
        $pdo->exec("
            UPDATE `homeowners` 
            SET `username` = CONCAT('homeowner_', `id`),
                `password_hash` = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
            WHERE `username` IS NULL
        ");
        echo "<span class='success'>✓ Updated $existingCount existing homeowner(s)</span>\n";
        echo "<span class='info'>  Default credentials assigned:</span>\n";
        echo "<span class='info'>  - Username: homeowner_[id]</span>\n";
        echo "<span class='info'>  - Password: password (they should change this)</span>\n\n";
    } else {
        echo "<span class='info'>  No existing homeowners found</span>\n\n";
    }
    
    echo "<span class='step'>STEP 5: Setting columns as required...</span>\n";
    $pdo->exec("ALTER TABLE `homeowners` MODIFY COLUMN `username` VARCHAR(50) NOT NULL");
    $pdo->exec("ALTER TABLE `homeowners` MODIFY COLUMN `password_hash` VARCHAR(255) NOT NULL");
    echo "<span class='success'>✓ Columns configured as NOT NULL</span>\n\n";
    
    echo str_repeat("=", 80) . "\n";
    echo "<span class='success'>✅ MIGRATION COMPLETED SUCCESSFULLY!</span>\n";
    echo str_repeat("=", 80) . "\n\n";
    
    echo "<span class='info'>📝 What's next:</span>\n";
    echo "  1. New homeowners can now register with username/password\n";
    echo "  2. Existing homeowners can login with their temp credentials\n";
    echo "  3. Registration page: /homeowners/homeowner_registration.php\n";
    echo "  4. Login page: /auth/login.php (select 'Homeowner' role)\n\n";
    
    // Show migrated homeowners
    if ($existingCount > 0) {
        $homeowners = $pdo->query("SELECT id, name, plate_number, username FROM homeowners ORDER BY id")->fetchAll();
        echo "<span class='info'>📋 Migrated homeowners:</span>\n";
        echo str_repeat("-", 80) . "\n";
        foreach ($homeowners as $h) {
            echo sprintf("ID: %-3s | %-20s | Plate: %-10s | Username: %s\n", 
                $h['id'], 
                substr($h['name'], 0, 20), 
                $h['plate_number'], 
                $h['username']
            );
        }
    }
    
} catch (PDOException $e) {
    echo "<span class='error'>✗ MIGRATION FAILED</span>\n\n";
    echo "<span class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</span>\n\n";
    echo "<span class='warning'>Possible causes:</span>\n";
    echo "  1. Database connection issue\n";
    echo "  2. Insufficient privileges (need ALTER, CREATE INDEX permissions)\n";
    echo "  3. Partial migration (try running again)\n\n";
    echo "<span class='info'>💡 Manual SQL alternative:</span>\n";
    echo "  Run this in phpMyAdmin:\n\n";
    echo "  ALTER TABLE `homeowners` \n";
    echo "  ADD COLUMN `username` VARCHAR(50) UNIQUE DEFAULT NULL,\n";
    echo "  ADD COLUMN `password_hash` VARCHAR(255) DEFAULT NULL;\n\n";
}

end:
?></div>
            
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <a href="../homeowners/homeowner_registration.php" class="btn">📝 Go to Registration</a>
                <a href="check_homeowner_columns.php" class="btn" style="background: #10b981;">🔍 Check Database Status</a>
            </div>
        </div>
    </div>
</body>
</html>
