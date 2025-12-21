<?php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
if (!in_array($_SESSION['role'] ?? '', ['super_admin', 'admin'])) exit(json_encode(['success' => false]));
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';
require_once __DIR__ . '/qr_helper.php';
header('Content-Type: application/json');

// Validate CSRF token using InputSanitizer
$posted = InputSanitizer::post('csrf', 'string');
if (!InputSanitizer::validateCsrf($posted)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Sanitize all inputs
$homeowner_id = InputSanitizer::post('homeowner_id', 'int', 0);
$visitor_name = InputSanitizer::post('visitor_name', 'string');
$visitor_plate = strtoupper(InputSanitizer::post('visitor_plate', 'string'));
$purpose = InputSanitizer::post('purpose', 'string');
$valid_from = InputSanitizer::post('valid_from', 'string');
$valid_until = InputSanitizer::post('valid_until', 'string');
$is_recurring = InputSanitizer::post('is_recurring', 'int', 0);

if (!$homeowner_id || !$visitor_name || !$visitor_plate || !$valid_from || !$valid_until) {
    echo json_encode(['success' => false, 'message' => 'All fields required']);
    exit;
}

try {
    // Generate unique token for QR code
    $qrToken = generateSecureToken();
    
    $stmt = $pdo->prepare("
        INSERT INTO visitor_passes 
        (homeowner_id, visitor_name, visitor_plate, purpose, valid_from, valid_until, is_recurring, qr_token, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$homeowner_id, $visitor_name, $visitor_plate, $purpose, $valid_from, $valid_until, $is_recurring, $qrToken]);
    
    $passId = $pdo->lastInsertId();
    
    // Generate QR code
    $qrCode = generateVisitorPassQR($passId, $qrToken, $pdo);
    
    if ($qrCode) {
        // Update pass with QR code
        $stmt = $pdo->prepare("UPDATE visitor_passes SET qr_code = ? WHERE id = ?");
        $stmt->execute([$qrCode, $passId]);
    }
    
    logAudit('VISITOR_PASS_CREATED', 'visitor_passes', $passId, "Created pass for $visitor_name ($visitor_plate)");
    
    echo json_encode(['success' => true, 'message' => 'Visitor pass created successfully']);
} catch (PDOException $e) {
    error_log("Create pass error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
