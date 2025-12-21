<?php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    exit('Unauthorized');
}

header('Content-Type: application/json');

try {
    // Get random homeowners
    $stmt = $pdo->query("SELECT plate_number FROM homeowners ORDER BY RAND() LIMIT 5");
    $plates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($plates)) {
        echo json_encode(['success' => false, 'message' => 'No homeowners found']);
        exit;
    }
    
    $count = 0;
    $statuses = ['ALLOWED', 'ALLOWED', 'ALLOWED', 'DENIED']; // 75% allowed
    
    foreach ($plates as $plate) {
        $status = $statuses[array_rand($statuses)];
        $log_time = date('Y-m-d H:i:s', strtotime('-' . rand(0, 60) . ' minutes'));
        
        $stmt = $pdo->prepare("INSERT INTO recent_logs (plate_number, status, log_time) VALUES (?, ?, ?)");
        $stmt->execute([$plate, $status, $log_time]);
        $count++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Generated $count demo log entries"
    ]);
} catch (Exception $e) {
    error_log('Demo logs error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to generate logs']);
}
