<?php
// admin/simulation/get_recent_simulation.php
require_once __DIR__ . '/../../includes/session_admin_unified.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT 
            s.plate_number,
            DATE_FORMAT(s.simulated_at, '%H:%i:%s') as time,
            h.name,
            h.vehicle_type
        FROM rfid_simulator s
        LEFT JOIN homeowners h ON s.plate_number = h.plate_number
        ORDER BY s.simulated_at DESC
        LIMIT 10
    ");
    
    $scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'scans' => $scans
    ]);
    
} catch (PDOException $e) {
    error_log('Get Recent Simulations Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
