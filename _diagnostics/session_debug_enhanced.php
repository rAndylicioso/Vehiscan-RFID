<?php
// Session Debug Tool
echo "<!DOCTYPE html>";
echo "<html><head><title>Session Debug</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;} .box{background:white;padding:15px;margin:10px 0;border-radius:5px;box-shadow:0 2px 4px rgba(0,0,0,0.1);} .success{background:#d4edda;color:#155724;} .error{background:#f8d7da;color:#721c24;} .info{background:#d1ecf1;color:#0c5460;} pre{background:#272822;color:#f8f8f2;padding:10px;border-radius:3px;overflow-x:auto;}</style>";
echo "</head><body>";

echo "<h1>Session Debug Information</h1>";
echo "<p><strong>Current Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Test 1: Super Admin Session
echo "<div class='box'><h2>Test 1: Super Admin Session</h2>";
session_name('vehiscan_superadmin');
@session_start();
echo "Session Name: <strong>" . session_name() . "</strong><br>";
echo "Session ID: <strong>" . session_id() . "</strong><br>";
echo "Session Data:<br><pre>";
print_r($_SESSION);
echo "</pre>";
$hasSuperAdminSession = isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
session_write_close();
echo "</div>";

// Test 2: Admin Session
echo "<div class='box'><h2>Test 2: Admin Session</h2>";
session_name('vehiscan_admin');
@session_start();
echo "Session Name: <strong>" . session_name() . "</strong><br>";
echo "Session ID: <strong>" . session_id() . "</strong><br>";
echo "Session Data:<br><pre>";
print_r($_SESSION);
echo "</pre>";
$hasAdminSession = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
session_write_close();
echo "</div>";

// Test 3: Guard Session
echo "<div class='box'><h2>Test 3: Guard Session</h2>";
session_name('vehiscan_guard');
@session_start();
echo "Session Name: <strong>" . session_name() . "</strong><br>";
echo "Session ID: <strong>" . session_id() . "</strong><br>";
echo "Session Data:<br><pre>";
print_r($_SESSION);
echo "</pre>";
$hasGuardSession = isset($_SESSION['role']) && $_SESSION['role'] === 'guard';
session_write_close();
echo "</div>";

// Summary
echo "<div class='box " . ($hasSuperAdminSession || $hasAdminSession || $hasGuardSession ? "success" : "error") . "'>";
echo "<h2>Authentication Status</h2>";
echo "<ul>";
echo "<li>Super Admin: " . ($hasSuperAdminSession ? " LOGGED IN" : " Not logged in") . "</li>";
echo "<li>Admin: " . ($hasAdminSession ? " LOGGED IN" : " Not logged in") . "</li>";
echo "<li>Guard: " . ($hasGuardSession ? " LOGGED IN" : " Not logged in") . "</li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";