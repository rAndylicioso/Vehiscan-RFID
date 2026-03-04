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
    $requestId = InputSanitizer::post('request_id', 'int');
    $action = InputSanitizer::post('action', 'string');
    $adminNotes = InputSanitizer::post('admin_notes', 'string');

    if (!$requestId || !$action) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }

    // Whitelist validation for action
    $validActions = ['acknowledged', 'completed', 'rejected'];
    if (!in_array($action, $validActions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Verify request exists and is actionable
        $checkStmt = $pdo->prepare("SELECT id, status, homeowner_id FROM profile_update_requests WHERE id = ?");
        $checkStmt->execute([$requestId]);
        $request = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Request not found']);
            exit();
        }

        // Update the request status
        $updateStmt = $pdo->prepare("
            UPDATE profile_update_requests 
            SET status = ?, admin_notes = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$action, $adminNotes ?: null, $requestId]);

        // Log the action to audit trail
        $logStmt = $pdo->prepare("
            INSERT INTO audit_trail (user_id, action, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $logStmt->execute([
            $_SESSION['user_id'] ?? 0,
            'profile_request_' . $action,
            json_encode([
                'request_id' => $requestId,
                'homeowner_id' => $request['homeowner_id'],
                'previous_status' => $request['status'],
                'new_status' => $action,
                'admin_notes' => $adminNotes
            ]),
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'
        ]);

        $pdo->commit();

        $actionLabels = [
            'acknowledged' => 'acknowledged',
            'completed' => 'marked as completed',
            'rejected' => 'rejected'
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Request has been ' . ($actionLabels[$action] ?? $action)
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Profile request action error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
