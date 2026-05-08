<?php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/security_headers.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';
require_once __DIR__ . '/../../includes/request_method_helper.php';
require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../includes/email.php';
require_once __DIR__ . '/../../includes/email_templates.php';
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

requireRequestMethod('POST');

// Validate CSRF token
$csrfToken = InputSanitizer::post('csrf_token', 'string');
if (!InputSanitizer::validateCsrf($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

// Get the raw POST data
$input = json_decode(file_get_contents('php://input'), true);
$ids = isset($input['ids']) ? (array)$input['ids'] : [];
$action = isset($input['action']) ? (string)$input['action'] : '';
$reason = isset($input['reason']) ? (string)$input['reason'] : '';

if (empty($ids) || !in_array($action, ['approve', 'reject'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid parameters']);
    exit();
}

$successCount = 0;
$errors = [];

foreach ($ids as $idData) {
    $userId = (int)($idData['id'] ?? 0);
    $accountType = strtolower((string)($idData['type'] ?? 'homeowner'));

    if (!$userId) continue;

    try {
        $pdo->beginTransaction();

        $isHomeowner = ($accountType === 'homeowner');
        
        if ($action === 'approve') {
            if ($isHomeowner) {
                $stmt = $pdo->prepare("UPDATE homeowners SET account_status = 'approved' WHERE id = ?");
                $stmt->execute([$userId]);
                
                $stmt = $pdo->prepare("UPDATE homeowner_auth SET is_active = 1 WHERE homeowner_id = ?");
                $stmt->execute([$userId]);
                
                $stmt = $pdo->prepare("SELECT email, first_name, last_name FROM homeowners WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
            } else {
                $stmt = $pdo->prepare("UPDATE users SET account_status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $userId]);
                
                $stmt = $pdo->prepare("SELECT email, username as first_name, '' as last_name FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
            }
        } else { // reject
            if ($isHomeowner) {
                $stmt = $pdo->prepare("UPDATE homeowners SET account_status = 'rejected' WHERE id = ?");
                $stmt->execute([$userId]);
                
                $stmt = $pdo->prepare("UPDATE homeowner_auth SET is_active = 0 WHERE homeowner_id = ?");
                $stmt->execute([$userId]);
                
                $stmt = $pdo->prepare("SELECT email, first_name, last_name FROM homeowners WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
            } else {
                $stmt = $pdo->prepare("UPDATE users SET account_status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $reason, $userId]);
                
                $stmt = $pdo->prepare("SELECT email, username as first_name, '' as last_name FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
            }
        }

        // Log action
        try {
            $stmt = $pdo->prepare("INSERT INTO account_approval_log (user_id, user_type, action, approved_by, reason) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $accountType, $action === 'approve' ? 'approved' : 'rejected', $_SESSION['user_id'], $reason]);
        } catch (PDOException $e) {}

        $pdo->commit();
        $successCount++;

        // Send Email (Non-blocking if possible, but here it's linear)
        if ($user && !empty($user['email'])) {
            try {
                $name = trim($user['first_name'] . ' ' . $user['last_name']);
                if (empty($name)) $name = $user['email'];
                $loginUrl = rtrim(getAppUrl(), '/') . '/auth/login.php';

                if ($action === 'approve') {
                    EmailService::send($user['email'], 'Account Approved — VehiScan RFID', EmailTemplates::accountApprovedEmail($name, $loginUrl));
                } else {
                    EmailService::send($user['email'], 'Account Rejected — VehiScan RFID', EmailTemplates::accountRejectedEmail($name, $reason));
                }
            } catch (Exception $e) {
                error_log("Bulk approval email error for ID $userId: " . $e->getMessage());
            }
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errors[] = "ID $userId: " . $e->getMessage();
    }
}

echo json_encode([
    'success' => $successCount > 0,
    'message' => "Successfully processed $successCount accounts." . (!empty($errors) ? " Errors: " . count($errors) : ""),
    'processed' => $successCount,
    'errors' => $errors
]);
