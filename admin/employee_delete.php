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
 *
 * Employee Delete
 * Delete employee endpoint (AJAX)
 */
require_once __DIR__ . '/../includes/session_admin_unified.php';

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (($_SESSION['role'] ?? '') !== 'super_admin') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Only Super Admin can delete employee accounts'
    ]);
    exit();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/audit_logger.php';

// Initialize audit logger
try {
    AuditLogger::init($pdo);
} catch (Exception $e) {
    // Audit logger not available
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request payload']);
    exit();
}

// CSRF validation
$csrf = $_SESSION['csrf_token'] ?? '';
$postedCsrf = $input['csrf_token'] ?? '';
if (!$csrf || !hash_equals($csrf, (string)$postedCsrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

$employeeId = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if (!$employeeId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit();
}

$confirmation = strtoupper(trim((string)($input['confirmation'] ?? '')));
if ($confirmation !== 'DELETE') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Confirmation text is required']);
    exit();
}

try {
    // Fetch employee data before deletion
    $stmt = $pdo->prepare("SELECT username, email, CONCAT_WS(' ', first_name, last_name) as full_name, role FROM users WHERE id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit();
    }

    if (($employee['role'] ?? '') === 'super_admin' && ($_SESSION['role'] ?? '') !== 'super_admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Only Super Admin can delete Super Admin accounts']);
        exit();
    }

    $currentUserId = (int)($_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 0);
    if ($currentUserId > 0 && $employeeId === $currentUserId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
        exit();
    }
    
    // Delete employee
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $success = $stmt->execute([$employeeId]);
    
    if ($success) {
        // Log to audit system
        try {
            AuditLogger::logEmployee('employee_deleted', $employeeId, [
                'old_values' => $employee
            ]);
        } catch (Exception $e) {
            // Audit logger not available
        }
        
        echo json_encode(['success' => true, 'message' => 'Employee deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete employee']);
    }
} catch (PDOException $e) {
    error_log('[EMPLOYEE_DELETE] DB error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again.']);
}
