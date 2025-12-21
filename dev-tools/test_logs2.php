<?php
require_once 'c:/xampp/htdocs/Vehiscan-RFID/db.php';
try {
    $count = $pdo->query('SELECT COUNT(*) FROM recent_logs')->fetchColumn();
    echo 'Total logs in recent_logs: ' . $count . PHP_EOL;
    $logs = $pdo->query('SELECT log_id, plate_number, status, created_at FROM recent_logs ORDER BY created_at DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
    foreach($logs as $log) {
        echo 'ID: ' . $log['log_id'] . ' | Plate: ' . $log['plate_number'] . ' | Status: ' . $log['status'] . ' | Date: ' . $log['created_at'] . PHP_EOL;
    }
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
