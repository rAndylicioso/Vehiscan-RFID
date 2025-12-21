<?php
require_once __DIR__ . '/../../db.php';
header('Content-Type: application/json');

session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
    
    $stmt = $pdo->prepare("
        SELECT DATE(timestamp) as date, COUNT(*) as count 
        FROM access_logs 
        WHERE timestamp >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(timestamp)
        ORDER BY date ASC
    ");
    $stmt->execute([$days]);
    $activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'activity' => $activity]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
