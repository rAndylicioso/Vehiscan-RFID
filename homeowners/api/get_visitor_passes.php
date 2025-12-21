<?php
require_once __DIR__ . '/../../includes/security_headers.php';

// Configure session for local network testing
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', 0);

// Use the same session name as login
session_name('vehiscan_session');
session_start();

header('Content-Type: application/json');

// Check if homeowner is logged in
if (!isset($_SESSION['homeowner_id']) || $_SESSION['role'] !== 'homeowner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once __DIR__ . '/../../db.php';

try {
    // Get visitor passes for this homeowner
    $stmt = $pdo->prepare("
        SELECT vp.*, 
               CASE 
                   WHEN vp.status = 'approved' AND NOW() BETWEEN vp.valid_from AND vp.valid_until THEN 'active'
                   WHEN vp.status = 'approved' AND NOW() > vp.valid_until THEN 'expired'
                   ELSE vp.status
               END as display_status
        FROM visitor_passes vp
        WHERE vp.homeowner_id = ?
        ORDER BY vp.created_at DESC
    ");
    $stmt->execute([$_SESSION['homeowner_id']]);
    $passes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'passes' => $passes
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching visitor passes: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load visitor passes'
    ]);
}
