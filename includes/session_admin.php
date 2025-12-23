<?php
require_once __DIR__ . '/session_manager.php';

start_secure_session([
    'session_name' => 'vehiscan_admin',
    'timeout'      => 1800, // 30 minutes
    'log_audit'    => true,
    'regeneration_interval' => 900 // 15 minutes
]);
