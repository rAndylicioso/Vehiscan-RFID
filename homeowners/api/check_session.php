<?php
require_once __DIR__ . '/../../includes/security_headers.php';

// Use the same session name as login
session_name('vehiscan_session');
session_start();

header('Content-Type: application/json');

// Check if session is valid
$valid = isset($_SESSION['homeowner_id']) && 
         $_SESSION['role'] === 'homeowner' &&
         isset($_SESSION['last_activity']);

// Check session timeout (30 minutes)
if ($valid && isset($_SESSION['last_activity'])) {
    $session_lifetime = 1800; // 30 minutes
    if (time() - $_SESSION['last_activity'] > $session_lifetime) {
        $valid = false;
        session_unset();
        session_destroy();
    } else {
        // Update last activity
        $_SESSION['last_activity'] = time();
    }
}

echo json_encode([
    'valid' => $valid,
    'timestamp' => time(),
    'remaining_time' => $valid && isset($_SESSION['last_activity']) 
        ? (1800 - (time() - $_SESSION['last_activity'])) 
        : 0
]);
