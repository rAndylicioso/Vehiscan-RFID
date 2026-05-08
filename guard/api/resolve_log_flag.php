<?php
require_once __DIR__ . '/../../includes/security_headers.php';
require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../includes/request_method_helper.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

requireRequestMethod('POST');

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0 || ($_SESSION['role'] ?? '') !== 'guard') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$contentType = (string)($_SERVER['CONTENT_TYPE'] ?? '');
$input = [];
if (stripos($contentType, 'application/json') !== false) {
    $decoded = json_decode(file_get_contents('php://input') ?: '{}', true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
} else {
    $input = $_POST;
}

$postedToken = (string)($input['csrf_token'] ?? '');
$sessionToken = (string)($_SESSION['csrf_token'] ?? '');
if ($postedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

$flagId = (int)($input['flag_id'] ?? 0);
$logId = (int)($input['log_id'] ?? 0);
if ($flagId <= 0 || $logId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid resolve request']);
    exit();
}

try {
    $tableExistsStmt = $pdo->query("SHOW TABLES LIKE 'guard_log_flags'");
    $tableExists = (bool)$tableExistsStmt->fetchColumn();
    if (!$tableExists) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS guard_log_flags (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            log_id BIGINT UNSIGNED NOT NULL,
            plate_number VARCHAR(32) NOT NULL,
            flagged_by_user_id INT UNSIGNED NOT NULL,
            reason VARCHAR(255) NULL,
            status ENUM('open', 'resolved') NOT NULL DEFAULT 'open',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolved_at TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_guard_log_flags_log_id (log_id),
            KEY idx_guard_log_flags_plate_number (plate_number),
            KEY idx_guard_log_flags_flagged_by (flagged_by_user_id),
            KEY idx_guard_log_flags_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    $stmt = $pdo->prepare('SELECT id, status FROM guard_log_flags WHERE id = ? AND log_id = ? LIMIT 1');
    $stmt->execute([$flagId, $logId]);
    $flag = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$flag) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Flag entry not found']);
        exit();
    }

    if (($flag['status'] ?? '') !== 'open') {
        echo json_encode(['success' => true, 'message' => 'Flag already resolved']);
        exit();
    }

    $resolve = $pdo->prepare('UPDATE guard_log_flags SET status = "resolved", resolved_at = NOW() WHERE id = ? AND log_id = ?');
    $resolve->execute([$flagId, $logId]);

    echo json_encode([
        'success' => true,
        'message' => 'Flag resolved',
        'data' => [
            'flag_id' => $flagId,
            'log_id' => $logId,
            'status' => 'resolved'
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to resolve flag']);
}
