<?php
/**
 * Weekly Stats API Endpoint
 * Returns 7-day activity data for dashboard chart
 */

// Security: Role-based access control
require_once __DIR__ . '/../includes/session_admin_unified.php';
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['success' => false, 'error' => 'Unauthorized access']));
}

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

try {
    // Get last 7 days of data
    $labels = [];
    $values = [];

    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('D', strtotime($date)); // Mon, Tue, Wed, etc.

        // Count logs for this day
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM recent_logs 
            WHERE DATE(log_time) = ?
        ");
        $stmt->execute([$date]);
        $count = $stmt->fetchColumn();
        $values[] = (int) $count;
    }

    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'values' => $values
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
