<?php
/**
 * Check for pending approvals count
 * Returns count of homeowner accounts pending approval
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/session_admin_unified.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';

try {
    // Count pending homeowner approvals
    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM homeowners 
        WHERE account_status = 'pending'
    ");
    $pendingCount = (int)$stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'pending_count' => $pendingCount
    ]);
    
} catch (Exception $e) {
    error_log('Check pending approvals error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to check approvals. Please try again later.'
    ]);
}
