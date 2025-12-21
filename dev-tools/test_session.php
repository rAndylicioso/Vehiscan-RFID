<?php
// Test admin session diagnostics
require_once __DIR__ . '/../includes/session_admin.php';

header('Content-Type: application/json');

$result = [
    'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE',
    'session_name' => session_name(),
    'session_id' => session_id(),
    'session_data' => [
        'role' => $_SESSION['role'] ?? 'NOT SET',
        'username' => $_SESSION['username'] ?? 'NOT SET',
        'csrf_token' => isset($_SESSION['csrf_token']) ? 'SET' : 'NOT SET',
        'last_activity' => $_SESSION['last_activity'] ?? 'NOT SET',
    ],
    'is_admin' => (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'),
    'timestamp' => date('Y-m-d H:i:s'),
    'all_session_keys' => array_keys($_SESSION)
];

echo json_encode($result, JSON_PRETTY_PRINT);
