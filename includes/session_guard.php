<?php
require_once __DIR__ . '/session_manager.php';

start_secure_session([
    'session_name' => 'vehiscan_guard',
    'timeout'      => 0, // No timeout for guards
    'log_audit'    => false,
    'regeneration_interval' => 0 // No regeneration for guards
]);
