-- Migration: Add authentication fields to homeowners table
-- Description: Adds username and password_hash columns for homeowner login functionality
-- Date: 2025-12-03

-- Add username column
ALTER TABLE `homeowners` 
ADD COLUMN `username` VARCHAR(50) UNIQUE DEFAULT NULL AFTER `address`,
ADD COLUMN `password_hash` VARCHAR(255) DEFAULT NULL AFTER `username`;

-- Add index for faster username lookups
CREATE INDEX `idx_homeowners_username` ON `homeowners` (`username`);

-- Update existing homeowners with temporary usernames (if any exist)
-- Admins can update these later or homeowners can be prompted to set their credentials
UPDATE `homeowners` 
SET `username` = CONCAT('homeowner_', `id`),
    `password_hash` = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' -- password: 'password'
WHERE `username` IS NULL;

-- Make username required for new entries (existing ones already have temp values)
ALTER TABLE `homeowners` 
MODIFY COLUMN `username` VARCHAR(50) NOT NULL,
MODIFY COLUMN `password_hash` VARCHAR(255) NOT NULL;
