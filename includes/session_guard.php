<?php
require_once __DIR__ . '/session_helpers.php';
// Configure session for guard access
initializeVehiscanSessionPath();
// Use Lax for local network testing, Strict for production
ini_set('session.gc_maxlifetime', 28800); // 8 hours (guard shift)
ini_set('session.cookie_lifetime', 0); // Session cookie (until browser closes)
// Enable secure cookie if HTTPS is active
ini_set('session.cookie_secure', vehiscanIsHttpsRequest() ? '1' : '0');

// Removed aggressive cookie cleanup to allow simultaneous multi-role sessions (Guard, Admin, Homeowner).
// Each role now has its own unique session cookie name.
/* 
$isAjaxRequest = (!empty($_SERVER['HTTP_X_REQUEST_WITH']) && strtolower($_SERVER['HTTP_X_REQUEST_WITH']) == 'xmlhttprequest') || 
                 (isset($_GET['ajax']) && $_GET['ajax'] == '1') ||
                 (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false);

if (!$isAjaxRequest) {
    foreach (['vehiscan_admin', 'vehiscan_superadmin', 'vehiscan_homeowner'] as $sName) {
        if (isset($_COOKIE[$sName])) {
            setcookie($sName, '', time() - 3600, '/');
            unset($_COOKIE[$sName]);
        }
    }
}
*/

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    vehiscanStartNamedSession('vehiscan_guard');
    
    // Debug session start (only in development)
    if (defined('APP_DEBUG') && APP_DEBUG) {
        error_log('Guard Session Started: ' . json_encode([
            'session_id' => session_id(),
            'time' => date('Y-m-d H:i:s')
        ]));
    }
}

// Session timeout: 8 hours (one guard shift)
$guard_session_lifetime = 28800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $guard_session_lifetime)) {
    vehiscanClearSessionAndCookie();
    
    // For AJAX requests, return JSON error
    if (vehiscanIsAjaxRequest()) {
        vehiscanJsonExit(401, ['success' => false, 'error' => 'Session expired after shift timeout']);
    }
    
    header('Location: /Vehiscan-RFID/auth/login.php?timeout=1');
    exit();
}

$_SESSION['last_activity'] = time();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = vehiscanGenerateCsrfToken();
}

if (($_SESSION['role'] ?? '') !== 'guard') {
    vehiscanClearSessionAndCookie();

    if (vehiscanIsAjaxRequest()) {
        vehiscanJsonExit(401, ['success' => false, 'error' => 'Unauthorized']);
    }

    header('Location: /Vehiscan-RFID/auth/login.php');
    exit();
}

// Expose CSRF via header for JS auto-refresh
header('X-CSRF-Token: ' . $_SESSION['csrf_token']);

if (!function_exists('logAudit')) {
    function logAudit($action, $table = null, $record_id = null, $details = null) {
        if (!isset($_SESSION['username'])) return;
        global $pdo;
        if (!isset($pdo)) return;
        try {
            $check = $pdo->query("SHOW TABLES LIKE 'audit_logs'")->fetch();
            if (!$check) return;
            $stmt = $pdo->prepare("INSERT INTO audit_logs (username, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['username'], $action, $table, $record_id, $details, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        } catch (Exception $e) {}
    }
}