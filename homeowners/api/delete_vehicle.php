<?php
/**
 * Delete (deactivate) vehicle
 */
require_once __DIR__ . '/../../includes/security_headers.php';
require_once __DIR__ . '/../../includes/session_homeowner.php';
require_once __DIR__ . '/../../includes/request_method_helper.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['homeowner_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

requireRequestMethod('POST');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $vehicleId = isset($data['vehicle_id']) ? (int)$data['vehicle_id'] : 0;

    // Validate CSRF token
    $csrfToken = $data['csrf_token'] ?? '';
    if (!InputSanitizer::validateCsrf((string)$csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit();
    }

    if ($vehicleId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Vehicle ID required']);
        exit();
    }

    $confirmation = strtoupper(trim((string)($data['confirmation'] ?? '')));
    if ($confirmation !== 'DELETE') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Confirmation text is required']);
        exit();
    }

    $vehicleColumns = [];
    try {
        $vehicleColumns = $pdo->query("SHOW COLUMNS FROM vehicles")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $vehicleColumns = [];
    }
    $vehicleIdColumn = in_array('id', $vehicleColumns, true) ? 'id' : 'vehicle_id';
    $hasVehicleImageColumn = in_array('vehicle_img', $vehicleColumns, true);

    $homeownerColumns = [];
    try {
        $homeownerColumns = $pdo->query("SHOW COLUMNS FROM homeowners")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $homeownerColumns = [];
    }
    $hasHomeownerCarImageColumn = in_array('car_img', $homeownerColumns, true);

    // Verify ownership and check if it's the only vehicle
    $stmt = $pdo->prepare("\n        SELECT COUNT(*) as total
        FROM vehicles
        WHERE homeowner_id = ? AND is_active = TRUE
    ");
    $stmt->execute([$_SESSION['homeowner_id']]);
    $result = $stmt->fetch();

    if (($result['total'] ?? 0) <= 1) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'Cannot delete your only vehicle. Please add another vehicle first.']);
        exit();
    }

    // Validate target vehicle belongs to homeowner and is active
    $stmt = $pdo->prepare("\n        SELECT {$vehicleIdColumn}
        FROM vehicles
        WHERE {$vehicleIdColumn} = ? AND homeowner_id = ? AND is_active = TRUE
        LIMIT 1
    ");
    $stmt->execute([$vehicleId, $_SESSION['homeowner_id']]);
    if (!$stmt->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Vehicle not found or access denied']);
        exit();
    }

    $pdo->beginTransaction();

    // Soft delete and clear primary flag on the removed vehicle.
    $stmt = $pdo->prepare("\n        UPDATE vehicles
        SET is_active = FALSE, is_primary = FALSE
        WHERE {$vehicleIdColumn} = ? AND homeowner_id = ?
    ");
    $stmt->execute([$vehicleId, $_SESSION['homeowner_id']]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Vehicle not found or access denied']);
        exit();
    }

    // Keep one active primary vehicle for consistent behavior.
    $stmt = $pdo->prepare("\n        SELECT {$vehicleIdColumn}
        FROM vehicles
        WHERE homeowner_id = ? AND is_active = TRUE AND is_primary = TRUE
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['homeowner_id']]);
    $currentPrimaryId = (int)($stmt->fetchColumn() ?: 0);

    if ($currentPrimaryId <= 0) {
        $stmt = $pdo->prepare("\n            SELECT {$vehicleIdColumn}
            FROM vehicles
            WHERE homeowner_id = ? AND is_active = TRUE
            ORDER BY registered_at DESC, {$vehicleIdColumn} DESC
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['homeowner_id']]);
        $newPrimaryId = (int)($stmt->fetchColumn() ?: 0);

        if ($newPrimaryId > 0) {
            $pdo->prepare("UPDATE vehicles SET is_primary = FALSE WHERE homeowner_id = ? AND is_active = TRUE")
                ->execute([$_SESSION['homeowner_id']]);
            $pdo->prepare("UPDATE vehicles SET is_primary = TRUE WHERE {$vehicleIdColumn} = ? AND homeowner_id = ?")
                ->execute([$newPrimaryId, $_SESSION['homeowner_id']]);
            $currentPrimaryId = $newPrimaryId;
        }
    }

    if ($hasVehicleImageColumn && $hasHomeownerCarImageColumn && $currentPrimaryId > 0) {
        $stmt = $pdo->prepare("SELECT vehicle_img FROM vehicles WHERE {$vehicleIdColumn} = ? AND homeowner_id = ? LIMIT 1");
        $stmt->execute([$currentPrimaryId, $_SESSION['homeowner_id']]);
        $primaryVehicleImg = $stmt->fetchColumn();
        if (!empty($primaryVehicleImg)) {
            $pdo->prepare("UPDATE homeowners SET car_img = ? WHERE id = ?")
                ->execute([$primaryVehicleImg, $_SESSION['homeowner_id']]);
        } else {
            $pdo->prepare("UPDATE homeowners SET car_img = NULL WHERE id = ?")
                ->execute([$_SESSION['homeowner_id']]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Vehicle removed successfully'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Delete vehicle error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete vehicle. Please try again later.'
    ]);
}
