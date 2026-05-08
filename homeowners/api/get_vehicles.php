<?php
/**
 * Get all vehicles for logged-in homeowner
 * Uses the canonical 'vehicles' table
 */
require_once __DIR__ . '/../../includes/security_headers.php';
require_once __DIR__ . '/../../includes/request_method_helper.php';

header('Content-Type: application/json');

requireRequestMethod('GET');

require_once __DIR__ . '/../../includes/session_homeowner.php';
require_once __DIR__ . '/../../db.php';

if (!isset($_SESSION['homeowner_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    $vehicleColumns = [];
    try {
        $vehicleColumns = $pdo->query("SHOW COLUMNS FROM vehicles")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $vehicleColumns = [];
    }
    $hasVehicleImageColumn = in_array('vehicle_img', $vehicleColumns, true);

    $homeownerColumns = [];
    try {
        $homeownerColumns = $pdo->query("SHOW COLUMNS FROM homeowners")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $homeownerColumns = [];
    }
    $hasHomeownerCarImageColumn = in_array('car_img', $homeownerColumns, true);

    if ($hasVehicleImageColumn && $hasHomeownerCarImageColumn) {
        $vehicleImageSelect = "COALESCE(NULLIF(v.vehicle_img, ''), CASE WHEN v.is_primary = TRUE THEN h.car_img ELSE NULL END) AS vehicle_img,";
    } elseif ($hasVehicleImageColumn) {
        $vehicleImageSelect = 'v.vehicle_img AS vehicle_img,';
    } elseif ($hasHomeownerCarImageColumn) {
        $vehicleImageSelect = "CASE WHEN v.is_primary = TRUE THEN h.car_img ELSE NULL END AS vehicle_img,";
    } else {
        $vehicleImageSelect = "NULL AS vehicle_img,";
    }

    $homeownerFallbackSelect = $hasHomeownerCarImageColumn ? 'h.car_img AS homeowner_car_img,' : "NULL AS homeowner_car_img,";
    $homeownerJoin = $hasHomeownerCarImageColumn ? 'LEFT JOIN homeowners h ON h.id = v.homeowner_id' : '';

    // Get all vehicles for this homeowner from the canonical vehicles table
    $stmt = $pdo->prepare("
        SELECT 
            v.id,
            v.vehicle_type,
            v.color,
            v.plate_number,
            {$vehicleImageSelect}
            {$homeownerFallbackSelect}
            v.brand,
            v.model,
            v.year,
            v.is_primary,
            v.is_active,
            v.rfid_uid,
            v.registered_at as created_at
        FROM vehicles v
        {$homeownerJoin}
        WHERE v.homeowner_id = ? AND v.is_active = TRUE
        ORDER BY v.is_primary DESC, v.registered_at DESC
    ");
    
    $stmt->execute([$_SESSION['homeowner_id']]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($hasHomeownerCarImageColumn && count($vehicles) === 1) {
        $legacyImage = $vehicles[0]['homeowner_car_img'] ?? null;
        if (!empty($legacyImage) && empty($vehicles[0]['vehicle_img'])) {
            $vehicles[0]['vehicle_img'] = $legacyImage;
        }
    }

    foreach ($vehicles as &$vehicle) {
        unset($vehicle['homeowner_car_img']);
    }
    unset($vehicle);
    
    echo json_encode([
        'success' => true,
        'vehicles' => $vehicles
    ]);
    
} catch (Exception $e) {
    error_log("Get vehicles error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch vehicles. Please try again later.'
    ]);
}
