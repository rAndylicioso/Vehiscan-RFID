<?php
/**
 * Homeowner Session Management
 */

// Configure session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

session_name('vehiscan_session');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if homeowner is logged in
if (!isset($_SESSION['homeowner_id']) || $_SESSION['role'] !== 'homeowner') {
    if (php_sapi_name() === 'cli') {
        return; // Allow CLI access for testing
    }
    
    // For AJAX requests, return JSON error
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Session expired']);
        exit();
    }
    
    // For regular requests, redirect to login
    header("Location: ../auth/login.php");
    exit();
}

// Session timeout check (30 minutes)
$session_lifetime = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_lifetime)) {
    session_unset();
    session_destroy();
    
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Session timeout']);
        exit();
    }
    
    header('Location: ../auth/login.php?timeout=1');
    exit();
}

// Regenerate session ID periodically
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 600) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

$_SESSION['last_activity'] = time();
