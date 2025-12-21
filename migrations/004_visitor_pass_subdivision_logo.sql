-- Migration: Add Subdivision-Specific Visitor Pass Customization
-- Purpose: Support custom logos and branding per subdivision

-- Add subdivision logo field to homeowners table
ALTER TABLE homeowners 
ADD COLUMN IF NOT EXISTS subdivision_logo VARCHAR(255) DEFAULT 'ville_de_palme.png' AFTER block_lot;

-- Add customization fields to visitor_passes
ALTER TABLE visitor_passes 
ADD COLUMN IF NOT EXISTS logo_file VARCHAR(255) DEFAULT 'ville_de_palme.png' AFTER qr_code_data,
ADD COLUMN IF NOT EXISTS subdivision_name VARCHAR(255) DEFAULT 'Ville de Palme' AFTER logo_file;

-- Set default for existing passes
UPDATE visitor_passes 
SET logo_file = 'ville_de_palme.png', subdivision_name = 'Ville de Palme' 
WHERE logo_file IS NULL;
