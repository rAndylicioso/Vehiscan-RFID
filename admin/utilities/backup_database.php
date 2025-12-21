<?php
// Clear any output buffers and suppress errors in output
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Clear any accumulated output
ob_end_clean();
header('Content-Type: application/json');

// Set PDO error mode to exception
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $backup_dir = __DIR__ . '/../backups_db';
    if (!is_dir($backup_dir)) {
        if (!mkdir($backup_dir, 0755, true)) {
            throw new Exception('Failed to create backup directory');
        }
    }

    $filename = 'vehiscan_backup_' . date('Y-m-d_His') . '.sql';
    $filepath = $backup_dir . '/' . $filename;

    // Get database config
    $host = 'localhost';
    $db = 'vehiscan_vdp';
    $user = 'root';
    $pass = '';

    // Create backup using mysqldump
    $command = "mysqldump --host=$host --user=$user --password=$pass $db > $filepath 2>&1";
    exec($command, $output, $return_var);

    if ($return_var === 0 && file_exists($filepath)) {
        echo json_encode([
            'success' => true,
            'message' => 'Backup created successfully',
            'filename' => $filename,
            'size' => round(filesize($filepath) / 1024, 2) . ' KB'
        ]);
    } else {
        // Fallback: PHP-based backup
        $tables = [];
        $result = $pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sql = "-- VehiScan Database Backup\n";
        $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            // Sanitize table name to prevent SQL injection
            $safeTable = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            $sql .= "DROP TABLE IF EXISTS `$safeTable`;\n";
            
            // Use prepared statement for SHOW CREATE TABLE
            $stmt = $pdo->prepare("SHOW CREATE TABLE `$safeTable`");
            $stmt->execute();
            $create = $stmt->fetch(PDO::FETCH_NUM);
            if (!$create || !isset($create[1])) {
                continue; // Skip if table creation statement not available
            }
            $sql .= $create[1] . ";\n\n";
            
            // Use prepared statement for SELECT
            $stmt = $pdo->prepare("SELECT * FROM `$safeTable`");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $values = array_map(function($val) use ($pdo) {
                        return $val === null ? 'NULL' : $pdo->quote($val);
                    }, array_values($row));
                    $sql .= "INSERT INTO `$safeTable` VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }

        file_put_contents($filepath, $sql);
        
        echo json_encode([
            'success' => true,
            'message' => 'Backup created successfully (PHP mode)',
            'filename' => $filename,
            'size' => round(filesize($filepath) / 1024, 2) . ' KB'
        ]);
    }
} catch (Exception $e) {
    error_log('Backup error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Backup failed: ' . $e->getMessage()
    ]);
}
exit();
