<?php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
if (!in_array($_SESSION['role'] ?? '', ['super_admin', 'admin'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';
require_once __DIR__ . '/../../includes/cache_invalidator.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

// CSRF validation
$csrf = $data['csrf_token'] ?? '';
if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

$pass_id = isset($data['pass_id']) ? InputSanitizer::sanitizeInt($data['pass_id']) : 0;
$reason = isset($data['reason']) ? InputSanitizer::sanitizeString($data['reason']) : '';

if (!$pass_id || !$reason) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        UPDATE visitor_passes 
        SET status = 'rejected', 
            rejection_reason = ?,
            approved_by = ?, 
            approved_at = NOW()
        WHERE id = ? AND status = 'pending'
    ");

    $stmt->execute([$reason, $_SESSION['user_id'] ?? null, $pass_id]);

    if ($stmt->rowCount() > 0) {
        // Invalidate caches
        CacheInvalidator::invalidatePasses();
        CacheInvalidator::invalidateDashboard();

        if (function_exists('logAudit')) {
            logAudit('VISITOR_PASS_REJECTED', 'visitor_passes', $pass_id, "Rejected visitor pass #$pass_id: $reason");
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Pass not found or already processed']);
    }

} catch (PDOException $e) {
    error_log("Reject pass error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
