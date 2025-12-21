<?php
// Configure session for 24/7 guard access (no timeout)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
// Use Lax for local network testing, Strict for production
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 86400); // 24 hours
ini_set('session.cookie_lifetime', 0); // Session cookie (until browser closes)
// Enable secure cookie if HTTPS is active
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
           (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_name('vehiscan_guard');
    session_start();
    
    // Debug session start
    error_log('Guard Session Started: ' . json_encode([
        'session_id' => session_id(),
        'time' => date('Y-m-d H:i:s')
    ]));
}

// No timeout check for guard - they need 24/7 access
// Just update last activity for logging purposes
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}