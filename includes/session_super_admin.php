<?php
/**
 * Super Admin Session Handler
 * Separate session management for Super Admin with enhanced security
 */

if (session_status() === PHP_SESSION_NONE) {
    // Enhanced security settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    // Enable secure cookie if HTTPS is active
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
               (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    ini_set('session.cookie_secure', $isHttps ? 1 : 0);
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes
    
    session_name('vehiscan_superadmin');
    session_start();
}

// Session timeout check (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    
    // Check if it's an AJAX request
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
              (isset($_GET['ajax']) && $_GET['ajax'] == '1');
    
    if ($isAjax) {
        http_response_code(403);
        header('Content-Type: application/json');
        exit(json_encode([
            'error' => 'Session expired', 
            'redirect' => '/Vehiscan-RFID/auth/login.php?timeout=1'
        ]));
    }
    
    header('Location: /Vehiscan-RFID/auth/login.php?timeout=1');
    exit();
}

$_SESSION['last_activity'] = time();

// Regenerate session ID periodically for security
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 300) { // Regenerate every 5 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Verify super admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    // Check if it's an AJAX request
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
              (isset($_GET['ajax']) && $_GET['ajax'] == '1');
    
    if ($isAjax) {
        http_response_code(403);
        header('Content-Type: application/json');
        exit(json_encode([
            'error' => 'Unauthorized access', 
            'redirect' => '/Vehiscan-RFID/auth/login.php'
        ]));
    }
    
    header('Location: /Vehiscan-RFID/auth/login.php');
    exit();
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
