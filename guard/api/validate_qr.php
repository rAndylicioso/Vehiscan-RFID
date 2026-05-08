<?php
date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';

// Check role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['guard', 'admin', 'super_admin'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$jsonInput = json_decode(file_get_contents('php://input'), true) ?? [];
$token = InputSanitizer::post('token', 'string') ?: ($jsonInput['token'] ?? '');
$direction = InputSanitizer::post('direction', 'string') ?: ($jsonInput['direction'] ?? 'in');
$csrf = InputSanitizer::post('csrf_token', 'string') ?: ($jsonInput['csrf_token'] ?? '');

if (!InputSanitizer::validateCsrf($csrf)) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Invalid CSRF token']));
}

if (empty($token)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'QR Token is required']));
}

try {
    // Look up the pass by token
    $stmt = $pdo->prepare("
        SELECT vp.*, h.name as homeowner_name 
        FROM visitor_passes vp 
        LEFT JOIN homeowners h ON vp.homeowner_id = h.id 
        WHERE vp.qr_token = ?
    ");
    $stmt->execute([$token]);
    $pass = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pass) {
        exit(json_encode(['success' => false, 'message' => 'Invalid QR Code']));
    }

    $now = date('Y-m-d H:i:s');
    
    // Check status and validity period
    if ($pass['status'] === 'rejected' || $pass['status'] === 'cancelled') {
        exit(json_encode(['success' => false, 'message' => 'This pass has been ' . $pass['status']]));
    }
    
    if ($pass['status'] === 'pending') {
        exit(json_encode(['success' => false, 'message' => 'This pass is still pending approval']));
    }

    if ($now < $pass['valid_from']) {
        exit(json_encode(['success' => false, 'message' => 'This pass is not yet valid. Starts at ' . $pass['valid_from']]));
    }

    if ($now > $pass['valid_until']) {
        exit(json_encode(['success' => false, 'message' => 'This pass has expired on ' . $pass['valid_until']]));
    }

    // Pass is valid!
    // Update scan count and status
    $newStatus = ($pass['status'] === 'approved') ? 'active' : $pass['status'];
    $pdo->prepare("UPDATE visitor_passes SET scan_count = scan_count + 1, last_scanned_at = NOW(), status = ? WHERE id = ?")
        ->execute([$newStatus, $pass['id']]);

    // Log the access
    $stmt = $pdo->prepare("
        INSERT INTO rfid_scan_log (rfid_uid, plate_number, access_type, direction, status, notes) 
        VALUES (?, ?, 'visitor', ?, 'granted', ?)
    ");
    $notes = "QR Scan Access: " . $pass['visitor_name'] . " (" . $pass['purpose'] . ")";
    $stmt->execute(['QR_SCAN_' . $pass['id'], $pass['visitor_plate'] ?: 'NO_PLATE', $direction, $notes]);

    echo json_encode([
        'success' => true,
        'message' => 'Access Granted: ' . $pass['visitor_name'],
        'visitor_name' => $pass['visitor_name'],
        'homeowner' => $pass['homeowner_name'],
        'purpose' => $pass['purpose']
    ]);
} catch (Exception $e) {
    error_log('[QR_VALIDATE] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
