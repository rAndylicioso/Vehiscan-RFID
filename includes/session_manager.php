<?php
/**
 * Unified Session Manager
 *
 * Provides a centralized and secure way to manage sessions for different user roles.
 */

if (!function_exists('start_secure_session')) {
    /**
     * Starts a secure session with role-specific configurations.
     *
     * @param array $config Configuration options for the session.
     *                      'session_name' => (string) The name of the session.
     *                      'timeout' => (int) Session idle timeout in seconds. 0 for no timeout.
     *                      'log_audit' => (bool) Whether to include the audit logging function.
     *                      'regeneration_interval' => (int) Seconds before regenerating session ID. 0 to disable.
     *                      'samesite' => (string) SameSite cookie policy ('Lax' or 'Strict').
     */
    function start_secure_session(array $config)
    {
        // Default configuration
        $defaults = [
            'session_name' => 'vehiscan_session',
            'timeout' => 1800, // 30 minutes
            'log_audit' => false,
            'regeneration_interval' => 900, // Regenerate ID every 15 minutes
            'samesite' => 'Lax', // Default to Lax
        ];
        $config = array_merge($defaults, $config);

        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', $config['samesite']);
            ini_set('session.use_strict_mode', 0); // Allows multi-tab login

            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                       (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
            ini_set('session.cookie_secure', $isHttps ? 1 : 0);
            session_name($config['session_name']);
            session_start();
        }

        // Handle session timeout
        if ($config['timeout'] > 0 && isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $config['timeout'])) {
            session_unset();
            session_destroy();

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

        // Regenerate session ID periodically to prevent session fixation
        if ($config['regeneration_interval'] > 0) {
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            }
            if (time() - $_SESSION['last_regeneration'] > $config['regeneration_interval']) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }

        // Regenerate CSRF token if it doesn't exist
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Include audit logger if required
        if ($config['log_audit']) {
            if (!function_exists('logAudit')) {
                function logAudit($action, $table = null, $record_id = null, $details = null) {
                    if (!isset($_SESSION['username'])) return;
                    global $pdo;
                    if (!isset($pdo)) return; // Requires db.php to be included
                    try {
                        $check = $pdo->query("SHOW TABLES LIKE 'audit_logs'")->fetch();
                        if (!$check) return;
                        $stmt = $pdo->prepare("INSERT INTO audit_logs (username, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$_SESSION['username'], $action, $table, $record_id, $details, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                    } catch (Exception $e) {
                        error_log("Audit log error: " . $e->getMessage());
                    }
                }
            }
        }
    }
}
