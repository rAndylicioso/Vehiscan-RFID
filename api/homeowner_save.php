<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('vehiscan_admin');
    session_start();
}
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/input_sanitizer.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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

$stmt = $pdo->prepare("UPDATE homeowners SET name=?, contact=?, plate_number=?, vehicle_type=? WHERE id=?");
$stmt->execute([$name, $contact, $plate, $vehicle, $id]);

echo json_encode(['success' => true, 'message' => 'Record updated']);
