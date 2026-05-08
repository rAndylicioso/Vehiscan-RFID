<?php
/**
 * ============================================================================
 * TODO [REVIEW]: STALE ENDPOINT — DELETE UI REMOVED
 * ============================================================================
 * The "Delete" button was intentionally removed from the employee list UI
 * (admin/fetch/fetch_employees.php). This backend endpoint still accepts
 * POST requests and performs hard deletes on the users table.
 *
 * Decide whether to:
 *   1. DELETE this file entirely (recommended if delete is permanently disabled)
 *   2. DISABLE by returning 403 early (keeps audit trail)
 *   3. RE-ENABLE by restoring the Delete button in the UI
 *
 * Role hierarchy: super_admin > admin > guard > homeowner
 * Currently restricted to: super_admin only
 * Last reviewed: 2026-04-25
 * ============================================================================
 */
require_once __DIR__ . '/../../includes/security_headers.php';
require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';
require_once __DIR__ . '/../../includes/request_method_helper.php';

header('Content-Type: application/json');

requireRequestMethod('POST');

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'], true)) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if (($_SESSION['role'] ?? '') !== 'super_admin') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Only Super Admin can delete employee accounts']));
}

try {
    // CSRF validation using InputSanitizer
    $csrfToken = InputSanitizer::post('csrf_token', 'string');
    if (!InputSanitizer::validateCsrf($csrfToken)) {
        throw new Exception('Invalid CSRF token', 403);
    }
    
    $id = InputSanitizer::post('id', 'int');
    
    if (!$id) {
        throw new Exception('Employee ID is required', 400);
    }

    $confirmation = strtoupper(trim((string)InputSanitizer::post('confirmation', 'string')));
    if ($confirmation !== 'DELETE') {
        throw new Exception('Confirmation text is required', 400);
    }
    
    // Get employee details before deletion
    $stmt = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        throw new Exception('Employee not found', 404);
    }
    
    // Only super admin can delete super_admin accounts
    if ($employee['role'] === 'super_admin' && $_SESSION['role'] !== 'super_admin') {
        throw new Exception('Only Super Admin can delete Super Admin accounts', 403);
    }
    
    // Prevent self-deletion
    if ($id == ($_SESSION['user_id'] ?? $_SESSION['admin_id'])) {
        throw new Exception('You cannot delete your own account', 400);
    }
    
    // Delete employee
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    
    // Audit log
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details) VALUES (?, 'employee_delete', ?)");
    $stmt->execute([
        $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null,
        json_encode(['employee_id' => $id, 'username' => $employee['username'], 'role' => $employee['role']])
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Employee deleted successfully']);
    
} catch (PDOException $e) {
    error_log('Employee delete DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again later.']);
} catch (Exception $e) {
    $code = (int)$e->getCode();
    if ($code < 400 || $code > 599) {
        $code = 400;
    }
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
