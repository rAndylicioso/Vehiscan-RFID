<?php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';

header('Content-Type: application/json');

try {
    // CSRF validation using InputSanitizer
    $csrfToken = InputSanitizer::post('csrf', 'string');
    if (!InputSanitizer::validateCsrf($csrfToken)) {
        throw new Exception('Invalid CSRF token');
    }
    
    $id = InputSanitizer::post('id', 'int');
    
    if (!$id) {
        throw new Exception('Employee ID is required');
    }
    
    // Get employee details before deletion
    $stmt = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        throw new Exception('Employee not found');
    }
    
    // Only super admin can delete super_admin accounts
    if ($employee['role'] === 'super_admin' && $_SESSION['role'] !== 'super_admin') {
        throw new Exception('Only Super Admin can delete Super Admin accounts');
    }
    
    // Prevent self-deletion
    if ($id == ($_SESSION['user_id'] ?? $_SESSION['admin_id'])) {
        throw new Exception('You cannot delete your own account');
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
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
