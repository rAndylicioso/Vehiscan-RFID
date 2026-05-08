<?php
/**
 * Keep-Alive Endpoint
 * Updates session activity to prevent timeout
 */

// Start session based on session cookie present (more reliable than Referer header)
// Priority: admin/superadmin cookies first, then guard, then homeowner.
// If both admin and guard cookies exist, prefer admin to avoid cross-session issues.
$hasAdminCookie = isset($_COOKIE['vehiscan_superadmin']) || isset($_COOKIE['vehiscan_admin']);
if ($hasAdminCookie) {
    require_once __DIR__ . '/../includes/session_admin_unified.php';
} elseif (isset($_COOKIE['vehiscan_guard'])) {
    require_once __DIR__ . '/../includes/session_guard.php';
} elseif (isset($_COOKIE['vehiscan_homeowner'])) {
    require_once __DIR__ . '/../includes/session_homeowner.php';
} else {
    // No recognized role cookie, fail closed.
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No active session'
    ]);
    exit;
}

header('Content-Type: application/json');

// Update last activity
if (isset($_SESSION['username'])) {
    $_SESSION['last_activity'] = time();
    
    echo json_encode([
        'success' => true,
        'message' => 'Session updated'
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'No active session'
    ]);
}
