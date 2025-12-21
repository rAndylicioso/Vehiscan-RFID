<?php
require_once 'db.php';

echo "=== Database Connection Test ===\n\n";

try {
    // Test connection
    echo "✓ Database connected successfully\n\n";
    
    // Check users table
    echo "=== Users in database ===\n";
    $stmt = $pdo->query("SELECT username, role FROM users");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "⚠ No users found in database!\n";
    } else {
        foreach ($users as $user) {
            echo "Username: " . $user['username'] . " | Role: " . $user['role'] . "\n";
        }
    }
    
    echo "\n=== Password Hash Check ===\n";
    $stmt = $pdo->query("SELECT username, password FROM users LIMIT 1");
    $testUser = $stmt->fetch();
    
    if ($testUser) {
        echo "Sample user: " . $testUser['username'] . "\n";
        echo "Password hash format: " . substr($testUser['password'], 0, 10) . "...\n";
        echo "Hash length: " . strlen($testUser['password']) . " characters\n";
        
        // Check if it's a proper bcrypt hash
        if (substr($testUser['password'], 0, 4) === '$2y$') {
            echo "✓ Password is properly hashed (bcrypt)\n";
        } else {
            echo "⚠ Password might not be properly hashed!\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
