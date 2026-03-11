<?php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    exit('Unauthorized');
}

// CSRF validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $csrf = $data['csrf_token'] ?? ($_POST['csrf_token'] ?? '');
    if (empty($csrf) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        http_response_code(403);
        header('Content-Type: application/json');
        exit(json_encode(['success' => false, 'message' => 'Invalid CSRF token']));
    }
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
    $statuses = ['IN', 'IN', 'IN', 'OUT']; // 75% entries
    
    foreach ($plates as $plate) {
        $status = $statuses[array_rand($statuses)];
        $log_time = date('H:i:s', strtotime('-' . rand(0, 60) . ' minutes'));
        
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
