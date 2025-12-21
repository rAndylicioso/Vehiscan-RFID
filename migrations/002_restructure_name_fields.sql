-- Migration: Restructure Name Fields
-- Purpose: Split name into First, Middle, Last, Suffix

-- Homeowners table
ALTER TABLE homeowners 
ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) AFTER name,
ADD COLUMN IF NOT EXISTS middle_name VARCHAR(100) AFTER first_name,
ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) AFTER middle_name,
ADD COLUMN IF NOT EXISTS suffix VARCHAR(20) AFTER last_name;

-- Migrate existing data (if name exists)
UPDATE homeowners 
SET 
  first_name = SUBSTRING_INDEX(name, ' ', 1),
  last_name = SUBSTRING_INDEX(name, ' ', -1)
WHERE first_name IS NULL AND name IS NOT NULL;

-- Guards table
ALTER TABLE guards 
ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) AFTER name,
ADD COLUMN IF NOT EXISTS middle_name VARCHAR(100) AFTER first_name,
ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) AFTER middle_name,
ADD COLUMN IF NOT EXISTS suffix VARCHAR(20) AFTER last_name;

UPDATE guards 
SET 
  first_name = SUBSTRING_INDEX(name, ' ', 1),
  last_name = SUBSTRING_INDEX(name, ' ', -1)
WHERE first_name IS NULL AND name IS NOT NULL;

-- Visitor passes table
ALTER TABLE visitor_passes 
ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) AFTER visitor_name,
ADD COLUMN IF NOT EXISTS middle_name VARCHAR(100) AFTER first_name,
ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) AFTER middle_name,
ADD COLUMN IF NOT EXISTS suffix VARCHAR(20) AFTER last_name;

UPDATE visitor_passes 
SET 
  first_name = SUBSTRING_INDEX(visitor_name, ' ', 1),
  last_name = SUBSTRING_INDEX(visitor_name, ' ', -1)
WHERE first_name IS NULL AND visitor_name IS NOT NULL;
