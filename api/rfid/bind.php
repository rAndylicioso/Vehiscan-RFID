<?php
/**
 * RFID Binding Session Management
 * 
 * Manages RFID binding sessions - initiate, cancel, check status.
 * Used by admin panel to bind RFID tags to vehicles.
 * 
 * POST /api/rfid/bind.php
 *   action=initiate  - Start a new binding session for a vehicle
 *   action=cancel    - Cancel an active binding session
 *   action=unbind    - Remove RFID binding from a vehicle
 *   action=status    - Check current binding session status (also supports GET)
 * 
 * GET /api/rfid/bind.php?action=status&session_id=<id>
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/security_headers.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';
require_once __DIR__ . '/../../includes/session_admin_unified.php';

// Auth check - admin, super_admin, or guard can bind RFID
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin', 'guard'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = InputSanitizer::post('action', 'string', '');
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = InputSanitizer::get('action', 'string', '');
}

if (empty($action)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Action is required']));
}

switch ($action) {
    case 'initiate':
        handleInitiate();
        break;
    case 'cancel':
        handleCancel();
        break;
    case 'unbind':
        handleUnbind();
        break;
    case 'status':
        handleStatus();
        break;
    default:
        http_response_code(400);
        exit(json_encode(['success' => false, 'message' => 'Invalid action']));
}

/**
 * Initiate a new RFID binding session
 */
function handleInitiate() {
    global $pdo;

    // CSRF validation
    $csrfToken = InputSanitizer::post('csrf_token', 'string');
    if (!InputSanitizer::validateCsrf($csrfToken)) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'message' => 'Invalid security token']));
    }

    $vehicleId = InputSanitizer::post('vehicle_id', 'int');
    if (!$vehicleId) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'message' => 'Vehicle ID is required']));
    }

    try {
        // Verify vehicle exists
        $stmt = $pdo->prepare("
            SELECT v.id, v.plate_number, v.rfid_uid, v.vehicle_type, h.name 
            FROM vehicles v 
            LEFT JOIN homeowners h ON v.homeowner_id = h.id 
            WHERE v.id = ?
        ");
        $stmt->execute([$vehicleId]);
        $vehicle = $stmt->fetch();

        if (!$vehicle) {
            exit(json_encode(['success' => false, 'message' => 'Vehicle not found']));
        }

        // Check if vehicle already has an RFID bound
        if (!empty($vehicle['rfid_uid'])) {
            exit(json_encode([
                'success' => false,
                'message' => "Vehicle {$vehicle['plate_number']} already has RFID tag bound ({$vehicle['rfid_uid']}). Unbind first."
            ]));
        }

        // Cancel any existing pending sessions for this vehicle
        $pdo->prepare("
            UPDATE rfid_binding_sessions 
            SET status = 'cancelled', completed_at = NOW() 
            WHERE target_id = ? AND target_type = 'vehicle' AND status = 'pending'
        ")->execute([$vehicleId]);

        // Also cancel any other pending sessions (only one at a time)
        $pdo->prepare("
            UPDATE rfid_binding_sessions 
            SET status = 'cancelled', completed_at = NOW() 
            WHERE status = 'pending'
        ")->execute();

        // Create binding session
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minute timeout

        $stmt = $pdo->prepare("
            INSERT INTO rfid_binding_sessions 
            (session_token, target_type, target_id, initiated_by, initiated_by_role, status, expires_at, created_at) 
            VALUES (?, 'vehicle', ?, ?, ?, 'pending', ?, NOW())
        ");
        $stmt->execute([
            $sessionToken,
            $vehicleId,
            $_SESSION['user_id'] ?? $_SESSION['id'] ?? 0,
            $_SESSION['role'],
            $expiresAt
        ]);

        $sessionId = $pdo->lastInsertId();

        logAudit('RFID binding initiated', 'vehicles', $vehicleId, 
            "Binding session #{$sessionId} for {$vehicle['plate_number']}");

        error_log("[RFID_BIND] Session #{$sessionId} initiated for vehicle #{$vehicleId} ({$vehicle['plate_number']})");

        exit(json_encode([
            'success' => true,
            'message' => "Binding session started for {$vehicle['plate_number']}. Scan an RFID tag within 5 minutes.",
            'data' => [
                'session_id' => (int)$sessionId,
                'session_token' => $sessionToken,
                'vehicle_id' => (int)$vehicleId,
                'plate_number' => $vehicle['plate_number'],
                'vehicle_type' => $vehicle['vehicle_type'],
                'owner_name' => $vehicle['name'],
                'expires_at' => $expiresAt,
                'timeout_seconds' => 300
            ]
        ]));

    } catch (PDOException $e) {
        error_log('[RFID_BIND] Initiate error: ' . $e->getMessage());
        http_response_code(500);
        exit(json_encode(['success' => false, 'message' => 'Database error']));
    }
}

/**
 * Cancel an active binding session
 */
function handleCancel() {
    global $pdo;

    // CSRF validation
    $csrfToken = InputSanitizer::post('csrf_token', 'string');
    if (!InputSanitizer::validateCsrf($csrfToken)) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'message' => 'Invalid security token']));
    }

    $sessionId = InputSanitizer::post('session_id', 'int');
    if (!$sessionId) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'message' => 'Session ID is required']));
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE rfid_binding_sessions 
            SET status = 'cancelled', completed_at = NOW() 
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$sessionId]);

        if ($stmt->rowCount() === 0) {
            exit(json_encode(['success' => false, 'message' => 'No active session found to cancel']));
        }

        logAudit('RFID binding cancelled', 'rfid_binding_sessions', $sessionId, 'Binding session cancelled by user');

        exit(json_encode(['success' => true, 'message' => 'Binding session cancelled']));

    } catch (PDOException $e) {
        error_log('[RFID_BIND] Cancel error: ' . $e->getMessage());
        http_response_code(500);
        exit(json_encode(['success' => false, 'message' => 'Database error']));
    }
}

/**
 * Unbind RFID from a vehicle
 */
function handleUnbind() {
    global $pdo;

    // CSRF validation
    $csrfToken = InputSanitizer::post('csrf_token', 'string');
    if (!InputSanitizer::validateCsrf($csrfToken)) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'message' => 'Invalid security token']));
    }

    $vehicleId = InputSanitizer::post('vehicle_id', 'int');
    if (!$vehicleId) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'message' => 'Vehicle ID is required']));
    }

    try {
        // Get current binding info
        $stmt = $pdo->prepare("SELECT id, plate_number, rfid_uid FROM vehicles WHERE id = ?");
        $stmt->execute([$vehicleId]);
        $vehicle = $stmt->fetch();

        if (!$vehicle) {
            exit(json_encode(['success' => false, 'message' => 'Vehicle not found']));
        }

        if (empty($vehicle['rfid_uid'])) {
            exit(json_encode(['success' => false, 'message' => 'Vehicle has no RFID tag bound']));
        }

        $oldUid = $vehicle['rfid_uid'];

        // Remove binding
        $pdo->prepare("UPDATE vehicles SET rfid_uid = NULL, rfid_bound_at = NULL, rfid_bound_by = NULL WHERE id = ?")
            ->execute([$vehicleId]);

        logAudit('RFID unbound', 'vehicles', $vehicleId, 
            "Removed RFID {$oldUid} from {$vehicle['plate_number']}");

        error_log("[RFID_BIND] Unbound UID: $oldUid from vehicle #{$vehicleId} ({$vehicle['plate_number']})");

        exit(json_encode([
            'success' => true,
            'message' => "RFID tag removed from {$vehicle['plate_number']}",
            'data' => [
                'vehicle_id' => (int)$vehicleId,
                'plate_number' => $vehicle['plate_number'],
                'old_rfid_uid' => $oldUid
            ]
        ]));

    } catch (PDOException $e) {
        error_log('[RFID_BIND] Unbind error: ' . $e->getMessage());
        http_response_code(500);
        exit(json_encode(['success' => false, 'message' => 'Database error']));
    }
}

/**
 * Check binding session status (supports GET and POST)
 */
function handleStatus() {
    global $pdo;

    $sessionId = null;
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sessionId = InputSanitizer::get('session_id', 'int');
    } else {
        $sessionId = InputSanitizer::post('session_id', 'int');
    }

    if (!$sessionId) {
        // Return latest active session if no ID specified
        try {
            $stmt = $pdo->query("
                SELECT bs.*, v.plate_number, v.vehicle_type, h.name as owner_name
                FROM rfid_binding_sessions bs
                LEFT JOIN vehicles v ON bs.target_id = v.id AND bs.target_type = 'vehicle'
                LEFT JOIN homeowners h ON v.homeowner_id = h.id
                WHERE bs.status = 'pending' AND bs.expires_at > NOW()
                ORDER BY bs.created_at DESC 
                LIMIT 1
            ");
            $session = $stmt->fetch();

            if (!$session) {
                exit(json_encode(['success' => true, 'active' => false, 'message' => 'No active binding session']));
            }

            $remaining = max(0, strtotime($session['expires_at']) - time());

            exit(json_encode([
                'success' => true,
                'active' => true,
                'data' => [
                    'session_id' => (int)$session['id'],
                    'status' => $session['status'],
                    'plate_number' => $session['plate_number'],
                    'vehicle_type' => $session['vehicle_type'],
                    'owner_name' => $session['owner_name'],
                    'expires_at' => $session['expires_at'],
                    'remaining_seconds' => $remaining
                ]
            ]));

        } catch (PDOException $e) {
            error_log('[RFID_BIND] Status check error: ' . $e->getMessage());
            exit(json_encode(['success' => false, 'message' => 'Database error']));
        }
    }

    try {
        $stmt = $pdo->prepare("
            SELECT bs.*, v.plate_number, v.vehicle_type, h.name as owner_name
            FROM rfid_binding_sessions bs
            LEFT JOIN vehicles v ON bs.target_id = v.id AND bs.target_type = 'vehicle'
            LEFT JOIN homeowners h ON v.homeowner_id = h.id
            WHERE bs.id = ?
        ");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();

        if (!$session) {
            exit(json_encode(['success' => false, 'message' => 'Session not found']));
        }

        // Check if session has expired
        if ($session['status'] === 'pending' && strtotime($session['expires_at']) < time()) {
            $pdo->prepare("UPDATE rfid_binding_sessions SET status = 'timeout', completed_at = NOW() WHERE id = ?")
                ->execute([$sessionId]);
            $session['status'] = 'timeout';
        }

        $remaining = $session['status'] === 'pending' ? max(0, strtotime($session['expires_at']) - time()) : 0;

        exit(json_encode([
            'success' => true,
            'active' => $session['status'] === 'pending',
            'data' => [
                'session_id' => (int)$session['id'],
                'status' => $session['status'],
                'scanned_uid' => $session['scanned_uid'],
                'plate_number' => $session['plate_number'],
                'vehicle_type' => $session['vehicle_type'],
                'owner_name' => $session['owner_name'],
                'expires_at' => $session['expires_at'],
                'completed_at' => $session['completed_at'],
                'remaining_seconds' => $remaining
            ]
        ]));

    } catch (PDOException $e) {
        error_log('[RFID_BIND] Status error: ' . $e->getMessage());
        exit(json_encode(['success' => false, 'message' => 'Database error']));
    }
}
