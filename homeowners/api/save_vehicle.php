<?php
require_once __DIR__ . '/../../includes/session_homeowner.php';
require_once __DIR__ . '/../../db.php';
header('Content-Type: application/json');

$homeownerId = $_SESSION['homeowner_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF validation
    $csrf = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }

    $plateNumber = trim($_POST['plate_number'] ?? '');
    $vehicleType = trim($_POST['vehicle_type'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $brand = trim($_POST['brand'] ?? null);
    $model = trim($_POST['model'] ?? null);
    $year = !empty($_POST['year']) ? (int)$_POST['year'] : null;
    $vehicleId = !empty($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null;
    
    if (empty($plateNumber) || empty($vehicleType) || empty($color)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    try {
        if ($vehicleId) {
            // Update existing vehicle
            $stmt = $pdo->prepare("
                UPDATE vehicles 
                SET plate_number = ?, vehicle_type = ?, color = ?, brand = ?, model = ?, year = ?
                WHERE id = ? AND homeowner_id = ?
            ");
            $stmt->execute([$plateNumber, $vehicleType, $color, $brand, $model, $year, $vehicleId, $homeownerId]);
            $message = 'Vehicle updated successfully';
        } else {
            // Check if this is the first vehicle (should be primary)
            $count = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE homeowner_id = ? AND is_active = TRUE");
            $count->execute([$homeownerId]);
            $isPrimary = ($count->fetchColumn() == 0);
            
            // Insert new vehicle
            $stmt = $pdo->prepare("
                INSERT INTO vehicles (homeowner_id, plate_number, vehicle_type, color, brand, model, year, is_primary)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$homeownerId, $plateNumber, $vehicleType, $color, $brand, $model, $year, $isPrimary]);
            $message = 'Vehicle added successfully';
        }
        
        echo json_encode(['success' => true, 'message' => $message]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again later.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
