<?php
// Security: Role-based access control
require_once __DIR__ . '/../../includes/session_guard.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guard') {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized']));
}

require_once __DIR__ . '/../../db.php';

try {
    // Fetch all homeowners for dropdown/autocomplete
    $query = "
        SELECT 
            id,
            COALESCE(name, CONCAT(first_name, ' ', last_name)) as name,
            plate_number,
            address,
            contact_number
        FROM homeowners
        WHERE account_status = 'active'
        ORDER BY name ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $homeowners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'homeowners' => $homeowners,
        'count' => count($homeowners)
    ]);

} catch (Exception $e) {
    error_log('Fetch homeowners error: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch homeowners',
        'homeowners' => []
    ]);
}
?>