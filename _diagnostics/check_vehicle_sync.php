<?php
require_once __DIR__ . '/../db.php';
$pdo = $GLOBALS['pdo'];

// Find approved homeowners without a matching vehicle entry
$stmt = $pdo->query("
    SELECT h.id, h.name, h.plate_number, h.vehicle_type, v.id AS vid
    FROM homeowners h
    LEFT JOIN vehicles v ON v.homeowner_id = h.id AND v.plate_number = h.plate_number
    WHERE h.account_status = 'approved'
    ORDER BY h.id DESC
");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $status = $r['vid'] ? 'HAS VEHICLE' : 'NO VEHICLE';
    echo sprintf("  H#%d: %s | Plate=%s | Type=%s | %s\n",
        $r['id'], $r['name'], $r['plate_number'] ?? 'NULL', $r['vehicle_type'] ?? 'NULL', $status);
}
