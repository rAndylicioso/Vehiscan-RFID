<?php
/**
 * Get Weekly Statistics
 * Returns last 7 days of access log data for charts
 */

// Ensure JSON output ONLY - prevent any HTML output
ob_start();
header('Content-Type: application/json');

// Start session without redirects
if (session_status() === PHP_SESSION_NONE) {
    session_name('vehiscan_admin');
    @session_start();
}

// Check authentication
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    ob_end_clean();
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Unauthorized access']));
}

// Clear any buffered output before JSON
ob_end_clean();

require_once __DIR__ . '/../../db.php';

try {
    // Check which timestamp column exists
    $columns = $pdo->query("SHOW COLUMNS FROM recent_logs")->fetchAll(PDO::FETCH_COLUMN);
    $timeCol = in_array('created_at', $columns) ? 'created_at' : 'log_time';
    
    error_log("[Weekly Stats] Using column: $timeCol");
    
    // Get last 7 days of data - use prepared statement to avoid SQL injection
    if ($timeCol === 'created_at') {
        $stmt = $pdo->query("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
            FROM recent_logs
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
    } else {
        $stmt = $pdo->query("
            SELECT 
                DATE(log_time) as date,
                COUNT(*) as count
            FROM recent_logs
            WHERE log_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(log_time)
            ORDER BY date ASC
        ");
    }
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format for Chart.js
    $labels = [];
    $values = [];
    
    // Fill in missing days with 0
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayLabel = date('M d', strtotime($date));
        
        $found = false;
        foreach ($data as $row) {
            if ($row['date'] === $date) {
                $labels[] = $dayLabel;
                $values[] = (int)$row['count'];
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $labels[] = $dayLabel;
            $values[] = 0;
        }
    }
    
    error_log("[Weekly Stats] Returning " . count($labels) . " data points");
    error_log("[Weekly Stats] Labels: " . json_encode($labels));
    error_log("[Weekly Stats] Values: " . json_encode($values));
    
    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'values' => $values,
        'debug' => [
            'column_used' => $timeCol ?? 'unknown',
            'raw_count' => count($data)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch weekly stats: ' . $e->getMessage()
    ]);
}
