<?php
// guard/clear_all_logs.php - Clear all logs with backup
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
    // Get count before deletion
    $stmt = $pdo->query("SELECT COUNT(*) FROM recent_logs");
    $count = $stmt->fetchColumn();
    
    error_log("[GUARD] Clearing all logs. Total count: $count");
    
    // Delete all logs
    $stmt = $pdo->prepare("DELETE FROM recent_logs");
    $result = $stmt->execute();
    
    if ($result) {
        // Log audit entry
        try {
            $stmt = $pdo->prepare("
                INSERT INTO audit_logs (username, action, table_name, details, ip_address, created_at)
                VALUES (?, 'DELETE_ALL', 'recent_logs', ?, ?, NOW())
            ");
            $stmt->execute([
                $_SESSION['username'] ?? 'guard',
                "Cleared all logs ($count records)",
                $_SERVER['REMOTE_ADDR']
            ]);
        } catch (Exception $e) {
            error_log("[GUARD] Failed to log audit entry: " . $e->getMessage());
        }
        
        error_log("[GUARD] Successfully cleared $count logs");
        
        exit(json_encode([
            'success' => true,
            'message' => 'All logs cleared successfully',
            'deleted_count' => $count
        ]));
    } else {
        throw new Exception('Failed to execute delete query');
    }
    
} catch (PDOException $e) {
    error_log("[GUARD] Database error clearing logs: " . $e->getMessage());
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]));
} catch (Exception $e) {
    error_log("[GUARD] Error clearing logs: " . $e->getMessage());
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]));
}
