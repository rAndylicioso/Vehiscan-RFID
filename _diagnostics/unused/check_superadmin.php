<?php
require_once __DIR__ . '/db.php';

echo "=== CHECKING SUPER ADMIN CONFIGURATION ===\n\n";

// Check super_admin table
echo "1. Checking super_admin table:\n";
try {
    $stmt = $pdo->query("SELECT id, username, email FROM super_admin LIMIT 5");
    $superAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($superAdmins) {
        echo "   ✅ super_admin table exists\n";
        foreach ($superAdmins as $sa) {
            echo "   - ID: {$sa['id']}, Username: {$sa['username']}, Email: {$sa['email']}\n";
        }
    } else {
        echo "   ⚠️  super_admin table exists but is EMPTY\n";
    }
} catch (PDOException $e) {
    echo "   ❌ super_admin table does NOT exist: " . $e->getMessage() . "\n";
}

echo "\n2. Checking users table for super_admin role:\n";
try {
    $stmt = $pdo->query("SELECT id, username, role FROM users WHERE role = 'super_admin' LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($users) {
        echo "   ✅ Found super_admin in users table\n";
        foreach ($users as $u) {
            echo "   - ID: {$u['id']}, Username: {$u['username']}, Role: {$u['role']}\n";
        }
    } else {
        echo "   ⚠️  No super_admin role found in users table\n";
    }
} catch (PDOException $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n3. Testing session name setup:\n";
session_name('vehiscan_superadmin');
session_start();
echo "   Session name: " . session_name() . "\n";
echo "   Session ID: " . session_id() . "\n";
echo "   Session data: " . json_encode($_SESSION) . "\n";
session_destroy();

echo "\n=== END CHECK ===\n";
