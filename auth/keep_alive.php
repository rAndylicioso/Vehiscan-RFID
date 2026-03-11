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
    // Fallback: try Referer as last resort
    $origin = $_SERVER['HTTP_REFERER'] ?? '';
    if (strpos($origin, '/guard/') !== false) {
        require_once __DIR__ . '/../includes/session_guard.php';
    } elseif (strpos($origin, '/admin/') !== false) {
        require_once __DIR__ . '/../includes/session_admin_unified.php';
    } elseif (strpos($origin, '/homeowners/') !== false) {
        require_once __DIR__ . '/../includes/session_homeowner.php';
    } else {
        if (session_status() === PHP_SESSION_NONE) {
            $appSavePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vehiscan_sessions';
            if (!is_dir($appSavePath)) { mkdir($appSavePath, 0700, true); }
            ini_set('session.save_path', $appSavePath);
            ini_set('session.gc_maxlifetime', 3600);
            session_start();
        }
    }
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
