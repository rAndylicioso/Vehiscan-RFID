-- Migration 001: Create Super Admin System
-- This migration creates the super admin table and removes default credentials
-- Created: 2025-11-20

-- Drop old admin entries (if using multi-admin from users table)
-- We'll migrate to a single super_admin table

CREATE TABLE IF NOT EXISTS super_admin (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  full_name VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login TIMESTAMP NULL,
  last_login_ip VARCHAR(45),
  password_changed_at TIMESTAMP NULL,
  require_password_change BOOLEAN DEFAULT 0,
  failed_login_attempts INT DEFAULT 0,
  locked_until TIMESTAMP NULL,
  two_factor_secret VARCHAR(100) NULL,
  two_factor_enabled BOOLEAN DEFAULT 0,
  is_setup_complete BOOLEAN DEFAULT 0,
  INDEX idx_username (username),
  INDEX idx_email (email),
  INDEX idx_locked (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create security settings table
CREATE TABLE IF NOT EXISTS security_settings (
  id INT PRIMARY KEY AUTO_INCREMENT,
  setting_key VARCHAR(100) UNIQUE NOT NULL,
  setting_value TEXT,
  setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
  description TEXT,
  updated_by VARCHAR(50),
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default security settings
INSERT INTO security_settings (setting_key, setting_value, setting_type, description) VALUES
('password_min_length', '12', 'integer', 'Minimum password length requirement'),
('password_require_uppercase', 'true', 'boolean', 'Require at least one uppercase letter'),
('password_require_lowercase', 'true', 'boolean', 'Require at least one lowercase letter'),
('password_require_number', 'true', 'boolean', 'Require at least one number'),
('password_require_special', 'true', 'boolean', 'Require at least one special character'),
('password_expiry_days', '90', 'integer', 'Days before password expires (0 = never)'),
('max_login_attempts', '5', 'integer', 'Maximum failed login attempts before lockout'),
('lockout_duration_minutes', '30', 'integer', 'Account lockout duration in minutes'),
('session_timeout_minutes', '30', 'integer', 'Session timeout in minutes'),
('https_only', 'true', 'boolean', 'Enforce HTTPS connections'),
('two_factor_enabled', 'false', 'boolean', 'Enable two-factor authentication'),
('backup_encryption_enabled', 'true', 'boolean', 'Encrypt backup files'),
('audit_log_retention_days', '365', 'integer', 'Days to retain audit logs')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- Create failed login attempts table
CREATE TABLE IF NOT EXISTS failed_login_attempts (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  user_agent TEXT,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reason ENUM('invalid_password', 'account_locked', 'account_not_found') DEFAULT 'invalid_password',
  INDEX idx_username (username),
  INDEX idx_ip (ip_address),
  INDEX idx_attempted (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create system installation table (tracks first-run setup)
CREATE TABLE IF NOT EXISTS system_installation (
  id INT PRIMARY KEY DEFAULT 1,
  is_installed BOOLEAN DEFAULT 0,
  installed_at TIMESTAMP NULL,
  installed_by VARCHAR(50),
  installation_key VARCHAR(64) UNIQUE,
  version VARCHAR(20),
  CHECK (id = 1) -- Only allow one row
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert initial installation record (not installed)
INSERT INTO system_installation (id, is_installed, installation_key) 
VALUES (1, 0, SHA2(UUID(), 256))
ON DUPLICATE KEY UPDATE id = id;

-- Keep existing users table for guards/homeowners
-- But ensure no default admin credentials exist
DELETE FROM users WHERE role = 'admin' AND username IN ('admin', 'administrator', 'root', 'test');

-- Add comments to existing tables
ALTER TABLE users COMMENT = 'Guards and homeowners only - admins use super_admin table';
