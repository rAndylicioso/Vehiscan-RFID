<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    // Use Lax for local network testing - allows multi-tab
    ini_set('session.cookie_samesite', 'Lax');
    // Don't use strict locking - allows concurrent access in multiple tabs
    ini_set('session.use_strict_mode', 0);
    // Enable secure cookie if HTTPS is active
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
               (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    ini_set('session.cookie_secure', $isHttps ? 1 : 0);
    session_name('vehiscan_admin'); // Admin session
    session_start();
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    
    // Check if it's an AJAX request (check multiple indicators)
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
    
    header("Location: /Vehiscan-RFID/auth/login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function logAudit($action, $table = null, $record_id = null, $details = null) {
    if (!isset($_SESSION['username'])) return;
    global $pdo;
    if (!isset($pdo)) return;
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'audit_logs'")->fetch();
        if (!$check) return;
        $stmt = $pdo->prepare("INSERT INTO audit_logs (username, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['username'], $action, $table, $record_id, $details, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}