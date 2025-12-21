<?php
// Unified session handler for both admin and super_admin
// SIMPLIFIED FOR LOCALHOST - Works with both admin types

if (session_status() === PHP_SESSION_NONE) {
    // Relaxed settings for localhost development
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', 0); // Allow HTTP for localhost
    ini_set('session.use_strict_mode', 0); // Allow multiple tabs
    
    // Check which session cookie exists
    $sessionStarted = false;
    
    // Try vehiscan_superadmin FIRST (super admin has priority)
    if (isset($_COOKIE['vehiscan_superadmin'])) {
        session_name('vehiscan_superadmin');
        session_start();
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
            $sessionStarted = true;
        } else {
            session_write_close();
        }
    }
    
    // Try vehiscan_admin if super admin didn't work
    if (!$sessionStarted && isset($_COOKIE['vehiscan_admin'])) {
        session_name('vehiscan_admin');
        session_start();
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            $sessionStarted = true;
        } else {
            session_write_close();
        }
    }
    
    // Fallback: try both without cookie check (for edge cases after login redirect)
    if (!$sessionStarted) {
        // Try superadmin first
        session_name('vehiscan_superadmin');
        @session_start();
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
            $sessionStarted = true;
        } else {
            session_write_close();
            // Try regular admin
            session_name('vehiscan_admin');
            @session_start();
            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
                $sessionStarted = true;
            } else {
                session_write_close();
                // Last resort: start default admin session (empty state)
                session_name('vehiscan_admin');
                @session_start();
            }
        }
    }
}

// Session timeout check (30 minutes = 1800 seconds)
// DISABLED FOR LOCALHOST DEBUGGING - Re-enable for production
// Uncomment the code below when deploying
/*
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
    
    header("Location: /Vehiscan-RFID/auth/login.php?timeout=1");
    exit;
}
*/
// Update last activity timestamp
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
    } catch (Exception $e) {}
}
