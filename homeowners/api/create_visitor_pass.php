<?php
require_once __DIR__ . '/../../includes/security_headers.php';

// Configure session for local network testing
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', 0);

// Use the same session name as login
session_name('vehiscan_session');
session_start();

header('Content-Type: application/json');

// Check if homeowner is logged in
if (!isset($_SESSION['homeowner_id']) || $_SESSION['role'] !== 'homeowner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/input_validator.php';
require_once __DIR__ . '/../../includes/rate_limiter.php';
require_once __DIR__ . '/../../includes/cache_invalidator.php';

// Rate limiting check (10 visitor passes per hour per homeowner)
$homeownerId = $_SESSION['homeowner_id'];
$rateLimiter = new RateLimiter($pdo);
$rateCheck = $rateLimiter->check("homeowner_$homeownerId", 'visitor_pass', 10, 60);

if (!$rateCheck['allowed']) {
    $minutesLeft = ceil($rateCheck['reset_time'] / 60);
    echo json_encode([
        'success' => false,
        'message' => "Too many visitor pass requests. Please try again in {$minutesLeft} minutes."
    ]);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate CSRF token
if (!isset($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_token']) {
    $rateLimiter->recordAttempt("homeowner_$homeownerId", 'visitor_pass', ['error' => 'csrf_token']);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

// Validate input
$visitor_name = trim($data['visitor_name'] ?? '');
$purpose = trim($data['purpose'] ?? '');
$visitor_plate = strtoupper(trim($data['visitor_plate'] ?? ''));
$valid_from = $data['valid_from'] ?? '';
$valid_until = $data['valid_until'] ?? '';

// Validate required fields
if (empty($visitor_name)) {
    echo json_encode(['success' => false, 'message' => 'Visitor name is required']);
    exit();
}

if (strlen($visitor_name) < 2 || strlen($visitor_name) > 100) {
    echo json_encode(['success' => false, 'message' => 'Visitor name must be 2-100 characters']);
    exit();
}

if (empty($purpose)) {
    echo json_encode(['success' => false, 'message' => 'Purpose is required']);
    exit();
}

if (strlen($purpose) < 3 || strlen($purpose) > 500) {
    echo json_encode(['success' => false, 'message' => 'Purpose must be 3-500 characters']);
    exit();
}

// Validate plate number if provided
if (!empty($visitor_plate)) {
    $plateValidation = InputValidator::validatePlateNumber($visitor_plate);
    if (!$plateValidation['valid']) {
        echo json_encode(['success' => false, 'message' => $plateValidation['message']]);
        exit();
    }
    $visitor_plate = $plateValidation['formatted'];
}

if (empty($valid_from) || empty($valid_until)) {
    echo json_encode(['success' => false, 'message' => 'Valid from and until dates are required']);
    exit();
}

// Validate date range
$from = strtotime($valid_from);
$until = strtotime($valid_until);
$now = time();
$fiveMinutesAgo = $now - (5 * 60); // Allow 5-minute grace period

if ($from === false || $until === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Please use the date picker.']);
    exit();
}

if ($until <= $from) {
    echo json_encode(['success' => false, 'message' => 'Valid until date must be after valid from date']);
    exit();
}

// Allow dates within last 5 minutes (form filling time)
if ($from < $fiveMinutesAgo) {
    echo json_encode(['success' => false, 'message' => 'Start date cannot be more than 5 minutes in the past']);
    exit();
}

// Check minimum duration (30 minutes)
$durationMinutes = ($until - $from) / 60;
if ($durationMinutes < 30) {
    echo json_encode(['success' => false, 'message' => 'Visit duration must be at least 30 minutes']);
    exit();
}

// Check maximum duration (7 days)
if ($durationMinutes > 10080) { // 7 days
    echo json_encode(['success' => false, 'message' => 'Visit duration cannot exceed 7 days']);
    exit();
}

try {
    // Generate QR token
    $qr_token = bin2hex(random_bytes(16));

    // Insert visitor pass
    $stmt = $pdo->prepare("
        INSERT INTO visitor_passes 
        (homeowner_id, visitor_name, purpose, visitor_plate, valid_from, valid_until, qr_token, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    $stmt->execute([
        $_SESSION['homeowner_id'],
        $visitor_name,
        $purpose,
        $visitor_plate ?: null,
        date('Y-m-d H:i:s', $from),
        date('Y-m-d H:i:s', $until),
        $qr_token
    ]);

    $pass_id = $pdo->lastInsertId();

    // Reset rate limit on successful creation
    $rateLimiter->reset("homeowner_$homeownerId", 'visitor_pass');

    echo json_encode([
        'success' => true,
        'message' => 'Visitor pass created successfully',
        'pass_id' => $pass_id
    ]);

} catch (PDOException $e) {
    error_log("Create visitor pass error: " . $e->getMessage());

    // Record failed attempt
    $rateLimiter->recordAttempt("homeowner_$homeownerId", 'visitor_pass', [
        'error' => 'database_error'
    ]);

    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
