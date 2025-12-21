<?php
// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_name('vehiscan_admin');
    session_start();
}

// Check if session exists and has valid role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Session expired or invalid',
        'redirect' => '/Vehiscan-RFID/auth/login.php?timeout=1'
    ]);
    exit();
}

// Update session activity timestamp (lowercase to match session_admin.php)
$_SESSION['last_activity'] = time();

// Return success
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'timestamp' => time(),
    'last_activity' => $_SESSION['last_activity'],
    'message' => 'Session refreshed successfully'
]);
