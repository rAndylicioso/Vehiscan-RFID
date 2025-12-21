<?php
/**
 * Delete (deactivate) vehicle
 */
require_once __DIR__ . '/../../includes/session_homeowner.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['homeowner_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $vehicleId = $data['vehicle_id'] ?? null;
    
    if (!$vehicleId) {
        echo json_encode(['success' => false, 'error' => 'Vehicle ID required']);
        exit();
    }
    
    // Verify ownership and check if it's the only vehicle
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM homeowner_vehicles
        WHERE homeowner_id = ? AND is_active = TRUE
    ");
    $stmt->execute([$_SESSION['homeowner_id']]);
    $result = $stmt->fetch();
    
    if ($result['total'] <= 1) {
        echo json_encode(['success' => false, 'error' => 'Cannot delete your only vehicle. Please add another vehicle first.']);
        exit();
    }
    
    // Soft delete (deactivate) vehicle
    $stmt = $pdo->prepare("
        UPDATE homeowner_vehicles 
        SET is_active = FALSE 
        WHERE id = ? AND homeowner_id = ?
    ");
    
    $stmt->execute([$vehicleId, $_SESSION['homeowner_id']]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Vehicle not found or access denied']);
        exit();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Vehicle removed successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Delete vehicle error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete vehicle: ' . $e->getMessage()
    ]);
}
