<?php
require_once __DIR__ . '/../db.php';

echo "=== SUPER ADMIN CHECK ===\n\n";

try {
    // Check if super_admin table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'super_admin'");
    if ($stmt->rowCount() === 0) {
        echo "❌ super_admin table does NOT exist!\n";
        echo "This is why you can't login as super admin.\n\n";
        echo "Creating super_admin table...\n";
        
        // Create super_admin table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS super_admin (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL,
                last_login_ip VARCHAR(45) NULL,
                require_password_change TINYINT(1) DEFAULT 0
            )
        ");
        echo "✓ Table created!\n\n";
        
        // Create default super admin
        $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->exec("
            INSERT INTO super_admin (username, email, password_hash) 
            VALUES ('Administrator', 'admin@vehiscan.local', '$defaultPassword')
        ");
        echo "✓ Default super admin created:\n";
        echo "   Username: Administrator\n";
        echo "   Email: admin@vehiscan.local\n";
        echo "   Password: admin123\n";
        echo "   (Please change this password after first login)\n\n";
    } else {
        echo "✓ super_admin table exists\n\n";
        
        // Check super admin accounts
        $stmt = $pdo->query("SELECT id, username, email, created_at, last_login FROM super_admin");
        $admins = $stmt->fetchAll();
        
        if (count($admins) === 0) {
            echo "⚠️  No super admin accounts found!\n";
            echo "Creating default super admin...\n\n";
            
            $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT);
            $pdo->exec("
                INSERT INTO super_admin (username, email, password_hash) 
                VALUES ('Administrator', 'admin@vehiscan.local', '$defaultPassword')
            ");
            echo "✓ Default super admin created:\n";
            echo "   Username: Administrator\n";
            echo "   Email: admin@vehiscan.local\n";
            echo "   Password: admin123\n\n";
        } else {
            echo "Found " . count($admins) . " super admin account(s):\n\n";
            foreach ($admins as $admin) {
                echo "ID: " . $admin['id'] . "\n";
                echo "Username: " . $admin['username'] . "\n";
                echo "Email: " . $admin['email'] . "\n";
                echo "Created: " . $admin['created_at'] . "\n";
                echo "Last Login: " . ($admin['last_login'] ?? 'Never') . "\n";
                echo "---\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== ALL USER TYPES ===\n\n";

// Check all user types
try {
    echo "SUPER ADMINS:\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM super_admin");
    echo "  Count: " . $stmt->fetchColumn() . "\n\n";
} catch (Exception $e) {
    echo "  Table doesn't exist\n\n";
}

echo "REGULAR ADMINS:\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
echo "  Count: " . $stmt->fetchColumn() . "\n\n";

echo "GUARDS:\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guard'");
echo "  Count: " . $stmt->fetchColumn() . "\n\n";

echo "HOMEOWNERS:\n";
$stmt = $pdo->query("SELECT COUNT(*) FROM homeowners");
echo "  Count: " . $stmt->fetchColumn() . "\n";
