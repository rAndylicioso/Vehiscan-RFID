<?php
/**
 * Get Homeowner's Vehicles
 */

require_once __DIR__ . '/../../includes/session_homeowner.php';
require_once __DIR__ . '/../../db.php';

$homeownerId = $_SESSION['homeowner_id'] ?? 0;

try {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            plate_number,
            vehicle_type,
            color,
            brand,
            model,
            year,
            is_primary,
            is_active,
            registered_at
        FROM vehicles
        WHERE homeowner_id = ? AND is_active = TRUE
        ORDER BY is_primary DESC, registered_at DESC
    ");
    
    $stmt->execute([$homeownerId]);
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
