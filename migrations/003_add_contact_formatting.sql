-- Migration: Add Standardized Contact Field
-- Purpose: Store formatted contact numbers (e.g., 0912-345-6789)

-- Homeowners
ALTER TABLE homeowners 
MODIFY COLUMN contact VARCHAR(15) COMMENT 'Format: 0912-345-6789';

-- Guards  
ALTER TABLE guards 
MODIFY COLUMN contact VARCHAR(15) COMMENT 'Format: 0912-345-6789';

-- Visitor passes
ALTER TABLE visitor_passes 
ADD COLUMN IF NOT EXISTS visitor_contact VARCHAR(15) AFTER visitor_name COMMENT 'Format: 0912-345-6789';
