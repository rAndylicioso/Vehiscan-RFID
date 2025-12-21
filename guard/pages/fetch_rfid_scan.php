<?php
// guard/pages/fetch_rfid_scan.php
require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../db.php';

// Security: Only guards can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guard') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

header('Content-Type: application/json');

try {
    // Get mode (single or multiple)
    $mode = $_GET['mode'] ?? 'single';
    
    if ($mode === 'multiple') {
        // Return last 5 scans for carousel
        $stmt = $pdo->query("
            SELECT DISTINCT r.plate_number, r.status, r.log_time,
                   h.name, h.address, h.contact_number AS contact, h.owner_img,
                   h.vehicle_type, h.color, h.car_img
            FROM recent_logs r
            LEFT JOIN homeowners h ON r.plate_number = h.plate_number
            WHERE r.log_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY r.created_at DESC
            LIMIT 5
        ");
        
        $scans = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($scans)) {
            echo json_encode(['success' => false, 'data' => []]);
            exit;
        }
        
        echo json_encode(['success' => true, 'data' => $scans]);
        exit;
    }
    
    // Default: Single most recent scan
    $stmt = $pdo->query("
        SELECT r.plate_number, r.status, r.log_time,
               h.name, h.address, h.contact_number AS contact, h.owner_img,
               h.vehicle_type, h.color, h.car_img
        FROM recent_logs r
        LEFT JOIN homeowners h ON r.plate_number = h.plate_number
        ORDER BY r.created_at DESC
        LIMIT 1
    ");
    
    $scan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$scan) {
        echo json_encode([
            'success' => false, 
            'message' => 'No recent scan detected',
            'data' => null
        ]);
        exit;
    }

    // Check if homeowner exists
    if (!$scan['name']) {
        echo json_encode([
            'success' => false,
            'message' => 'Unknown plate number: ' . $scan['plate_number'],
            'data' => [
                'plate_number' => $scan['plate_number'],
                'status' => $scan['status'],
                'name' => 'UNKNOWN',
                'address' => 'Not registered',
                'contact' => 'N/A',
                'vehicle_type' => 'Unknown',
                'color' => 'Unknown',
                'owner_img' => null,
                'car_img' => null
            ]
        ]);
        exit;
    }

    // Return full homeowner data
    echo json_encode([
        'success' => true,
        'message' => 'Scan retrieved',
        'data' => [
            'name' => $scan['name'],
            'address' => $scan['address'],
            'contact' => $scan['contact'],
            'vehicle_type' => $scan['vehicle_type'],
            'color' => $scan['color'],
            'plate_number' => $scan['plate_number'],
            'owner_img' => $scan['owner_img'],
            'car_img' => $scan['car_img'],
            'status' => $scan['status'],
            'log_time' => $scan['log_time']
        ]
    ]);

} catch (PDOException $e) {
    error_log('RFID fetch error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error',
        'data' => null
    ]);
}