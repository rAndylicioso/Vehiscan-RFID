-- Performance Optimization Indexes
-- Creates indexes on frequently queried columns to improve query performance
-- Date: 2025-12-01

-- Index on recent_logs table for common queries
-- Speeds up queries filtering by created_at and status
CREATE INDEX IF NOT EXISTS idx_recent_logs_created_at ON recent_logs(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_recent_logs_status ON recent_logs(status);
CREATE INDEX IF NOT EXISTS idx_recent_logs_created_status ON recent_logs(created_at DESC, status);
CREATE INDEX IF NOT EXISTS idx_recent_logs_plate_number ON recent_logs(plate_number);

-- Index on homeowners table for user lookups
-- Speeds up queries joining with users table
CREATE INDEX IF NOT EXISTS idx_homeowners_user_id ON homeowners(user_id);
CREATE INDEX IF NOT EXISTS idx_homeowners_plate_number ON homeowners(plate_number);

-- Index on visitor_passes table for homeowner lookups
-- Speeds up queries filtering by homeowner_id and status
CREATE INDEX IF NOT EXISTS idx_visitor_passes_homeowner_id ON visitor_passes(homeowner_id);
CREATE INDEX IF NOT EXISTS idx_visitor_passes_status ON visitor_passes(status);
CREATE INDEX IF NOT EXISTS idx_visitor_passes_valid_from ON visitor_passes(valid_from);
CREATE INDEX IF NOT EXISTS idx_visitor_passes_valid_until ON visitor_passes(valid_until);
CREATE INDEX IF NOT EXISTS idx_visitor_passes_qr_token ON visitor_passes(qr_token);

-- Index on users table for authentication
CREATE INDEX IF NOT EXISTS idx_users_username ON users(username);
CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);

-- Index on audit_logs table for monitoring
CREATE INDEX IF NOT EXISTS idx_audit_logs_created_at ON audit_logs(created_at DESC);
CREATE INDEX IF NOT EXISTS idx_audit_logs_action ON audit_logs(action);
CREATE INDEX IF NOT EXISTS idx_audit_logs_user_id ON audit_logs(user_id);

-- Index on failed_login_attempts for security monitoring
CREATE INDEX IF NOT EXISTS idx_failed_login_ip ON failed_login_attempts(ip_address);
CREATE INDEX IF NOT EXISTS idx_failed_login_username ON failed_login_attempts(username);
CREATE INDEX IF NOT EXISTS idx_failed_login_attempted_at ON failed_login_attempts(attempted_at);

-- Super admin table indexes
CREATE INDEX IF NOT EXISTS idx_super_admin_username ON super_admin(username);
CREATE INDEX IF NOT EXISTS idx_super_admin_email ON super_admin(email);
