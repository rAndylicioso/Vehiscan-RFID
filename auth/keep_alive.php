<?php
/**
 * Keep-Alive Endpoint
 * Updates session activity to prevent timeout
 */

// Start session based on request origin
$origin = $_SERVER['HTTP_REFERER'] ?? '';

if (strpos($origin, '/guard/') !== false) {
    require_once __DIR__ . '/../includes/session_guard.php';
} elseif (strpos($origin, '/admin/') !== false) {
    require_once __DIR__ . '/../includes/session_admin_unified.php';
} elseif (strpos($origin, '/homeowners/') !== false) {
    session_name('vehiscan_session');
    session_start();
} else {
    // Default session start
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

header('Content-Type: application/json');

// Update last activity
if (isset($_SESSION['username'])) {
    $_SESSION['last_activity'] = time();
    
    echo json_encode([
        'success' => true,
        'message' => 'Session updated',
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'] ?? 'unknown'
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No active session'
    ]);
}
