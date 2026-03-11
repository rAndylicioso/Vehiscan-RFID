<?php
require_once __DIR__ . '/../includes/session_guard.php';
require_once __DIR__ . '/../db.php';

// Security: Only guards can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guard') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

// CSRF validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$postedToken = $_POST['csrf_token'] ?? '';
if (empty($postedToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $postedToken)) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Invalid CSRF token']));
}

header('Content-Type: application/json');

// Scope to the current guard's notifications
$guardId = $_SESSION['guard_id'] ?? $_SESSION['user_id'] ?? 0;
$stmt = $pdo->prepare("UPDATE notifications SET is_read=1 WHERE is_read=0 AND (user_id = ? OR user_id IS NULL)");
$stmt->execute([$guardId]);
echo json_encode(['success'=>true]);
?>
