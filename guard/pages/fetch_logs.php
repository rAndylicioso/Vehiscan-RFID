<?php
// guard/pages/fetch_logs.php
// FIXED: Correct path to session file
require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../db.php';

// Security: Only guards can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guard') {
    error_log('[FETCH_LOGS] Unauthorized access attempt - Role: ' . ($_SESSION['role'] ?? 'none'));
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized']));
}

header('Content-Type: application/json');

try {
    // Query recent logs with homeowner data and visitor pass info
    $stmt = $pdo->query("
        SELECT 
            r.log_id,
            DATE_FORMAT(r.log_time, '%H:%i:%s') AS time,
            DATE_FORMAT(r.log_time, '%Y-%m-%d %H:%i:%s') AS log_time_raw,
            r.plate_number, 
            r.status,
            h.vehicle_type, 
            h.color,
            -- Ensure car_img points to uploads/vehicles when only filename stored
            CASE
                WHEN h.car_img IS NULL OR h.car_img = '' THEN NULL
                WHEN h.car_img LIKE 'uploads/%' THEN h.car_img
                WHEN h.car_img LIKE 'vehicles/%' THEN CONCAT('uploads/', h.car_img)
                ELSE CONCAT('uploads/vehicles/', h.car_img)
            END AS car_img,
            h.name, 
            h.address, 
            h.contact_number AS contact, 
            -- Ensure owner_img points to uploads/homeowners when only filename stored
            CASE
                WHEN h.owner_img IS NULL OR h.owner_img = '' THEN NULL
                WHEN h.owner_img LIKE 'uploads/%' THEN h.owner_img
                WHEN h.owner_img LIKE 'homeowners/%' THEN CONCAT('uploads/', h.owner_img)
                ELSE CONCAT('uploads/homeowners/', h.owner_img)
            END AS owner_img,
            -- Visitor pass information
            vp.id AS visitor_pass_id,
            vp.visitor_name,
            vp.purpose AS visitor_purpose
        FROM recent_logs r
        LEFT JOIN homeowners h ON r.plate_number = h.plate_number
        LEFT JOIN visitor_passes vp ON r.plate_number = vp.visitor_plate 
            AND vp.status = 'active'
        ORDER BY r.created_at DESC, r.log_id DESC
        LIMIT 10
    ");
    
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log('[FETCH_LOGS] Successfully fetched ' . count($rows) . ' logs');
    // Build absolute site base for images
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'], 3), '/');
    $siteBase = $scheme . '://' . $host . ($basePath === '' ? '' : $basePath);

    foreach ($rows as &$r) {
        if (!empty($r['owner_img'])) $r['owner_img_url'] = $siteBase . '/' . ltrim($r['owner_img'], '/');
        else $r['owner_img_url'] = null;
        if (!empty($r['car_img'])) $r['car_img_url'] = $siteBase . '/' . ltrim($r['car_img'], '/');
        else $r['car_img_url'] = null;
    }

    echo json_encode($rows);
    
} catch (PDOException $e) {
    error_log('[FETCH_LOGS ERROR] Database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}