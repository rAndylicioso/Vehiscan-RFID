<?php
require_once __DIR__ . '/../db.php';

$pdo = $GLOBALS['pdo'];

// Count vehicles
$r = $pdo->query("SELECT COUNT(*) AS total FROM vehicles")->fetch(PDO::FETCH_ASSOC);
echo "Total vehicles: " . $r['total'] . "\n";

$r = $pdo->query("SELECT COUNT(*) AS cnt FROM vehicles WHERE rfid_uid IS NOT NULL AND rfid_uid != ''")->fetch(PDO::FETCH_ASSOC);
echo "RFID bound: " . $r['cnt'] . "\n";

$r = $pdo->query("SELECT COUNT(*) AS cnt FROM vehicles WHERE rfid_uid IS NULL OR rfid_uid = ''")->fetch(PDO::FETCH_ASSOC);
echo "RFID unbound: " . $r['cnt'] . "\n";

// Show some sample vehicles
echo "\nSample vehicles:\n";
$stmt = $pdo->query("SELECT v.id, v.plate_number, v.rfid_uid, v.is_active, h.name FROM vehicles v LEFT JOIN homeowners h ON v.homeowner_id = h.id LIMIT 10");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("  ID=%d, Plate=%s, RFID=%s, Active=%d, Owner=%s\n", 
        $row['id'], $row['plate_number'], $row['rfid_uid'] ?? 'NULL', $row['is_active'], $row['name'] ?? 'Unknown');
}

// Count homeowners
$r = $pdo->query("SELECT COUNT(*) AS cnt FROM homeowners WHERE account_status = 'approved' AND plate_number IS NOT NULL AND plate_number != ''")->fetch(PDO::FETCH_ASSOC);
echo "\nApproved homeowners with plates: " . $r['cnt'] . "\n";
