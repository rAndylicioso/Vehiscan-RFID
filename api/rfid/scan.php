<?php
/**
 * RFID Scan Endpoint
 * 
 * Receives RFID UID scans from hardware readers (ESP32/Arduino) or the simulator.
 * Authenticates via API key, logs scans, and handles access control.
 * 
 * POST /api/rfid/scan.php
 * Headers: X-API-Key: <api_key>, X-Reader-ID: <reader_id>
 * Body: { "rfid_uid": "ABC123DEF456" }
 * 
 * OR for simulator/internal: POST with session auth
 * Body: rfid_uid=<uid>&csrf=<token>
 */

header('Content-Type: application/json');

// CORS: only allow same-origin and configured trusted origins
$allowedOrigins = ['http://localhost', 'https://localhost', 'http://127.0.0.1'];
$wifiIp = getenv('WIFI_IP');
if ($wifiIp) {
    $allowedOrigins[] = 'http://' . $wifiIp;
    $allowedOrigins[] = 'https://' . $wifiIp;
}
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, X-Reader-ID');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed']));
}

require_once __DIR__ . '/../../db.php';

// Determine authentication method
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$readerId = $_SERVER['HTTP_X_READER_ID'] ?? '';
$inputSource = 'unknown';
$apiKeyId = null;

if (!empty($apiKey)) {
    // Hardware reader authentication via API key
    try {
        $stmt = $pdo->prepare("SELECT id, reader_id, is_active FROM rfid_api_keys WHERE api_key = ?");
        $stmt->execute([$apiKey]);
        $key = $stmt->fetch();

        if (!$key || !$key['is_active']) {
            http_response_code(401);
            exit(json_encode(['success' => false, 'message' => 'Invalid or inactive API key']));
        }

        // Verify reader ID matches if provided
        if (!empty($readerId) && $key['reader_id'] !== $readerId) {
            http_response_code(401);
            exit(json_encode(['success' => false, 'message' => 'Reader ID mismatch']));
        }

        $apiKeyId = $key['id'];
        $inputSource = 'api_key';
        $readerId = $key['reader_id'];

        // Update last used timestamp
        $pdo->prepare("UPDATE rfid_api_keys SET last_used_at = NOW() WHERE id = ?")->execute([$apiKeyId]);
    } catch (PDOException $e) {
        error_log('[RFID_SCAN] API key validation error: ' . $e->getMessage());
        http_response_code(500);
        exit(json_encode(['success' => false, 'message' => 'Authentication error']));
    }
} else {
    // Session-based authentication (simulator or admin)
    // Use guard session ONLY when no admin/superadmin cookie exists
    $hasAdminCookie = isset($_COOKIE['vehiscan_admin']) || isset($_COOKIE['vehiscan_superadmin']);
    if (isset($_COOKIE['vehiscan_guard']) && !$hasAdminCookie) {
        require_once __DIR__ . '/../../includes/session_guard.php';
    } else {
        require_once __DIR__ . '/../../includes/session_admin_unified.php';
    }
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin', 'guard'])) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
    }

    // CSRF validation for session-based requests
    $csrf = $_SESSION['csrf_token'] ?? '';
    $posted = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, (string)$posted)) {
        error_log('[RFID_SCAN] CSRF mismatch — session_name=' . session_name()
            . ', role=' . ($_SESSION['role'] ?? 'none')
            . ', has_session_token=' . (!empty($csrf) ? 'yes' : 'no')
            . ', has_posted_token=' . (!empty($posted) ? 'yes' : 'no'));
        http_response_code(403);
        exit(json_encode(['success' => false, 'message' => 'Invalid security token']));
    }

    $inputSource = 'simulator';
}

// Get RFID UID from request body
$rfidUid = '';
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'application/json') !== false) {
    $body = json_decode(file_get_contents('php://input'), true);
    $rfidUid = trim($body['rfid_uid'] ?? '');
} else {
    $rfidUid = trim($_POST['rfid_uid'] ?? '');
}

if (empty($rfidUid)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'RFID UID is required']));
}

// Sanitize RFID UID (alphanumeric only, max 32 chars)
$rfidUid = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $rfidUid));
if (strlen($rfidUid) > 32 || strlen($rfidUid) < 4) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Invalid RFID UID format']));
}

try {
    $pdo->beginTransaction();

    // Check 1: Is there an active binding session waiting for this scan?
    $bindStmt = $pdo->prepare("
        SELECT id, target_type, target_id, initiated_by 
        FROM rfid_binding_sessions 
        WHERE status = 'pending' AND expires_at > NOW()
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $bindStmt->execute();
    $bindingSession = $bindStmt->fetch();

    if ($bindingSession) {
        // Complete the binding session
        $targetId = $bindingSession['target_id'];

        // Check if this UID is already bound to another vehicle
        $dupCheck = $pdo->prepare("SELECT id, plate_number FROM vehicles WHERE rfid_uid = ? AND id != ?");
        $dupCheck->execute([$rfidUid, $targetId]);
        $duplicate = $dupCheck->fetch();

        if ($duplicate) {
            // UID already in use
            $pdo->prepare("UPDATE rfid_binding_sessions SET status = 'cancelled', completed_at = NOW() WHERE id = ?")
                ->execute([$bindingSession['id']]);

            // Log the failed binding
            logScan($pdo, $rfidUid, $readerId, $apiKeyId, 'binding_failed', $inputSource, null, $bindingSession['id'],
                "UID already bound to vehicle {$duplicate['plate_number']}");

            $pdo->commit();
            exit(json_encode([
                'success' => false,
                'scan_result' => 'binding_failed',
                'message' => "This RFID tag is already bound to vehicle {$duplicate['plate_number']}"
            ]));
        }

        // Bind the RFID UID to the vehicle
        $pdo->prepare("UPDATE vehicles SET rfid_uid = ?, rfid_bound_at = NOW(), rfid_bound_by = ? WHERE id = ?")
            ->execute([$rfidUid, $bindingSession['initiated_by'], $targetId]);

        // Mark session as completed
        $pdo->prepare("UPDATE rfid_binding_sessions SET status = 'completed', scanned_uid = ?, completed_at = NOW() WHERE id = ?")
            ->execute([$rfidUid, $bindingSession['id']]);

        // Get vehicle info for response
        $vInfo = $pdo->prepare("SELECT v.plate_number, v.vehicle_type, h.name FROM vehicles v LEFT JOIN homeowners h ON v.homeowner_id = h.id WHERE v.id = ?");
        $vInfo->execute([$targetId]);
        $vehicleInfo = $vInfo->fetch();

        // Log the successful binding
        logScan($pdo, $rfidUid, $readerId, $apiKeyId, 'uid_bound', $inputSource, $targetId, $bindingSession['id'], null);

        $pdo->commit();

        exit(json_encode([
            'success' => true,
            'scan_result' => 'uid_bound',
            'message' => "RFID tag bound to {$vehicleInfo['plate_number']} ({$vehicleInfo['name']})",
            'data' => [
                'rfid_uid' => $rfidUid,
                'plate_number' => $vehicleInfo['plate_number'],
                'vehicle_type' => $vehicleInfo['vehicle_type'],
                'owner_name' => $vehicleInfo['name']
            ]
        ]));
    }

    // Check 2: Is this UID bound to a vehicle? (Normal access scan)
    $vehicleStmt = $pdo->prepare("
        SELECT v.id, v.plate_number, v.vehicle_type, v.color, v.homeowner_id,
               h.name, h.address, h.contact_number, h.owner_img, h.car_img, h.account_status
        FROM vehicles v
        LEFT JOIN homeowners h ON v.homeowner_id = h.id
        WHERE v.rfid_uid = ? AND v.is_active = 1
    ");
    $vehicleStmt->execute([$rfidUid]);
    $vehicle = $vehicleStmt->fetch();

    if ($vehicle) {
        // Check if homeowner account is active
        if ($vehicle['account_status'] !== 'approved') {
            logScan($pdo, $rfidUid, $readerId, $apiKeyId, 'access_denied', $inputSource, $vehicle['id'], null,
                'Homeowner account not approved');

            $pdo->commit();
            exit(json_encode([
                'success' => false,
                'scan_result' => 'access_denied',
                'message' => 'Access denied - account not approved',
                'data' => [
                    'plate_number' => $vehicle['plate_number'],
                    'name' => $vehicle['name']
                ]
            ]));
        }

        // Determine IN/OUT status by checking last log entry
        $lastLog = $pdo->prepare("SELECT status FROM recent_logs WHERE plate_number = ? ORDER BY created_at DESC LIMIT 1");
        $lastLog->execute([$vehicle['plate_number']]);
        $lastEntry = $lastLog->fetch();
        $newStatus = (!$lastEntry || $lastEntry['status'] === 'OUT') ? 'IN' : 'OUT';

        // Insert into recent_logs (this is what the guard panel reads)
        $pdo->prepare("INSERT INTO recent_logs (plate_number, rfid_uid, status, log_time) VALUES (?, ?, ?, CURTIME())")
            ->execute([$vehicle['plate_number'], $rfidUid, $newStatus]);

        // Log the access scan
        logScan($pdo, $rfidUid, $readerId, $apiKeyId, 'access_granted', $inputSource, $vehicle['id'], null, null);

        $pdo->commit();

        exit(json_encode([
            'success' => true,
            'scan_result' => 'access_granted',
            'message' => "Vehicle {$vehicle['plate_number']} - $newStatus",
            'data' => [
                'rfid_uid' => $rfidUid,
                'plate_number' => $vehicle['plate_number'],
                'vehicle_type' => $vehicle['vehicle_type'],
                'color' => $vehicle['color'],
                'name' => $vehicle['name'],
                'address' => $vehicle['address'],
                'contact' => $vehicle['contact_number'],
                'owner_img' => $vehicle['owner_img'],
                'car_img' => $vehicle['car_img'],
                'status' => $newStatus,
                'direction' => $newStatus === 'IN' ? 'Entering' : 'Exiting'
            ]
        ]));
    }

    // Check 3: Unknown RFID UID
    logScan($pdo, $rfidUid, $readerId, $apiKeyId, 'unknown_uid', $inputSource, null, null, 'No vehicle bound to this UID');

    $pdo->commit();

    exit(json_encode([
        'success' => false,
        'scan_result' => 'unknown_uid',
        'message' => 'Unknown RFID tag - not bound to any vehicle'
    ]));

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[RFID_SCAN] Database error: ' . $e->getMessage());
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Database error']));
}

/**
 * Log an RFID scan to the rfid_scan_log table
 */
function logScan($pdo, $rfidUid, $readerId, $apiKeyId, $scanResult, $inputSource, $vehicleId, $bindingSessionId, $errorMessage) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO rfid_scan_log 
            (rfid_uid, reader_id, api_key_id, scan_result, input_source, vehicle_id, binding_session_id, error_message, ip_address, scanned_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $rfidUid,
            $readerId ?: null,
            $apiKeyId,
            $scanResult,
            $inputSource,
            $vehicleId,
            $bindingSessionId,
            $errorMessage,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log('[RFID_SCAN] Failed to log scan: ' . $e->getMessage());
    }
}
