<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

$posted_csrf = $_POST['csrf'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$posted_csrf)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$plate = trim($_POST['plate_number'] ?? '');
$vehicle_type = trim($_POST['vehicle_type'] ?? '');
$color = trim($_POST['color'] ?? '');
$address = trim($_POST['address'] ?? '');

if ($name === '' || $contact === '' || $plate === '' || $vehicle_type === '') {
    echo json_encode(['success'=>false,'message'=>'Required fields missing']);
    exit;
}

$plate_normalized = strtoupper(preg_replace('/\s+/', '', $plate));

require_once __DIR__ . '/../includes/upload_helper.php';

$ownersDir = __DIR__ . '/../uploads/homeowners/';
$vehiclesDir = __DIR__ . '/../uploads/vehicles/';

// Create directories if they don't exist
if (!is_dir($ownersDir)) mkdir($ownersDir, 0755, true);
if (!is_dir($vehiclesDir)) mkdir($vehiclesDir, 0755, true);

// Handle owner image upload
$owner_img = null;
if (isset($_FILES['owner_img'])) {
    $result = validateAndUploadImage($_FILES['owner_img'], $ownersDir, 'owner_');
    if ($result['success']) {
        $owner_img = 'homeowners/' . $result['path'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Owner image: ' . $result['error']]);
        exit;
    }
}

// Handle vehicle image upload
$car_img = null;
if (isset($_FILES['car_img']) && $_FILES['car_img']['error'] !== UPLOAD_ERR_NO_FILE) {
    $result = validateAndUploadImage($_FILES['car_img'], $vehiclesDir, 'vehicle_');
    if ($result['success']) {
        $car_img = 'vehicles/' . $result['path'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Vehicle image: ' . $result['error']]);
        exit;
    }
}

try {
    $stmt = $pdo->prepare("SELECT id FROM homeowners WHERE plate_number = ? LIMIT 1");
    $stmt->execute([$plate_normalized]);
    if ($stmt->fetch()) {
        echo json_encode(['success'=>false,'message'=>'Plate number already registered']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO homeowners (name, contact, plate_number, vehicle_type, color, address, owner_img, car_img, created_at)
                           VALUES (?,?,?,?,?,?,?, ?, NOW())");
    $stmt->execute([$name, $contact, $plate_normalized, $vehicle_type, $color, $address, $owner_img, $car_img]);

    echo json_encode(['success'=>true,'message'=>'Registered successfully']);
} catch (PDOException $e) {
    error_log('Registration error: ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error']);
}
