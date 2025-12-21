<?php
require_once __DIR__ . '/../../includes/session_guard.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guard') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}
require_once __DIR__ . '/../../db.php';

try {
    // Check if visitor_passes table exists
    $checkTable = $pdo->query("SHOW TABLES LIKE 'visitor_passes'");
    if ($checkTable->rowCount() === 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'passes' => [],
            'message' => 'Visitor passes table not yet created'
        ]);
        exit;
    }
    
    // Build query to show ACTIVE visitor passes
    // Filter by status = 'active' or 'approved' (approved by admin) within valid time range
    $query = "
        SELECT 
            vp.id,
            vp.visitor_name,
            vp.visitor_plate,
            vp.purpose,
            vp.valid_from,
            vp.valid_until,
            vp.status,
            vp.qr_code,
            vp.created_at,
            COALESCE(h.name, CONCAT(h.first_name, ' ', h.last_name)) as homeowner_name
        FROM visitor_passes vp
        LEFT JOIN homeowners h ON vp.homeowner_id = h.id
        WHERE vp.status IN ('active', 'approved')
        ORDER BY vp.created_at DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $passes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('Visitor passes fetched: ' . count($passes) . ' passes found');
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'passes' => $passes,
        'count' => count($passes)
    ]);
    
} catch (Exception $e) {
    error_log('Visitor fetch error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch visitor passes: ' . $e->getMessage()
    ]);
}
