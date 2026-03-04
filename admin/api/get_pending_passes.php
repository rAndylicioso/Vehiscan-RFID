<?php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';

header('Content-Type: application/json');

// Authorization check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

try {
    $stmt = $pdo->query("
        SELECT vp.*, h.name as homeowner_name, h.address, h.contact_number as homeowner_contact
        FROM visitor_passes vp
        JOIN homeowners h ON vp.homeowner_id = h.id
        WHERE vp.status = 'pending'
        ORDER BY vp.created_at DESC
    ");
    
    echo json_encode($stmt->fetchAll());
    
} catch (PDOException $e) {
    error_log("Get pending passes error: " . $e->getMessage());
    echo json_encode([]);
}
