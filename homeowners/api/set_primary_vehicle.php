<?php
require_once __DIR__ . '/../../includes/session_homeowner.php';
require_once __DIR__ . '/../../db.php';
header('Content-Type: application/json');

$homeownerId = $_SESSION['homeowner_id'];

$data = json_decode(file_get_contents('php://input'), true);
$vehicleId = $data['vehicle_id'] ?? null;

// Validate CSRF token
$csrfToken = $data['csrf_token'] ?? '';
if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

if (!$vehicleId) {
    echo json_encode(['success' => false, 'message' => 'Vehicle ID required']);
    exit();
}

try {
    // First, unset all other primary vehicles for this homeowner
    $pdo->prepare("UPDATE vehicles SET is_primary = FALSE WHERE homeowner_id = ?")->execute([$homeownerId]);
    
    // Set this vehicle as primary
    $stmt = $pdo->prepare("UPDATE vehicles SET is_primary = TRUE WHERE id = ? AND homeowner_id = ?");
    $stmt->execute([$vehicleId, $homeownerId]);
    
    echo json_encode(['success' => true, 'message' => 'Primary vehicle updated']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again later.']);
}
