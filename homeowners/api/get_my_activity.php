<?php
/**
 * Get Homeowner Vehicle Activity
 * Returns access logs for vehicles registered to this homeowner
 */
require_once __DIR__ . '/../../includes/security_headers.php';

session_name('vehiscan_session');
session_start();

if (!isset($_SESSION['homeowner_id']) || $_SESSION['role'] !== 'homeowner') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

require_once __DIR__ . '/../../db.php';

$homeownerId = $_SESSION['homeowner_id'];
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;

try {
    // Check if vehicles table exists
    $tablesQuery = $pdo->query("SHOW TABLES LIKE 'vehicles'");
    $vehiclesTableExists = $tablesQuery->rowCount() > 0;
    
    // Get homeowner's plate numbers from both homeowners and vehicles tables
    if ($vehiclesTableExists) {
        $plateStmt = $pdo->prepare("
            SELECT DISTINCT plate_number FROM (
                SELECT plate_number FROM homeowners WHERE id = ?
                UNION
                SELECT plate_number FROM vehicles WHERE homeowner_id = ? AND is_active = 1
            ) AS all_plates
            WHERE plate_number IS NOT NULL AND plate_number != ''
        ");
        $plateStmt->execute([$homeownerId, $homeownerId]);
    } else {
        // Fallback if vehicles table doesn't exist
        $plateStmt = $pdo->prepare("
            SELECT plate_number FROM homeowners WHERE id = ? AND plate_number IS NOT NULL AND plate_number != ''
        ");
        $plateStmt->execute([$homeownerId]);
    }
    $plates = $plateStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($plates)) {
        // No vehicles registered
        echo json_encode([
            'success' => true,
            'logs' => [],
            'stats' => [
                'total_entries' => 0,
                'in_count' => 0,
                'out_count' => 0,
                'vehicles_used' => 0,
                'active_days' => 0
            ],
            'daily_activity' => []
        ]);
        exit;
    }
    
    // Create placeholder for IN clause
    $placeholders = str_repeat('?,', count($plates) - 1) . '?';
    
    // Get access logs for this homeowner's vehicles
    if ($vehiclesTableExists) {
        $stmt = $pdo->prepare("
            SELECT 
                al.log_id,
                al.plate_number,
                al.status,
                COALESCE(al.created_at, al.log_time) as created_at,
                COALESCE(v.vehicle_type, h.vehicle_type) as vehicle_type,
                COALESCE(v.color, h.color) as color,
                COALESCE(v.brand, h.brand) as brand,
                COALESCE(v.model, h.model) as model
            FROM recent_logs al
            LEFT JOIN vehicles v ON al.plate_number = v.plate_number
            LEFT JOIN homeowners h ON al.plate_number = h.plate_number
            WHERE al.plate_number IN ($placeholders)
              AND COALESCE(al.created_at, al.log_time) >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY COALESCE(al.created_at, al.log_time) DESC
            LIMIT 100
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT 
                al.log_id,
                al.plate_number,
                al.status,
                COALESCE(al.created_at, al.log_time) as created_at,
                h.vehicle_type,
                h.color,
                h.brand,
                h.model
            FROM recent_logs al
            LEFT JOIN homeowners h ON al.plate_number = h.plate_number
            WHERE al.plate_number IN ($placeholders)
              AND COALESCE(al.created_at, al.log_time) >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY COALESCE(al.created_at, al.log_time) DESC
            LIMIT 100
        ");
    }
    $stmt->execute([...$plates, $days]);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_entries,
            SUM(CASE WHEN status = 'IN' THEN 1 ELSE 0 END) as in_count,
            SUM(CASE WHEN status = 'OUT' THEN 1 ELSE 0 END) as out_count,
            COUNT(DISTINCT plate_number) as vehicles_used,
            COUNT(DISTINCT DATE(COALESCE(created_at, log_time))) as active_days
        FROM recent_logs
        WHERE plate_number IN ($placeholders)
          AND COALESCE(created_at, log_time) >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([...$plates, $days]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get daily activity for chart
    $stmt = $pdo->prepare("
        SELECT 
            DATE(COALESCE(created_at, log_time)) as date,
            SUM(CASE WHEN status = 'IN' THEN 1 ELSE 0 END) as in_count,
            SUM(CASE WHEN status = 'OUT' THEN 1 ELSE 0 END) as out_count
        FROM recent_logs
        WHERE plate_number IN ($placeholders)
          AND COALESCE(created_at, log_time) >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DATE(COALESCE(created_at, log_time))
        ORDER BY date ASC
    ");
    $stmt->execute([...$plates, $days]);
    $dailyActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'stats' => $stats,
        'daily_activity' => $dailyActivity,
        'period_days' => $days
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch activity: ' . $e->getMessage()
    ]);
}
