<?php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
}
require_once __DIR__ . '/../../db.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT rl.plate_number, 
               TIME_FORMAT(rl.created_at, '%H:%i:%s') as time,
               rl.status,
               h.name, h.vehicle_type
        FROM recent_logs rl
        LEFT JOIN homeowners h ON rl.plate_number = h.plate_number
        ORDER BY rl.created_at DESC, rl.log_id DESC
        LIMIT 10
    ");
    
    echo json_encode([
        'success' => true,
        'scans' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false]);
}
