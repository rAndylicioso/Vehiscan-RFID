<?php
require_once __DIR__ . '/../includes/session_admin_unified.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/input_sanitizer.php';
require_once __DIR__ . '/../includes/input_validator.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// CSRF validation
$csrfToken = InputSanitizer::post('csrf_token', 'string');
if (!InputSanitizer::validateCsrf($csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Sanitize inputs
$id = InputSanitizer::post('id', 'int', 0);
$name = InputSanitizer::post('name', 'string');
$contact = InputSanitizer::post('contact', 'string');
$plate = InputSanitizer::post('plate_number', 'string');
$vehicle = InputSanitizer::post('vehicle_type', 'string');

// Validate inputs
if (empty($name) || strlen($name) < 2 || strlen($name) > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name must be 2-100 characters']);
    exit;
}

if (!empty($plate)) {
    $plateCheck = InputValidator::validatePlateNumber($plate);
    if (!$plateCheck['valid']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $plateCheck['message']]);
        exit;
    }
    $plate = $plateCheck['formatted'];
}

if (!empty($contact)) {
    $phoneCheck = InputValidator::validatePhoneNumber($contact);
    if (!$phoneCheck['valid']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $phoneCheck['message']]);
        exit;
    }
    $contact = $phoneCheck['formatted'];
}

try {
    $stmt = $pdo->prepare("UPDATE homeowners SET name=?, contact_number=?, plate_number=?, vehicle_type=? WHERE id=?");
    $stmt->execute([$name, $contact, $plate, $vehicle, $id]);
    echo json_encode(['success' => true, 'message' => 'Record updated']);
} catch (Exception $e) {
    error_log('[HOMEOWNER_SAVE] DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred']);
}
