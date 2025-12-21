<?php
/**
 * Multi-Tab Friendly Session Configuration
 * Allows same user in multiple tabs/browsers simultaneously
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Session settings for multi-tab support
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    // Don't use strict locking - allows concurrent access
    ini_set('session.use_strict_mode', 0);
    
    session_name('vehiscan_session');
    session_start();
}

// Session timeout (30 minutes of inactivity)
if (isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    if ($inactive_time > 1800) { // 30 minutes
        session_unset();
        session_destroy();
        if (!headers_sent()) {
            header("Location: /Vehiscan-RFID/auth/login.php?timeout=1");
            exit;
        }
    }
}
$_SESSION['last_activity'] = time();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper function for audit logging (optional)
function logAudit($action, $table = null, $record_id = null, $details = null) {
    if (!isset($_SESSION['username'])) return;
    
    global $pdo;
    if (!isset($pdo)) return;
    
    try {
        // Check if audit_logs table exists
        $check = $pdo->query("SHOW TABLES LIKE 'audit_logs'")->fetch();
        if (!$check) return;
        
        $stmt = $pdo->prepare("INSERT INTO audit_logs (username, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['username'],
            $action,
            $table,
            $record_id,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}
/**
 * Check if user has a specific role
 * Supports multiple roles separated by commas (e.g., "admin,guard")
 */
function hasRole($required_role) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    $user_roles = array_map('trim', explode(',', $_SESSION['role']));
    return in_array($required_role, $user_roles);
}