<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/security_headers.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';
require_once __DIR__ . '/../../includes/session_admin_unified.php';

header('Content-Type: application/json');

// Check if user is admin or super admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $csrfToken = InputSanitizer::post('csrf_token', 'string');
    if (!InputSanitizer::validateCsrf($csrfToken)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit();
    }
    
    // Sanitize inputs
    $userId = InputSanitizer::post('user_id', 'int');
    $action = InputSanitizer::post('action', 'string');
    $reason = InputSanitizer::post('reason', 'string');
    
    if (!$userId || !$action) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    // Whitelist validation for action
    if (!in_array($action, ['approve', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
    }
    
    try {
        $pdo->beginTransaction();
        
        // First, check if this is a homeowner or regular user
        $checkStmt = $pdo->prepare("SELECT id FROM homeowners WHERE id = ?");
        $checkStmt->execute([$userId]);
        $isHomeowner = $checkStmt->rowCount() > 0;
        
        if ($action === 'approve') {
            if ($isHomeowner) {
                // Approve homeowner account
                $stmt = $pdo->prepare("
                    UPDATE homeowners 
                    SET account_status = 'approved'
                    WHERE id = ?
                ");
                $stmt->execute([$userId]);
                
                // Activate homeowner auth
                $stmt = $pdo->prepare("
                    UPDATE homeowner_auth 
                    SET is_active = 1
                    WHERE homeowner_id = ?
                ");
                $stmt->execute([$userId]);
                
                // Get homeowner info for notification
                $stmt = $pdo->prepare("SELECT email, first_name, last_name FROM homeowners WHERE id = ?");
                $stmt->execute([$userId]);
                $homeowner = $stmt->fetch();
                
                // Log the approval
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO account_approval_log (user_id, user_type, action, approved_by, reason)
                        VALUES (?, 'homeowner', 'approved', ?, ?)
                    ");
                    $stmt->execute([$userId, $_SESSION['user_id'], $reason]);
                } catch (PDOException $e) {
                    // Table may not exist, continue anyway
                    error_log('Could not log approval: ' . $e->getMessage());
                }
                
                $message = 'Homeowner account approved successfully';
                
                // TODO: Send email notification to homeowner
                error_log("Homeowner approved: {$homeowner['email']} - {$homeowner['first_name']} {$homeowner['last_name']}");
                
            } else {
                // Approve regular user account
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET account_status = 'approved',
                        approved_by = ?,
                        approved_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $userId]);
                
                // Log the approval
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO account_approval_log (user_id, user_type, action, approved_by, reason)
                        VALUES (?, 'user', 'approved', ?, ?)
                    ");
                    $stmt->execute([$userId, $_SESSION['user_id'], $reason]);
                } catch (PDOException $e) {
                    error_log('Could not log approval: ' . $e->getMessage());
                }
                
                $message = 'Account approved successfully';
            }
            
        } elseif ($action === 'reject') {
            if ($isHomeowner) {
                // Reject homeowner account
                $stmt = $pdo->prepare("
                    UPDATE homeowners 
                    SET account_status = 'rejected'
                    WHERE id = ?
                ");
                $stmt->execute([$userId]);
                
                // Deactivate homeowner auth
                $stmt = $pdo->prepare("
                    UPDATE homeowner_auth 
                    SET is_active = 0
                    WHERE homeowner_id = ?
                ");
                $stmt->execute([$userId]);
                
                // Get homeowner info
                $stmt = $pdo->prepare("SELECT email, first_name, last_name FROM homeowners WHERE id = ?");
                $stmt->execute([$userId]);
                $homeowner = $stmt->fetch();
                
                // Log the rejection
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO account_approval_log (user_id, user_type, action, approved_by, reason)
                        VALUES (?, 'homeowner', 'rejected', ?, ?)
                    ");
                    $stmt->execute([$userId, $_SESSION['user_id'], $reason]);
                } catch (PDOException $e) {
                    error_log('Could not log rejection: ' . $e->getMessage());
                }
                
                $message = 'Homeowner account rejected';
                
                // TODO: Send email notification to homeowner
                error_log("Homeowner rejected: {$homeowner['email']} - Reason: $reason");
                
            } else {
                // Reject regular user
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET account_status = 'rejected',
                        approved_by = ?,
                        approved_at = NOW(),
                        rejection_reason = ?
                    WHERE id = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $reason, $userId]);
                
                // Log the rejection
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO account_approval_log (user_id, user_type, action, approved_by, reason)
                        VALUES (?, 'user', 'rejected', ?, ?)
                    ");
                    $stmt->execute([$userId, $_SESSION['user_id'], $reason]);
                } catch (PDOException $e) {
                    error_log('Could not log rejection: ' . $e->getMessage());
                }
                
                $message = 'Account rejected';
            }
            
        } else {
            throw new Exception('Invalid action');
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => $message]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
