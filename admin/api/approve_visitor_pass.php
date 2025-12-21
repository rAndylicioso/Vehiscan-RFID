<?php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';
require_once __DIR__ . '/../../includes/cache_invalidator.php';
require_once __DIR__ . '/qr_helper.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$pass_id = isset($data['pass_id']) ? InputSanitizer::sanitizeInt($data['pass_id']) : 0;

if (!$pass_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid pass ID']);
    exit();
}

try {
    // Get the pass details first
    $getPass = $pdo->prepare("SELECT qr_token FROM visitor_passes WHERE id = ? AND status = 'pending'");
    $getPass->execute([$pass_id]);
    $pass = $getPass->fetch(PDO::FETCH_ASSOC);

    if (!$pass) {
        echo json_encode(['success' => false, 'message' => 'Pass not found or already processed']);
        exit();
    }

    // Generate QR code
    $qrCode = generateVisitorPassQR($pass_id, $pass['qr_token'], $pdo);

    // Update the pass with active status and QR code
    $stmt = $pdo->prepare("
        UPDATE visitor_passes 
        SET status = 'active', 
            approved_by = ?, 
            approved_at = NOW(),
            qr_code = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $_SESSION['admin_id'] ?? $_SESSION['user_id'],
        $qrCode,
        $pass_id
    ]);


    if ($stmt->rowCount() > 0) {
        // Invalidate caches
        CacheInvalidator::invalidatePasses();

        echo json_encode(['success' => true, 'message' => 'Pass activated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update pass']);
    }

} catch (PDOException $e) {
    error_log("Approve pass error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Approve pass error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
