<?php
require_once __DIR__ . '/../../includes/security_headers.php';
require_once __DIR__ . '/../../includes/request_method_helper.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';
header('Content-Type: application/json');

requireRequestMethod('POST');

require_once __DIR__ . '/../../includes/session_homeowner.php';
require_once __DIR__ . '/../../db.php';

$homeownerId = $_SESSION['homeowner_id'];

$rawInput = file_get_contents('php://input');
$data = [];

if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $data = $decoded;
    }
}

if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

$vehicleId = isset($data['vehicle_id']) ? (int)$data['vehicle_id'] : 0;

// Validate CSRF token
$csrfToken = $data['csrf_token'] ?? '';
if (!InputSanitizer::validateCsrf((string)$csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

if ($vehicleId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vehicle ID required']);
    exit();
}

try {
    $vehicleColumns = [];
    try {
        $vehicleColumns = $pdo->query("SHOW COLUMNS FROM vehicles")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $vehicleColumns = [];
    }
    $vehicleIdColumn = in_array('id', $vehicleColumns, true) ? 'id' : 'vehicle_id';
    $hasVehicleImageColumn = in_array('vehicle_img', $vehicleColumns, true);

    $homeownerColumns = [];
    try {
        $homeownerColumns = $pdo->query("SHOW COLUMNS FROM homeowners")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $homeownerColumns = [];
    }
    $hasHomeownerCarImageColumn = in_array('car_img', $homeownerColumns, true);

    $pdo->beginTransaction();

    // First, unset all other primary vehicles for this homeowner
    $pdo->prepare("UPDATE vehicles SET is_primary = FALSE WHERE homeowner_id = ? AND is_active = TRUE")->execute([$homeownerId]);
    
    // Set this vehicle as primary
    $stmt = $pdo->prepare("UPDATE vehicles SET is_primary = TRUE WHERE {$vehicleIdColumn} = ? AND homeowner_id = ? AND is_active = TRUE");
    $stmt->execute([$vehicleId, $homeownerId]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Vehicle not found or inactive']);
        exit();
    }

    if ($hasVehicleImageColumn && $hasHomeownerCarImageColumn) {
        $stmt = $pdo->prepare("SELECT vehicle_img FROM vehicles WHERE {$vehicleIdColumn} = ? AND homeowner_id = ? LIMIT 1");
        $stmt->execute([$vehicleId, $homeownerId]);
        $vehicleImg = $stmt->fetchColumn();
        if (!empty($vehicleImg)) {
            $pdo->prepare("UPDATE homeowners SET car_img = ? WHERE id = ?")->execute([$vehicleImg, $homeownerId]);
        } else {
            $pdo->prepare("UPDATE homeowners SET car_img = NULL WHERE id = ?")->execute([$homeownerId]);
        }
    }

    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Primary vehicle updated']);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again later.']);
}
