<?php
/**
 * Get Pending User Accounts for Admin Approval
 */

require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';

header('Content-Type: application/json');

try {
    // Query homeowners table with auth information
    $stmt = $pdo->query("
        SELECT 
            h.id,
            h.name,
            h.first_name,
            h.middle_name,
            h.last_name,
            h.suffix,
            h.email,
            h.contact_number,
            h.address,
            h.plate_number,
            h.created_at,
            ha.username,
            'homeowner' as role
        FROM homeowners h
        LEFT JOIN homeowner_auth ha ON h.id = ha.homeowner_id
        WHERE h.account_status = 'pending'
        ORDER BY h.created_at DESC
    ");
    
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($accounts);
} catch (PDOException $e) {
    error_log('Error loading pending accounts: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
