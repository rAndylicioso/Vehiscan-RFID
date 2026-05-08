<?php
require_once __DIR__ . '/../../includes/security_headers.php';
require_once __DIR__ . '/../../includes/request_method_helper.php';

header('Content-Type: application/json');

requireRequestMethod('GET');

require_once __DIR__ . '/../../includes/session_homeowner.php';
require_once __DIR__ . '/../../db.php';

try {
    $scanLogsExistsStmt = $pdo->query("SHOW TABLES LIKE 'visitor_pass_scan_logs'");
    $hasScanLogs = (bool)$scanLogsExistsStmt->fetchColumn();

    $scanJoin = '';
    $scanFields = "0 AS scan_count, NULL AS first_scanned_at, NULL AS last_scanned_at";
    $usedWhenClause = '';

    if ($hasScanLogs) {
        $scanJoin = "
            LEFT JOIN (
                SELECT visitor_pass_id, COUNT(*) AS scan_count, MIN(scanned_at) AS first_scanned_at, MAX(scanned_at) AS last_scanned_at
                FROM visitor_pass_scan_logs
                GROUP BY visitor_pass_id
            ) vpsl ON vpsl.visitor_pass_id = vp.id
        ";
        $scanFields = "COALESCE(vpsl.scan_count, 0) AS scan_count, vpsl.first_scanned_at, vpsl.last_scanned_at";
        $usedWhenClause = "WHEN vp.status IN ('active', 'approved') AND COALESCE(vpsl.scan_count, 0) > 0 THEN 'used'";
    }

    $stmt = $pdo->prepare("\n        SELECT vp.*,\n               {$scanFields},\n               CASE\n                   {$usedWhenClause}\n                   WHEN vp.status IN ('active', 'approved') AND NOW() > vp.valid_until THEN 'expired'\n                   WHEN vp.status IN ('active', 'approved') AND NOW() < vp.valid_from THEN 'upcoming'\n                   ELSE vp.status\n               END AS display_status\n        FROM visitor_passes vp\n        {$scanJoin}\n        WHERE vp.homeowner_id = ?\n        ORDER BY vp.created_at DESC\n    ");
    $stmt->execute([$_SESSION['homeowner_id']]);
    $passes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'passes' => $passes
    ]);
} catch (Exception $e) {
    error_log('Error fetching visitor passes: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load visitor passes'
    ]);
}
