<?php
/**
 * Add new vehicle for homeowner
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
    $vehicleType = trim($_POST['vehicle_type'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $plateNumber = strtoupper(trim($_POST['plate_number'] ?? ''));
    $isPrimary = isset($_POST['is_primary']) && $_POST['is_primary'] === 'true';
    
    // Validate required fields
    if (empty($vehicleType) || empty($color) || empty($plateNumber)) {
        echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit();
    }
    
    // Check for duplicate plate number
    $stmt = $pdo->prepare("SELECT id FROM homeowner_vehicles WHERE plate_number = ?");
    $stmt->execute([$plateNumber]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'This plate number is already registered']);
        exit();
    }
    
    // Handle vehicle image upload
    $vehicleImg = null;
    if (isset($_FILES['vehicle_img']) && $_FILES['vehicle_img']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/vehicles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $ext = strtolower(pathinfo($_FILES['vehicle_img']['name'], PATHINFO_EXTENSION));
        $filename = 'vehicle_' . uniqid() . '.' . $ext;
        $destination = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['vehicle_img']['tmp_name'], $destination)) {
            $vehicleImg = 'vehicles/' . $filename;
        }
    }
    
    // If setting as primary, unset other primary vehicles
    if ($isPrimary) {
        $pdo->prepare("UPDATE homeowner_vehicles SET is_primary = FALSE WHERE homeowner_id = ?")
            ->execute([$_SESSION['homeowner_id']]);
    }
    
    // Insert new vehicle
    $stmt = $pdo->prepare("
        INSERT INTO homeowner_vehicles (homeowner_id, vehicle_type, color, plate_number, vehicle_img, is_primary)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $_SESSION['homeowner_id'],
        $vehicleType,
        $color,
        $plateNumber,
        $vehicleImg,
        $isPrimary
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Vehicle added successfully',
        'vehicle_id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    error_log("Add vehicle error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to add vehicle: ' . $e->getMessage()
    ]);
}
