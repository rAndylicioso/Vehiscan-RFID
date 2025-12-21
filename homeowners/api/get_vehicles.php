<?php
/**
 * Get all vehicles for logged-in homeowner
 */
require_once __DIR__ . '/../../includes/session_homeowner.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['homeowner_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // Check if homeowner_vehicles table exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'homeowner_vehicles'");
    
    if ($checkTable->rowCount() === 0) {
        // Create homeowner_vehicles table
        $pdo->exec("
            CREATE TABLE homeowner_vehicles (
                id INT PRIMARY KEY AUTO_INCREMENT,
                homeowner_id INT NOT NULL,
                vehicle_type VARCHAR(50) NOT NULL,
                color VARCHAR(50) NOT NULL,
                plate_number VARCHAR(20) NOT NULL UNIQUE,
                vehicle_img VARCHAR(255),
                is_primary BOOLEAN DEFAULT FALSE,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (homeowner_id) REFERENCES homeowners(id) ON DELETE CASCADE,
                INDEX idx_homeowner (homeowner_id),
                INDEX idx_plate (plate_number)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Migrate existing vehicle data from homeowners table
        $pdo->exec("
            INSERT INTO homeowner_vehicles (homeowner_id, vehicle_type, color, plate_number, vehicle_img, is_primary)
            SELECT id, vehicle_type, color, plate_number, car_img, TRUE
            FROM homeowners
            WHERE plate_number IS NOT NULL AND plate_number != ''
        ");
        
        error_log("Created homeowner_vehicles table and migrated existing data");
    }
    
    // Get all vehicles for this homeowner
    $stmt = $pdo->prepare("
        SELECT 
            id,
            vehicle_type,
            color,
            plate_number,
            vehicle_img,
            is_primary,
            is_active,
            created_at
        FROM homeowner_vehicles
        WHERE homeowner_id = ? AND is_active = TRUE
        ORDER BY is_primary DESC, created_at DESC
    ");
    
    $stmt->execute([$_SESSION['homeowner_id']]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'vehicles' => $vehicles
    ]);
    
} catch (Exception $e) {
    error_log("Get vehicles error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch vehicles: ' . $e->getMessage()
    ]);
}
