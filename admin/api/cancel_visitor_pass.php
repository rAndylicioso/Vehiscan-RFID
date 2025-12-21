<?php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
if (!in_array($_SESSION['role'] ?? '', ['super_admin', 'admin']))
    exit(json_encode(['success' => false]));
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';
require_once __DIR__ . '/../../includes/cache_invalidator.php';
header('Content-Type: application/json');

// Validate CSRF token using InputSanitizer
$posted = InputSanitizer::post('csrf', 'string');
if (!InputSanitizer::validateCsrf($posted)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$id = InputSanitizer::post('id', 'int', 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE visitor_passes SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$id]);

    // Invalidate cache after cancellation
    CacheInvalidator::invalidatePasses();
    CacheInvalidator::invalidateDashboard();

    logAudit('VISITOR_PASS_CANCELLED', 'visitor_passes', $id, "Cancelled visitor pass #$id");

    echo json_encode(['success' => true, 'message' => 'Visitor pass cancelled']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
