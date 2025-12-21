-- Migration: Support Multiple Vehicles Per Homeowner
-- Purpose: Allow homeowners to register multiple vehicles

-- Create vehicles table
CREATE TABLE IF NOT EXISTS vehicles (
  vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
  homeowner_id INT NOT NULL,
  plate_number VARCHAR(50) NOT NULL UNIQUE,
  vehicle_type VARCHAR(100) NOT NULL,
  color VARCHAR(50),
  make VARCHAR(100),
  model VARCHAR(100),
  year INT,
  is_primary BOOLEAN DEFAULT FALSE,
  status ENUM('active', 'inactive') DEFAULT 'active',
  registered_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (homeowner_id) REFERENCES homeowners(id) ON DELETE CASCADE,
  INDEX idx_homeowner_id (homeowner_id),
  INDEX idx_plate_number (plate_number),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migrate existing vehicle data from homeowners table
INSERT INTO vehicles (homeowner_id, plate_number, vehicle_type, color, is_primary, status)
SELECT 
  id, 
  plate_number, 
  vehicle_type, 
  color,
  TRUE as is_primary,
  'active' as status
FROM homeowners 
WHERE plate_number IS NOT NULL AND plate_number != ''
ON DUPLICATE KEY UPDATE vehicle_id = vehicle_id;

-- Add reference columns to logs tables for backward compatibility
ALTER TABLE access_logs 
ADD COLUMN IF NOT EXISTS vehicle_id INT AFTER homeowner_id,
ADD FOREIGN KEY IF NOT EXISTS (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE SET NULL;

ALTER TABLE recent_logs 
ADD COLUMN IF NOT EXISTS vehicle_id INT AFTER homeowner_id,
ADD FOREIGN KEY IF NOT EXISTS (vehicle_id) REFERENCES vehicles(vehicle_id) ON DELETE SET NULL;
