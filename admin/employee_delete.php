<?php
/**
 * Employee Delete
 * Delete employee endpoint (AJAX)
 */
require_once __DIR__ . '/../includes/session_admin_unified.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
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

// CSRF validation
$csrf = $_SESSION['csrf_token'] ?? '';
$postedCsrf = $input['csrf_token'] ?? '';
if (!$csrf || !hash_equals($csrf, (string)$postedCsrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

$employeeId = $input['id'] ?? null;

if (!$employeeId) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit();
}

try {
    // Fetch employee data before deletion
    $stmt = $pdo->prepare("SELECT username, email, CONCAT_WS(' ', first_name, last_name) as full_name, role FROM users WHERE id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
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
        echo json_encode(['success' => false, 'message' => 'Failed to delete employee']);
    }
} catch (PDOException $e) {
    error_log('[EMPLOYEE_DELETE] DB error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again.']);
}
