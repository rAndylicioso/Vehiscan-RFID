<?php
/**
 * Simple Rate Limiter
 * Prevents brute force attacks by limiting request frequency
 */

class RateLimiter {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Check if action is rate limited
     * @param string $identifier Usually IP address or username
     * @param string $action Action type (e.g., 'login', 'api_call')
     * @param int $maxAttempts Maximum attempts allowed
     * @param int $windowMinutes Time window in minutes
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_time' => string]
     */
    public function check($identifier, $action = 'default', $maxAttempts = 5, $windowMinutes = 15) {
        try {
            // Clean old attempts
            $windowStart = date('Y-m-d H:i:s', strtotime("-$windowMinutes minutes"));
            
            $this->pdo->prepare("
                DELETE FROM rate_limits 
                WHERE created_at < ?
            ")->execute([$windowStart]);
            
            // Count recent attempts
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as attempt_count 
                FROM rate_limits 
                WHERE identifier = ? 
                AND action = ? 
                AND created_at >= ?
            ");
            $stmt->execute([$identifier, $action, $windowStart]);
            $result = $stmt->fetch();
            $attemptCount = $result ? (int)$result['attempt_count'] : 0;
            
            $remaining = max(0, $maxAttempts - $attemptCount);
            $resetTime = date('Y-m-d H:i:s', strtotime("+$windowMinutes minutes"));
            
            return [
                'allowed' => $attemptCount < $maxAttempts,
                'remaining' => $remaining,
                'reset_time' => $resetTime,
                'attempts' => $attemptCount
            ];
        } catch (PDOException $e) {
            // If table doesn't exist or error, allow the action
            error_log("Rate limiter error: " . $e->getMessage());
            return ['allowed' => true, 'remaining' => $maxAttempts, 'reset_time' => null, 'attempts' => 0];
        }
    }
    
    /**
     * Record an attempt
     * @param string $identifier
     * @param string $action
     * @param array $metadata Optional additional data
     */
    public function recordAttempt($identifier, $action = 'default', $metadata = []) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO rate_limits (identifier, action, ip_address, user_agent, metadata, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $identifier,
                $action,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                json_encode($metadata)
            ]);
        } catch (PDOException $e) {
            // Silently fail if table doesn't exist
            error_log("Rate limiter record error: " . $e->getMessage());
        }
    }
    
    /**
     * Reset rate limit for an identifier
     * @param string $identifier
     * @param string $action
     */
    public function reset($identifier, $action = 'default') {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM rate_limits 
                WHERE identifier = ? AND action = ?
            ");
            $stmt->execute([$identifier, $action]);
        } catch (PDOException $e) {
            error_log("Rate limiter reset error: " . $e->getMessage());
        }
    }
    
    /**
     * Check if identifier is currently locked out
     * @param string $identifier
     * @param string $action
     * @return array ['locked' => bool, 'unlock_time' => string|null]
     */
    public function isLockedOut($identifier, $action = 'default', $maxAttempts = 5, $lockoutMinutes = 15) {
        $result = $this->check($identifier, $action, $maxAttempts, $lockoutMinutes);
        
        if (!$result['allowed']) {
            return [
                'locked' => true,
                'unlock_time' => $result['reset_time'],
                'attempts' => $result['attempts']
            ];
        }
        
        return ['locked' => false, 'unlock_time' => null, 'attempts' => $result['attempts']];
    }
}

/**
 * Migration SQL to create rate_limits table:
 * 
 * CREATE TABLE IF NOT EXISTS rate_limits (
 *     id INT AUTO_INCREMENT PRIMARY KEY,
 *     identifier VARCHAR(255) NOT NULL,
 *     action VARCHAR(50) NOT NULL DEFAULT 'default',
 *     ip_address VARCHAR(45),
 *     user_agent TEXT,
 *     metadata JSON,
 *     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *     INDEX idx_identifier_action (identifier, action),
 *     INDEX idx_created_at (created_at)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */
