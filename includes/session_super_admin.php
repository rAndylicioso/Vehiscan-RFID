<?php
require_once __DIR__ . '/session_manager.php';

start_secure_session([
    'session_name' => 'vehiscan_superadmin',
    'timeout'      => 1800, // 30 minutes
    'log_audit'    => true,
    'regeneration_interval' => 900, // 15 minutes
    'samesite'     => 'Strict'
]);

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ||
              (isset($_GET['ajax']) && $_GET['ajax'] == '1');

    if ($isAjax) {
        http_response_code(403);
        header('Content-Type: application/json');
        exit(json_encode([
            'error' => 'Unauthorized',
            'redirect' => '/Vehiscan-RFID/auth/login.php'
        ]));
    }

    header("Location: /Vehiscan-RFID/auth/login.php");
    exit();
}
