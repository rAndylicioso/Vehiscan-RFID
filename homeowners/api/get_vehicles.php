<?php
/**
 * Get all vehicles for logged-in homeowner
 * Uses the canonical 'vehicles' table
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
    // Get all vehicles for this homeowner from the canonical vehicles table
    $stmt = $pdo->prepare("
        SELECT 
            id,
            vehicle_type,
            color,
            plate_number,
            brand,
            model,
            year,
            is_primary,
            is_active,
            rfid_uid,
            registered_at as created_at
        FROM vehicles
        WHERE homeowner_id = ? AND is_active = TRUE
        ORDER BY is_primary DESC, registered_at DESC
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
        'error' => 'Failed to fetch vehicles. Please try again later.'
    ]);
}
