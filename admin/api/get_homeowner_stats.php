<?php
/**
 * Get Homeowner Statistics by Subdivision
 * Returns data for pie chart visualization
 */

require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../db.php';

try {
    $stmt = $pdo->query("
        SELECT 
            subdivision,
            COUNT(*) as count
        FROM homeowners
        WHERE subdivision IS NOT NULL
        GROUP BY subdivision
        ORDER BY count DESC
    ");
    
    echo json_encode($stmt->fetchAll());
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
