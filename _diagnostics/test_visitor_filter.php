<?php
require_once __DIR__ . '/db.php';

echo "<h2>Testing Visitor Filter Query</h2>";
echo "<style>table{border-collapse:collapse;margin:20px 0;}th,td{border:1px solid #ddd;padding:8px;}th{background:#f4f4f4;}</style>";

// Check visitor passes
echo "<h3>1. All Visitor Passes</h3>";
$stmt = $pdo->query("SELECT id, visitor_name, visitor_plate, status, valid_from, valid_until, homeowner_id FROM visitor_passes ORDER BY id DESC LIMIT 10");
$passes = $stmt->fetchAll();
echo "<table><tr><th>ID</th><th>Visitor</th><th>Plate</th><th>Status</th><th>Valid From</th><th>Valid Until</th><th>Homeowner ID</th></tr>";
foreach ($passes as $p) {
    echo "<tr><td>{$p['id']}</td><td>{$p['visitor_name']}</td><td>{$p['visitor_plate']}</td><td><strong>{$p['status']}</strong></td><td>{$p['valid_from']}</td><td>{$p['valid_until']}</td><td>{$p['homeowner_id']}</td></tr>";
}
echo "</table>";

// Check active passes
echo "<h3>2. Active Visitor Passes (status='active' AND time valid)</h3>";
$stmt = $pdo->query("
    SELECT vp.*, h.name as homeowner_name
    FROM visitor_passes vp
    JOIN homeowners h ON vp.homeowner_id = h.id
    WHERE vp.status = 'active'
      AND vp.valid_from <= NOW()
      AND vp.valid_until >= NOW()
    ORDER BY vp.created_at DESC
");
$activePasses = $stmt->fetchAll();
echo "<p>Found: " . count($activePasses) . " active passes</p>";
if (!empty($activePasses)) {
    echo "<table><tr><th>ID</th><th>Visitor</th><th>Plate</th><th>Homeowner</th><th>Valid From</th><th>Valid Until</th></tr>";
    foreach ($activePasses as $p) {
        echo "<tr><td>{$p['id']}</td><td>{$p['visitor_name']}</td><td>{$p['visitor_plate']}</td><td>{$p['homeowner_name']}</td><td>{$p['valid_from']}</td><td>{$p['valid_until']}</td></tr>";
    }
    echo "</table>";
}

// Check logs with visitor passes
echo "<h3>3. Recent Logs with Visitor Pass Match</h3>";
$stmt = $pdo->query("
    SELECT 
        r.log_id,
        r.plate_number,
        r.log_time,
        r.status,
        vp.id AS visitor_pass_id,
        vp.visitor_name,
        vp.status as vp_status
    FROM recent_logs r
    LEFT JOIN visitor_passes vp ON r.plate_number = vp.visitor_plate 
        AND vp.status = 'active'
        AND r.log_time BETWEEN vp.valid_from AND vp.valid_until
    ORDER BY r.log_id DESC
    LIMIT 20
");
$logs = $stmt->fetchAll();
echo "<table><tr><th>Log ID</th><th>Plate</th><th>Log Time</th><th>Status</th><th>VP ID</th><th>Visitor Name</th><th>VP Status</th></tr>";
foreach ($logs as $l) {
    $vpMatch = $l['visitor_pass_id'] ? "✅" : "❌";
    echo "<tr><td>{$l['log_id']}</td><td>{$l['plate_number']}</td><td>{$l['log_time']}</td><td>{$l['status']}</td><td>{$vpMatch} {$l['visitor_pass_id']}</td><td>{$l['visitor_name']}</td><td>{$l['vp_status']}</td></tr>";
}
echo "</table>";

echo "<h3>4. Current Time</h3>";
echo "<p>" . date('Y-m-d H:i:s') . "</p>";
?>
