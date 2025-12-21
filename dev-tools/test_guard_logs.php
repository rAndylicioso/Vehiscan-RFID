<?php
/**
 * TEST SCRIPT FOR GUARD LOGS (moved)
 */

echo "<h1>🔍 Guard Logs Test (moved)</h1><pre>";

require_once __DIR__ . '/../../db.php';

echo "\n=== DATABASE CONNECTION ===\n";
try {
    $pdo->query("SELECT 1");
    echo "✅ Connected\n";
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
    exit;
}

echo "\n=== RECENT_LOGS TABLE ===\n";
try {
    $stmt = $pdo->query("SELECT * FROM recent_logs ORDER BY log_time DESC LIMIT 5");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($logs) . " logs:\n\n";
    foreach ($logs as $log) {
        echo "LOG_ID: {$log['log_id']}\n";
        echo "  Plate: {$log['plate_number']}\n";
        echo "  Status: {$log['status']}\n";
        echo "  Time: {$log['log_time']}\n";
        echo "  ---\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== JOINED DATA (Like Guard Panel Uses) ===\n";
try {
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(r.log_time, '%H:%i:%s') AS time,
            r.plate_number, 
            r.status,
            h.name
        FROM recent_logs r
        LEFT JOIN homeowners h ON r.plate_number = h.plate_number
        ORDER BY r.log_time DESC
        LIMIT 5
    ");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($logs) . " logs:\n\n";
    foreach ($logs as $log) {
        echo "Time: {$log['time']}\n";
        echo "  Plate: {$log['plate_number']}\n";
        echo "  Status: {$log['status']}\n";
        echo "  Owner: " . ($log['name'] ?? 'Unknown') . "\n";
        echo "  ---\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FETCH_LOGS.PHP PATH CHECK ===\n";
$fetchLogsPath = __DIR__ . '/fetch_logs.php';
if (file_exists($fetchLogsPath)) {
    echo "✅ fetch_logs.php exists at: $fetchLogsPath\n";
} else {
    echo "❌ fetch_logs.php NOT FOUND at: $fetchLogsPath\n";
}

echo "\n=== GUARD_SIDE.JS PATH CHECK ===\n";
$guardJsPath = __DIR__ . '/../js/guard_side.js';
if (file_exists($guardJsPath)) {
    echo "✅ guard_side.js exists at: $guardJsPath\n";
    
    $jsContent = file_get_contents($guardJsPath);
    if (strpos($jsContent, '../pages/fetch_logs.php') !== false) {
        echo "✅ guard_side.js has correct endpoint\n";
    } else {
        echo "❌ guard_side.js has WRONG endpoint!\n";
        echo "   Looking for: '../pages/fetch_logs.php'\n";
    }
} else {
    echo "❌ guard_side.js NOT FOUND at: $guardJsPath\n";
}

echo "\n</pre>";

echo "<h2>🧪 Test Fetch Logs Directly</h2>";
echo "<button onclick=\"testFetch()\">Test Fetch</button>";
echo "<pre id=\"result\"></pre>";

echo "<script>
async function testFetch() {
    const result = document.getElementById('result');
    result.textContent = 'Loading...';
    
    try {
        const res = await fetch('fetch_logs.php?t=' + Date.now());
        const text = await res.text();
        result.textContent = 'Response:\n' + text;
        
        try {
            const json = JSON.parse(text);
            result.textContent = 'JSON Response:\n' + JSON.stringify(json, null, 2);
        } catch (e) {
            result.textContent = 'Not JSON. Raw response:\n' + text;
        }
    } catch (err) {
        result.textContent = 'Error: ' + err.message;
    }
}
</script>";
?>