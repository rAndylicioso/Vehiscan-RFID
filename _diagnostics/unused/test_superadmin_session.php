<?php
// Test Super Admin Login
require_once __DIR__ . '/db.php';

echo "<h2>Super Admin Login Test</h2>";

// Simulate login process
$username = 'Administrator'; // Your super admin username
$testPassword = 'your_password_here'; // Replace with actual password for testing

echo "<h3>Step 1: Check super_admin table</h3>";
$stmt = $pdo->prepare("SELECT * FROM super_admin WHERE username = ?");
$stmt->execute([$username]);
$superAdmin = $stmt->fetch();

if ($superAdmin) {
    echo "✅ Super admin found<br>";
    echo "Username: {$superAdmin['username']}<br>";
    echo "Email: {$superAdmin['email']}<br>";
    echo "Password hash exists: " . (!empty($superAdmin['password_hash']) ? "Yes" : "No") . "<br>";
    
    // Test password (REMOVE THIS AFTER TESTING)
    // Uncomment to test password:
    // if (password_verify($testPassword, $superAdmin['password_hash'])) {
    //     echo "✅ Password verification: SUCCESS<br>";
    // } else {
    //     echo "❌ Password verification: FAILED<br>";
    // }
} else {
    echo "❌ Super admin NOT found<br>";
    exit;
}

echo "<h3>Step 2: Test Session Creation</h3>";
session_name('vehiscan_superadmin');
session_start();

$_SESSION['username'] = $superAdmin['username'];
$_SESSION['role'] = 'super_admin';
$_SESSION['user_id'] = $superAdmin['id'];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

echo "Session name: " . session_name() . "<br>";
echo "Session ID: " . session_id() . "<br>";
echo "Session role: " . ($_SESSION['role'] ?? 'NOT SET') . "<br>";
echo "Session username: " . ($_SESSION['username'] ?? 'NOT SET') . "<br>";

echo "<h3>Step 3: Test Redirect</h3>";
echo "✅ If this were the actual login, you would be redirected to:<br>";
echo "<strong>../admin/admin_panel.php</strong><br>";

echo "<hr>";
echo "<a href='admin/admin_panel.php'>Click here to test admin panel access</a><br>";
echo "<p><em>Note: Session is now set. Try accessing the admin panel.</em></p>";
