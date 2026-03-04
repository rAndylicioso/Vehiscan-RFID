<?php
// Configure session for guard access
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
// Use Lax for local network testing, Strict for production
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 28800); // 8 hours (guard shift)
ini_set('session.cookie_lifetime', 0); // Session cookie (until browser closes)
// Enable secure cookie if HTTPS is active
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name('vehiscan_guard');
    session_start();
    
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
    session_unset();
    session_destroy();
    
    // For AJAX requests, return JSON error
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Session expired after shift timeout']);
        exit();
    }
    
    header('Location: ../auth/login.php?timeout=1');
    exit();
}

$_SESSION['last_activity'] = time();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}