-- Migration: Add Email and Account Status Support
-- Purpose: Enable email-based login and admin approval workflow

-- Add email column to all user tables
ALTER TABLE homeowners 
ADD COLUMN IF NOT EXISTS email VARCHAR(255) UNIQUE AFTER username,
ADD COLUMN IF NOT EXISTS account_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER password,
ADD COLUMN IF NOT EXISTS approved_by INT NULL AFTER account_status,
ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL AFTER approved_by;

ALTER TABLE guards 
ADD COLUMN IF NOT EXISTS email VARCHAR(255) UNIQUE AFTER username,
ADD COLUMN IF NOT EXISTS account_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER password,
ADD COLUMN IF NOT EXISTS approved_by INT NULL AFTER account_status,
ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL AFTER approved_by;

ALTER TABLE admins 
ADD COLUMN IF NOT EXISTS email VARCHAR(255) UNIQUE AFTER username,
ADD COLUMN IF NOT EXISTS account_status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved' AFTER password,
ADD COLUMN IF NOT EXISTS approved_by INT NULL AFTER account_status,
ADD COLUMN IF NOT EXISTS approved_at DATETIME NULL AFTER approved_by;

-- Add foreign key constraints for approved_by
ALTER TABLE homeowners ADD FOREIGN KEY IF NOT EXISTS (approved_by) REFERENCES admins(id) ON DELETE SET NULL;
ALTER TABLE guards ADD FOREIGN KEY IF NOT EXISTS (approved_by) REFERENCES admins(id) ON DELETE SET NULL;

-- Set existing accounts as approved
UPDATE homeowners SET account_status = 'approved' WHERE account_status IS NULL;
UPDATE guards SET account_status = 'approved' WHERE account_status IS NULL;
UPDATE admins SET account_status = 'approved' WHERE account_status IS NULL;

-- Create index on email for faster lookups
CREATE INDEX IF NOT EXISTS idx_homeowners_email ON homeowners(email);
CREATE INDEX IF NOT EXISTS idx_guards_email ON guards(email);
CREATE INDEX IF NOT EXISTS idx_admins_email ON admins(email);
