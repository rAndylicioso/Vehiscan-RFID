<?php
ob_start();
require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';
ob_end_clean();

header('Content-Type: application/json');

// Authorization check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

try {
    // CSRF validation using InputSanitizer
    $postCsrf = InputSanitizer::post('csrf', 'string');
    
    if (!InputSanitizer::validateCsrf($postCsrf)) {
        throw new Exception('Invalid CSRF token');
    }
    
    // Sanitize all inputs
    $id = InputSanitizer::post('id', 'int');
    $username = InputSanitizer::post('username', 'string');
    $password = InputSanitizer::post('password', 'string');
    $confirmPassword = InputSanitizer::post('confirm_password', 'string');
    $newPassword = InputSanitizer::post('new_password', 'string');
    $resetPassword = isset($_POST['reset_password']);
    $role = InputSanitizer::post('role', 'string');
    
    // Validation
    if (empty($username)) {
        throw new Exception('Username is required');
    }
    
    if (strlen($username) < 3) {
        throw new Exception('Username must be at least 3 characters');
    }
    
    if (empty($role)) {
        throw new Exception('Role is required');
    }
    
    if (!in_array($role, ['admin', 'guard', 'owner'])) {
        throw new Exception('Invalid role');
    }
    
    if ($id) {
        // Update existing employee
        $passwordUpdate = false;
        $hashedPassword = null;
        
        // Check if password reset is requested (edit mode)
        if ($resetPassword && !empty($newPassword)) {
            if (strlen($newPassword) < 8) {
                throw new Exception('Password must be at least 8 characters');
            }
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $passwordUpdate = true;
        }
        
        // Prevent self-demotion
        if ($_SESSION['user_id'] == $id && $_SESSION['role'] !== $role) {
            throw new Exception('You cannot change your own role');
        }
        
        if ($passwordUpdate) {
            $stmt = $pdo->prepare("UPDATE users SET role = ?, password = ? WHERE id = ?");
            $success = $stmt->execute([$role, $hashedPassword, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $success = $stmt->execute([$role, $id]);
        }
        
        // Audit log (optional)
        try {
            logAudit('employee_update', 'users', $id, json_encode(['username' => $username, 'role' => $role]));
        } catch (Exception $e) {
            // Silently continue if audit logging fails
        }
        
        echo json_encode(['success' => true, 'message' => 'Employee updated successfully']);
        
    } else {
        // Create new employee
        if (empty($password)) {
            throw new Exception('Password is required for new employee');
        }
        
        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }
        
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters');
        }
        
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            throw new Exception('Username already exists');
        }
        
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $role]);
        
        $newId = $pdo->lastInsertId();
        
        // Audit log (optional)
        try {
            logAudit('employee_create', 'users', $newId, json_encode(['username' => $username, 'role' => $role]));
        } catch (Exception $e) {
            // Silently continue if audit logging fails
        }
        
        echo json_encode(['success' => true, 'message' => 'Employee created successfully']);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
