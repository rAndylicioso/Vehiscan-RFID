<?php
require_once __DIR__ . '/../includes/session_guard.php';
require_once __DIR__ . '/../db.php';

// Security: Only guards can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guard') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

header('Content-Type: application/json');
$pdo->query("UPDATE notifications SET is_read=1 WHERE is_read=0");
echo json_encode(['success'=>true]);
?>
