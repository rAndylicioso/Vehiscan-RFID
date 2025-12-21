<?php
/**
 * Export and Delete Logs
 * 
 * This endpoint:
 * 1. Exports all logs to CSV format
 * 2. Deletes all logs from the database
 * 3. Returns the CSV data and deletion count
 * 
 * Security: Only guards can access this endpoint
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session_guard.php';

// SECURITY: Guards can no longer delete logs - only administrators
// This endpoint is deprecated and will return 403 for all guard access
http_response_code(403);
exit(json_encode([
    'success' => false,
    'message' => 'Access denied. Only administrators can delete logs.'
]));

require_once __DIR__ . '/../db.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]));
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // STEP 1: Export logs to CSV format
    $stmt = $pdo->query("
        SELECT 
            al.created_at as timestamp,
            h.name as homeowner_name,
            al.plate_number,
            al.status,
            h.vehicle_type,
            h.color,
            h.address,
            h.contact
        FROM recent_logs al
        LEFT JOIN homeowners h ON al.plate_number = h.plate_number
        ORDER BY al.created_at DESC, al.log_id DESC
    ");
    
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $recordCount = count($logs);
    
    // Generate CSV content
    $csvContent = '';
    
    // Add UTF-8 BOM for Excel compatibility
    $csvContent .= chr(0xEF) . chr(0xBB) . chr(0xBF);
    
    // CSV Header
    $header = [
        'Timestamp',
        'Homeowner Name',
        'Plate Number',
        'Status',
        'Vehicle Type',
        'Color',
        'Address',
        'Contact'
    ];
    $csvContent .= '"' . implode('","', $header) . '"' . "\n";
    
    // CSV Data Rows
    foreach ($logs as $row) {
        $csvRow = [
            $row['timestamp'] ?? '',
            $row['homeowner_name'] ?? 'Unknown',
            $row['plate_number'] ?? '',
            $row['status'] ?? '',
            $row['vehicle_type'] ?? '',
            $row['color'] ?? '',
            $row['address'] ?? '',
            $row['contact'] ?? ''
        ];
        
        // Escape quotes and wrap in quotes
        $csvRow = array_map(function($value) {
            return str_replace('"', '""', $value);
        }, $csvRow);
        
        $csvContent .= '"' . implode('","', $csvRow) . '"' . "\n";
    }
    
    // STEP 2: Delete all logs from database
    $deleteStmt = $pdo->prepare("DELETE FROM recent_logs");
    $deleteResult = $deleteStmt->execute();
    
    if (!$deleteResult) {
        throw new Exception('Failed to delete logs from database');
    }
    
    // STEP 3: Create audit log entry
    try {
        $auditStmt = $pdo->prepare("
            INSERT INTO audit_logs (username, action, table_name, details, ip_address, created_at)
            VALUES (?, 'EXPORT_AND_DELETE', 'recent_logs', ?, ?, NOW())
        ");
        $auditStmt->execute([
            $_SESSION['username'] ?? 'guard',
            "Exported and deleted all logs ($recordCount records)",
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (Exception $e) {
        // Audit log failed, but continue (non-critical)
        error_log("[GUARD] Audit log failed: " . $e->getMessage());
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Log success
    error_log("[GUARD] Successfully exported and deleted $recordCount logs by user: " . ($_SESSION['username'] ?? 'guard'));
    
    // Return success with CSV data
    exit(json_encode([
        'success' => true,
        'message' => 'Logs exported and deleted successfully',
        'recordsDeleted' => $recordCount,
        'csvData' => $csvContent
    ]));
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("[GUARD] Export and delete error: " . $e->getMessage());
    
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'message' => 'Failed to export and delete logs: ' . $e->getMessage()
    ]));
}
