<?php
// admin/homeowners/homeowner_delete.php
require_once __DIR__ . '/../../includes/session_admin_unified.php';

// Check if user is admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

require_once __DIR__ . '/../../db.php';
header('Content-Type: application/json');

$csrf = $_SESSION['csrf_token'] ?? '';
$posted = $_POST['csrf'] ?? '';
if (!hash_equals($csrf, (string)$posted)) {
    echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit;
}
$id = intval($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }

try {
    $stmt = $pdo->prepare("DELETE FROM homeowners WHERE id = ?");
    $ok = $stmt->execute([$id]);
    echo json_encode(['success'=>$ok,'message'=> $ok ? "Deleted homeowner #{$id}" : 'Delete failed']);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
}
