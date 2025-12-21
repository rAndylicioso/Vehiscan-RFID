-- Migration 002: Enhanced Audit Logging System
-- Creates comprehensive audit logging with event types and severity levels
-- Created: 2025-11-20

CREATE TABLE IF NOT EXISTS audit_logs_enhanced (
  id INT PRIMARY KEY AUTO_INCREMENT,
  event_type ENUM('auth', 'data', 'config', 'backup', 'security', 'employee') NOT NULL,
  action VARCHAR(100) NOT NULL,
  username VARCHAR(50),
  user_role VARCHAR(20),
  table_name VARCHAR(100),
  record_id INT,
  old_values JSON,
  new_values JSON,
  ip_address VARCHAR(45),
  user_agent TEXT,
  request_method VARCHAR(10),
  request_uri TEXT,
  severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
  status ENUM('success', 'failure', 'warning') DEFAULT 'success',
  error_message TEXT,
  session_id VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_event (event_type),
  INDEX idx_username (username),
  INDEX idx_created (created_at),
  INDEX idx_severity (severity),
  INDEX idx_status (status),
  INDEX idx_table (table_name),
  INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comment
ALTER TABLE audit_logs_enhanced 
COMMENT = 'Enhanced audit logging system with comprehensive event tracking';
