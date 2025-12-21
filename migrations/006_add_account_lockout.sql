-- Add account lockout columns to homeowner_auth table
ALTER TABLE `homeowner_auth` 
ADD COLUMN `failed_login_attempts` INT(11) DEFAULT 0 AFTER `is_active`,
ADD COLUMN `locked_until` TIMESTAMP NULL DEFAULT NULL AFTER `failed_login_attempts`,
ADD COLUMN `last_failed_login` TIMESTAMP NULL DEFAULT NULL AFTER `locked_until`;
