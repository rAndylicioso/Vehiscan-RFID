<?php
/**
 * Get activity logs for homeowner's vehicles
 */
require_once __DIR__ . '/../../includes/session_homeowner.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['homeowner_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $period = $_GET['period'] ?? 'week'; // day, week, month
    $vehicleId = $_GET['vehicle_id'] ?? null;
    
    // Determine date range
    switch ($period) {
        case 'day':
            $dateFrom = date('Y-m-d 00:00:00');
            $groupBy = "DATE_FORMAT(al.timestamp, '%H:00')";
            $dateFormat = '%H:00';
            break;
        case 'month':
            $dateFrom = date('Y-m-d 00:00:00', strtotime('-30 days'));
            $groupBy = "DATE(al.timestamp)";
            $dateFormat = '%Y-%m-%d';
            break;
        case 'week':
        default:
            $dateFrom = date('Y-m-d 00:00:00', strtotime('-7 days'));
            $groupBy = "DATE(al.timestamp)";
            $dateFormat = '%Y-%m-%d';
            break;
    }
    
    // Get plate numbers for this homeowner
    $stmt = $pdo->prepare("
        SELECT plate_number 
        FROM homeowner_vehicles 
        WHERE homeowner_id = ? AND is_active = TRUE
    ");
    $stmt->execute([$_SESSION['homeowner_id']]);
    $plateNumbers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($plateNumbers)) {
        echo json_encode([
            'success' => true,
            'activity' => [],
            'summary' => [
                'total_entries' => 0,
                'total_exits' => 0,
                'total_logs' => 0
            ]
        ]);
        exit();
    }
    
    $placeholders = implode(',', array_fill(0, count($plateNumbers), '?'));
    
    // Get activity data grouped by time period
    $query = "
        SELECT 
            DATE_FORMAT(al.timestamp, '$dateFormat') as period,
            SUM(CASE WHEN al.status = 'IN' THEN 1 ELSE 0 END) as entries,
            SUM(CASE WHEN al.status = 'OUT' THEN 1 ELSE 0 END) as exits,
            COUNT(*) as total
        FROM access_logs al
        WHERE al.plate_number IN ($placeholders)
          AND al.timestamp >= ?
        GROUP BY $groupBy
        ORDER BY al.timestamp ASC
    ";
    
    $params = array_merge($plateNumbers, [$dateFrom]);
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get summary
    $summaryQuery = "
        SELECT 
            SUM(CASE WHEN status = 'IN' THEN 1 ELSE 0 END) as total_entries,
            SUM(CASE WHEN status = 'OUT' THEN 1 ELSE 0 END) as total_exits,
            COUNT(*) as total_logs
        FROM access_logs
        WHERE plate_number IN ($placeholders)
          AND timestamp >= ?
    ";
    
    $stmt = $pdo->prepare($summaryQuery);
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'period' => $period,
        'activity' => $activity,
        'summary' => $summary
    ]);
    
} catch (Exception $e) {
    error_log("Get vehicle activity error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch activity: ' . $e->getMessage()
    ]);
}
