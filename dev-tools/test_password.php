<?php
require_once 'db.php';

// Test with the admin user
$testUsername = 'admin';
$testPassword = 'admin'; // Change this to what you're trying to use

echo "=== Testing Login for: $testUsername ===\n\n";

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$testUsername]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "✗ User '$testUsername' not found in database\n";
        exit;
    }
    
    echo "✓ User found in database\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Role: " . $user['role'] . "\n";
    echo "Password hash: " . substr($user['password'], 0, 20) . "...\n\n";
    
    // Test password verification
    echo "=== Testing Password Verification ===\n";
    echo "Testing password: '$testPassword'\n\n";
    
    if (password_verify($testPassword, $user['password'])) {
        echo "✓ Password verification SUCCESSFUL!\n";
        echo "This password is correct for user: $testUsername\n";
    } else {
        echo "✗ Password verification FAILED!\n";
        echo "The password '$testPassword' does not match the stored hash.\n\n";
        
        // Try to create a new hash to compare
        echo "=== Creating test hash ===\n";
        $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
        echo "New hash for '$testPassword': " . substr($newHash, 0, 30) . "...\n";
        echo "Verification of new hash: " . (password_verify($testPassword, $newHash) ? "✓ Works" : "✗ Failed") . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n\n=== All Users ===\n";
$stmt = $pdo->query("SELECT username FROM users");
while ($row = $stmt->fetch()) {
    echo "- " . $row['username'] . "\n";
}
