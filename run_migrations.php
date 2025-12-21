<?php
/**
 * Quick Database Setup Script
 * Run this ONCE to create all required tables and columns
 */

require_once __DIR__ . '/db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Setup</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e293b; color: #e2e8f0; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .info { color: #3b82f6; }
        .warning { color: #f59e0b; }
        pre { background: #0f172a; padding: 15px; border-radius: 8px; margin: 10px 0; }
    </style>
</head>
<body>
<h1>🔧 VehiScan Database Setup</h1>
<pre><?php

try {
    echo "<span class='info'>Starting database setup...</span>\n\n";
    
    // 1. Create homeowner_auth table if not exists
    echo "<span class='info'>[1/5] Creating homeowner_auth table...</span>\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS homeowner_auth (
            id INT AUTO_INCREMENT PRIMARY KEY,
            homeowner_id INT NOT NULL,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            is_active TINYINT(1) DEFAULT 0,
            account_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            last_login DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (homeowner_id) REFERENCES homeowners(id) ON DELETE CASCADE,
            INDEX idx_username (username),
            INDEX idx_email (email),
            INDEX idx_status (account_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<span class='success'>✓ homeowner_auth table ready</span>\n\n";
    
    // Helper function to safely check column existence
    function columnExists($pdo, $table, $column) {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return $stmt->fetch() !== false;
    }

    // 2. Add account_status to homeowners if not exists
    echo "<span class='info'>[2/5] Checking homeowners table...</span>\n";
    if (!columnExists($pdo, 'homeowners', 'account_status')) {
        $pdo->exec("ALTER TABLE homeowners ADD COLUMN account_status ENUM('pending','approved','rejected') DEFAULT 'approved' AFTER email");
        echo "<span class='success'>✓ Added account_status column to homeowners</span>\n\n";
    } else {
        echo "<span class='success'>✓ account_status column already exists</span>\n\n";
    }
    
    // 3. Add structured name fields if not exist
    echo "<span class='info'>[3/5] Checking name fields...</span>\n";
    $nameFields = ['first_name', 'middle_name', 'last_name', 'suffix'];
    foreach ($nameFields as $field) {
        if (!columnExists($pdo, 'homeowners', $field)) {
            // Safe to interpolate specific whitelist values
            $size = $field === 'suffix' ? '10' : '50';
            $pdo->exec("ALTER TABLE homeowners ADD COLUMN `$field` VARCHAR($size) AFTER name");
            echo "<span class='success'>✓ Added $field column</span>\n";
        }
    }
    echo "<span class='success'>✓ Name fields ready</span>\n\n";
    
    // 4. Add email to homeowners if not exists
    echo "<span class='info'>[4/5] Checking email field...</span>\n";
    if (!columnExists($pdo, 'homeowners', 'email')) {
        $pdo->exec("ALTER TABLE homeowners ADD COLUMN email VARCHAR(100) UNIQUE AFTER contact");
        echo "<span class='success'>✓ Added email column</span>\n\n";
    } else {
        echo "<span class='success'>✓ Email column already exists</span>\n\n";
    }
    
    // 5. Check visitor_passes table for subdivision logo support
    echo "<span class='info'>[5/5] Checking visitor_passes table...</span>\n";
    
    // Check if visitor_passes table exists first
    $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
    $stmt->execute(['visitor_passes']);
    if ($stmt->fetch()) {
        if (!columnExists($pdo, 'visitor_passes', 'subdivision_logo')) {
            // Try to add after qr_code column if it exists
            if (columnExists($pdo, 'visitor_passes', 'qr_code')) {
                $pdo->exec("ALTER TABLE visitor_passes ADD COLUMN subdivision_logo VARCHAR(255) AFTER qr_code");
                echo "<span class='success'>✓ Added subdivision_logo column</span>\n\n";
            } else {
                // Just add it at the end
                $pdo->exec("ALTER TABLE visitor_passes ADD COLUMN subdivision_logo VARCHAR(255)");
                echo "<span class='success'>✓ Added subdivision_logo column</span>\n\n";
            }
        } else {
            echo "<span class='success'>✓ Subdivision logo support already exists</span>\n\n";
        }
    } else {
        echo "<span class='warning'>⚠ visitor_passes table not found (will be created when first pass is generated)</span>\n\n";
    }
    
    echo "\n<span class='info'>[6/5] Checking users table...</span>\n";
    if (columnExists($pdo, 'users', 'username')) {
        // Check for email
        if (!columnExists($pdo, 'users', 'email')) {
            $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE AFTER username");
            echo "<span class='success'>✓ Added email column to users</span>\n";
        } else {
            echo "<span class='success'>✓ Email column already exists in users</span>\n";
        }

        // Check for account_status
        if (!columnExists($pdo, 'users', 'account_status')) {
            $pdo->exec("ALTER TABLE users ADD COLUMN account_status ENUM('active','pending','suspended') DEFAULT 'active' AFTER role");
            echo "<span class='success'>✓ Added account_status column to users</span>\n";
        } else {
            echo "<span class='success'>✓ account_status column already exists in users</span>\n";
        }
    } else {
        echo "<span class='warning'>⚠ users table not found!</span>\n";
    }
    
    echo "\n<span class='success'>========================================</span>\n";
    echo "<span class='success'>✓ DATABASE SETUP COMPLETE!</span>\n";
    echo "<span class='success'>========================================</span>\n\n";
    
    echo "<span class='info'>📊 Database Summary:</span>\n";
    
    // Count homeowners
    $homeownerCount = $pdo->query("SELECT COUNT(*) FROM homeowners")->fetchColumn();
    echo "   • Homeowners: $homeownerCount\n";
    
    // Count auth records
    $authCount = $pdo->query("SELECT COUNT(*) FROM homeowner_auth")->fetchColumn();
    echo "   • Auth Records: $authCount\n";
    
    // Count pending approvals
    $pendingCount = $pdo->query("SELECT COUNT(*) FROM homeowners WHERE account_status = 'pending'")->fetchColumn();
    echo "   • Pending Approvals: $pendingCount\n";
    
    echo "\n<span class='info'>Next Steps:</span>\n";
    echo "1. Delete this file (run_migrations.php) for security\n";
    echo "2. Test registration at: homeowners/homeowner_registration.php\n";
    echo "3. Check pending accounts at: Admin Panel → Account Approvals\n\n";
    
} catch (PDOException $e) {
    echo "<span class='error'>✗ ERROR: " . $e->getMessage() . "</span>\n";
    echo "<span class='error'>SQL State: " . $e->getCode() . "</span>\n";
}

?></pre>

<p><a href="homeowners/homeowner_registration.php" style="color: #10b981;">→ Go to Registration</a> | 
   <a href="admin/admin_panel.php" style="color: #3b82f6;">→ Go to Admin Panel</a></p>
</body>
</html>
