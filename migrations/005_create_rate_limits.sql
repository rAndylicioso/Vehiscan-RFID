-- Rate Limiting Table
-- This table tracks request attempts for rate limiting and brute force prevention

CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL COMMENT 'IP address or username',
    action VARCHAR(50) NOT NULL DEFAULT 'default' COMMENT 'Type of action being rate limited',
    ip_address VARCHAR(45) COMMENT 'Client IP address',
    user_agent TEXT COMMENT 'Client user agent',
    metadata JSON COMMENT 'Additional context about the attempt',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_identifier_action (identifier, action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cleanup old entries (run periodically via cron)
-- DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR);
