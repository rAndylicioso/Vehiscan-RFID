<?php
require_once __DIR__ . '/../../includes/session_homeowner.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/security_headers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// CSRF token validation
$csrfToken = $_POST['csrf_token'] ?? '';
if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request token']);
    exit();
}

$homeownerId = (int) $_SESSION['homeowner_id'];
$requestText = trim($_POST['request_text'] ?? '');

if (empty($requestText)) {
    echo json_encode(['success' => false, 'message' => 'Please describe the changes you would like to request.']);
    exit();
}

if (mb_strlen($requestText) > 2000) {
    echo json_encode(['success' => false, 'message' => 'Request description is too long (maximum 2000 characters).']);
    exit();
}

try {
    // Check if homeowner already has an open (pending or acknowledged) request
    $stmt = $pdo->prepare("
        SELECT id FROM profile_update_requests
        WHERE homeowner_id = ? AND status IN ('pending', 'acknowledged')
        LIMIT 1
    ");
    $stmt->execute([$homeownerId]);
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message'  => 'You already have an open request. Please wait for it to be reviewed before submitting a new one.'
        ]);
        exit();
    }

    // Insert the new request
    $stmt = $pdo->prepare("
        INSERT INTO profile_update_requests (homeowner_id, request_text, status, created_at)
        VALUES (?, ?, 'pending', NOW())
    ");
    $stmt->execute([$homeownerId, $requestText]);

    echo json_encode([
        'success' => true,
        'message' => 'Your request has been submitted. An administrator will review it and contact you if needed.'
    ]);

} catch (PDOException $e) {
    error_log('[ProfileRequest] DB error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again.']);
}
