<?php
// auth/logout.php
// Universal logout handler - handles all session types
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/audit_logger.php';

// Initialize audit logger and capture username before destroying sessions
$username = null;
$role = null;
try {
    AuditLogger::init($pdo);
    
    // Try to get username from any active session
    foreach (['vehiscan_superadmin', 'vehiscan_admin', 'vehiscan_guard', 'vehiscan_session'] as $sName) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        session_name($sName);
        session_start();
        if (isset($_SESSION['username'])) {
            $username = $_SESSION['username'];
            $role = $_SESSION['role'] ?? 'unknown';
            session_write_close();
            break;
        }
        session_write_close();
    }
} catch (Exception $e) {
    // Audit logger not available
}

// Function to destroy a specific session
function destroySessionByName($sessionName) {
    // Close current session if active
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    
    // Start the specific session
    session_name($sessionName);
    session_start();
    
    // Destroy session data
    $_SESSION = array();
    session_unset();
    session_destroy();
    
    // Clear session cookie
    if (isset($_COOKIE[$sessionName])) {
        setcookie($sessionName, '', time() - 3600, '/');
    }
    
    session_write_close();
}

// Destroy all possible session types (including Super Admin)
$sessionTypes = ['vehiscan_superadmin', 'vehiscan_admin', 'vehiscan_guard', 'vehiscan_session'];

foreach ($sessionTypes as $sessionName) {
    destroySessionByName($sessionName);
}

// Log the logout action
error_log("User logged out - All sessions destroyed at " . date('Y-m-d H:i:s'));

// Log to audit system
try {
    if ($username) {
        AuditLogger::logAuth($role . '_logout', true, $username);
    }
} catch (Exception $e) {
    // Audit logger not available
}

// If AJAX (fetch) request, return JSON
if (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Logged out']);
    exit;
}

// Otherwise, redirect normally to login page
header("Location: login.php");
exit;
?>

