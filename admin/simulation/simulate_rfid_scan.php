<?php
// admin/simulation/simulate_rfid_scan.php
// Set JSON header FIRST before anything else
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/session_admin_unified.php';

// Security: Only admins and super_admins can simulate scans
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    error_log('[RFID_SIM] Unauthorized access attempt');
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once __DIR__ . '/../../db.php';

error_log('[RFID_SIM] Request received - Method: ' . $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log('[RFID_SIM] Invalid method');
    exit(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

// Validate CSRF token
$csrf = $_SESSION['csrf_token'] ?? '';
$posted = $_POST['csrf'] ?? '';
if (!hash_equals($csrf, (string)$posted)) {
    error_log('[RFID_SIM] Invalid CSRF token');
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Invalid security token']));
}

$plate = $_POST['plate_number'] ?? '';

error_log('[RFID_SIM] Plate number: ' . $plate);

if (empty($plate)) {
    error_log('[RFID_SIM] Empty plate number');
    exit(json_encode(['success' => false, 'message' => 'Plate number required']));
}

try {
    // Check if homeowner exists
    $stmt = $pdo->prepare("SELECT name, vehicle_type, plate_number FROM homeowners WHERE plate_number = ?");
    $stmt->execute([$plate]);
    $homeowner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$homeowner) {
        error_log('[RFID_SIM] Vehicle not found: ' . $plate);
        exit(json_encode([
            'success' => false, 
            'message' => 'Vehicle not registered in system'
        ]));
    }
    
    error_log('[RFID_SIM] Homeowner found: ' . $homeowner['name']);
    
    // Check the last scan status for this plate to toggle IN/OUT
    $stmt = $pdo->prepare(
        "SELECT status FROM recent_logs WHERE plate_number = ? ORDER BY log_id DESC LIMIT 1"
    );
    $stmt->execute([$plate]);
    $lastLog = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Toggle status: if last was IN, make it OUT; if OUT or no record, make it IN
    $newStatus = 'IN'; // Default for first scan
    if ($lastLog) {
        $newStatus = ($lastLog['status'] === 'IN') ? 'OUT' : 'IN';
        error_log('[RFID_SIM] Last status was ' . $lastLog['status'] . ', toggling to ' . $newStatus);
    } else {
        error_log('[RFID_SIM] No previous scan found, setting status to IN');
    }
    
    // Insert into recent_logs table (this is what the guard panel reads)
    // log_time is TIME field (use CURTIME()), created_at auto-populates
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO recent_logs (plate_number, status, log_time) VALUES (?, ?, CURTIME())"
        );
        $result = $stmt->execute([$plate, $newStatus]);

        if (!$result) {
            error_log('[RFID_SIM] Failed to insert into recent_logs');
            exit(json_encode([
                'success' => false,
                'message' => 'Failed to create log entry'
            ]));
        }

        error_log('[RFID_SIM] Inserted into recent_logs successfully with status=' . $newStatus);
    } catch (PDOException $e) {
        // If an unexpected schema or DB error occurs, surface it for debugging
        error_log('[RFID_SIM] Database insert error: ' . $e->getMessage());
        exit(json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]));
    }
    
    // Also log to rfid_simulator table for admin tracking
    try {
        $stmt = $pdo->prepare("
            INSERT INTO rfid_simulator (plate_number, simulated_at) 
            VALUES (?, NOW())
        ");
        $stmt->execute([$plate]);
        error_log('[RFID_SIM] Inserted into rfid_simulator table');
    } catch (PDOException $e) {
        // Non-critical error - simulator table might not exist
        error_log('[RFID_SIM] Warning: Could not insert into rfid_simulator: ' . $e->getMessage());
    }
    
    // Return success
    error_log('[RFID_SIM] Simulation complete - Success');
    
    $statusMessage = $newStatus === 'IN' ? 'Entry Logged' : 'Exit Logged';
    
    exit(json_encode([
        'success' => true,
        'message' => 'RFID scan simulated successfully',
        'plate' => $plate,
        'name' => $homeowner['name'],
        'status' => $statusMessage,
        'direction' => $newStatus
    ]));
    
} catch (PDOException $e) {
    error_log('[RFID_SIM] Database error: ' . $e->getMessage());
    exit(json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]));
}
