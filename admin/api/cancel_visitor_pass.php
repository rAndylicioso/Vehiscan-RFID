<?php
require_once __DIR__ . '/../../includes/security_headers.php';
require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../includes/request_method_helper.php';
requireRequestMethod('POST');
if (!in_array($_SESSION['role'] ?? '', ['super_admin', 'admin'], true)) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';
require_once __DIR__ . '/../../includes/cache_invalidator.php';
require_once __DIR__ . '/../../includes/audit_logger.php';
header('Content-Type: application/json');

AuditLogger::init($pdo);

// Validate CSRF token using InputSanitizer
$posted = InputSanitizer::post('csrf_token', 'string');
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
    $stmt = $pdo->prepare("UPDATE visitor_passes SET status = 'cancelled' WHERE id = ? AND status IN ('pending', 'active')");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Pass not found or already processed']);
        exit;
    }

    try {
        AuditLogger::logDataChange('visitor_pass_cancelled', 'visitor_passes', (int)$id, [
            'status' => 'pending/active',
        ], [
            'status' => 'cancelled',
        ]);
    } catch (Exception $auditError) {
        error_log('Cancel visitor pass audit error: ' . $auditError->getMessage());
    }

    // Invalidate cache after cancellation
    CacheInvalidator::invalidatePasses();
    CacheInvalidator::invalidateDashboard();

    logAudit('VISITOR_PASS_CANCELLED', 'visitor_passes', $id, "Cancelled visitor pass #$id");

    echo json_encode(['success' => true, 'message' => 'Visitor pass cancelled']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
