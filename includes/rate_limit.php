<?php
/**
 * Rate Limiting Helper Functions
 * Location: includes/rate_limit.php
 * 
 * Prevents brute force attacks and API abuse
 */

/**
 * Check if action is rate limited
 * Only counts FAILED attempts, not successful ones or page loads
 * @param string $action - The action being performed (login, api_call, etc.)
 * @param int $max_attempts - Maximum failed attempts allowed
 * @param int $window_minutes - Time window in minutes
 * @return array ['allowed' => bool, 'remaining' => int, 'reset_at' => timestamp]
 */
function checkRateLimit($action, $max_attempts = 5, $window_minutes = 15) {
    global $pdo;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_id = $_SESSION['user_id'] ?? null;
    
    // Create table if not exists
    createRateLimitTable();
    
    // Clean old entries
    cleanOldRateLimits();
    
    // Get FAILED attempts only in current window (success=0)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as attempts, 
               MIN(created_at) as first_attempt
        FROM rate_limits 
        WHERE ip_address = ? 
        AND action = ? 
        AND success = 0
        AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ");
    $stmt->execute([$ip, $action, $window_minutes]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $attempts = (int)$result['attempts'];
    $remaining = max(0, $max_attempts - $attempts);
    
    // Calculate when the limit resets
    if ($result['first_attempt']) {
        $reset_at = strtotime($result['first_attempt']) + ($window_minutes * 60);
    } else {
        $reset_at = time() + ($window_minutes * 60);
    }
    
    $allowed = $attempts < $max_attempts;
    
    return [
        'allowed' => $allowed,
        'remaining' => $remaining,
        'reset_at' => $reset_at,
        'reset_in' => max(0, $reset_at - time())
    ];
}

/**
 * Log an attempt
 * @param string $action - The action being performed
 * @param bool $success - Whether the attempt was successful
 */
function logRateLimit($action, $success = false) {
    global $pdo;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_id = $_SESSION['user_id'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    createRateLimitTable();
    
    $stmt = $pdo->prepare("
        INSERT INTO rate_limits (ip_address, action, user_id, user_agent, success) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$ip, $action, $user_id, $user_agent, $success ? 1 : 0]);
}

/**
 * Block request if rate limited
 * @param string $action
 * @param int $max_attempts
 * @param int $window_minutes
 * @param bool $return_error - If true, returns error instead of exiting (for web forms)
 */
function enforceRateLimit($action, $max_attempts = 5, $window_minutes = 15, $return_error = false) {
    $check = checkRateLimit($action, $max_attempts, $window_minutes);
    
    if (!$check['allowed']) {
        $minutes = ceil($check['reset_in'] / 60);
        $error_message = "Too many attempts. Please try again in $minutes minutes.";
        
        // If return_error is true, return the error message instead of exiting
        if ($return_error) {
            return $error_message;
        }
        
        // Check if this is an AJAX/API request or web form
        $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                   strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        $accept_header = $_SERVER['HTTP_ACCEPT'] ?? '';
        $wants_json = strpos($accept_header, 'application/json') !== false;
        
        http_response_code(429);
        header('Retry-After: ' . $check['reset_in']);
        
        // Return JSON for AJAX/API requests
        if ($is_ajax || $wants_json) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Too many attempts',
                'message' => $error_message,
                'retry_after' => $check['reset_in']
            ]);
            exit;
        }
        
        // For web forms, redirect back with error parameter
        $referer = $_SERVER['HTTP_REFERER'] ?? 'login.php';
        $separator = strpos($referer, '?') !== false ? '&' : '?';
        header("Location: {$referer}{$separator}rate_limit=" . urlencode($error_message));
        exit;
    }
    
    return null;
}

/**
 * Create rate_limits table if it doesn't exist
 */
function createRateLimitTable() {
    global $pdo;
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            action VARCHAR(50) NOT NULL,
            user_id INT NULL,
            user_agent TEXT NULL,
            success TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_action_time (ip_address, action, created_at),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

/**
 * Clean old rate limit entries (older than 24 hours)
 */
function cleanOldRateLimits() {
    global $pdo;
    
    // Run cleanup only 1% of the time (probabilistic cleanup)
    if (rand(1, 100) === 1) {
        $pdo->exec("
            DELETE FROM rate_limits 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            LIMIT 1000
        ");
    }
}

/**
 * Get rate limit stats for monitoring
 */
function getRateLimitStats() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT 
            action,
            COUNT(*) as total_attempts,
            SUM(success) as successful,
            COUNT(DISTINCT ip_address) as unique_ips,
            MAX(created_at) as last_attempt
        FROM rate_limits
        WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY action
        ORDER BY total_attempts DESC
    ");
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Clear rate limits for an IP and action (e.g., after successful login)
 * This prevents page refreshes from counting as new attempts
 * @param string $action - The action to clear limits for
 * @param string $ip - Optional IP address (defaults to current IP)
 */
function clearRateLimit($action, $ip = null) {
    global $pdo;
    
    if ($ip === null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    $stmt = $pdo->prepare("
        DELETE FROM rate_limits 
        WHERE ip_address = ? 
        AND action = ?
    ");
    $stmt->execute([$ip, $action]);
}
