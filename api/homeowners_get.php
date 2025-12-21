<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('vehiscan_admin');
    session_start();
}
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/input_sanitizer.php';
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$stmt = $pdo->query("SELECT id, name, contact, plate_number, vehicle_type FROM homeowners ORDER BY id DESC");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
