<?php
/**
 * Database Structure Test
 * Returns information about database tables and columns
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

try {
    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get columns for each critical table
    $columns = [];
    $criticalTables = ['recent_logs', 'homeowners', 'admins', 'vehicles'];
    
    foreach ($criticalTables as $table) {
        if (in_array($table, $tables)) {
            // Sanitize table name to prevent SQL injection
            $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            $stmt = $pdo->prepare("SHOW COLUMNS FROM `$safeTable`");
            $stmt->execute();
            $columns[$table] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }
    
    // Check recent_logs for data
    $logCount = $pdo->query("SELECT COUNT(*) FROM recent_logs")->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'tables' => $tables,
        'columns' => $columns,
        'log_count' => $logCount
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
