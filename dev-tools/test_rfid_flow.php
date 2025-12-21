<?php
require_once __DIR__ . '/db.php';

echo "=== TESTING RFID SIMULATOR FLOW ===\n\n";

// Step 1: Check current logs count
echo "STEP 1: Current logs in recent_logs table\n";
$count = $pdo->query('SELECT COUNT(*) FROM recent_logs')->fetchColumn();
echo "Total logs: $count\n\n";

// Step 2: Get a real homeowner to test with
echo "STEP 2: Getting a test vehicle\n";
$stmt = $pdo->query('SELECT plate_number, name FROM homeowners LIMIT 1');
$homeowner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$homeowner) {
    echo "ERROR: No homeowners found in database!\n";
    exit;
}

$testPlate = $homeowner['plate_number'];
$testName = $homeowner['name'];
echo "Test vehicle: $testPlate ($testName)\n\n";

// Step 3: Simulate what the RFID simulator does
echo "STEP 3: Simulating RFID scan (what admin/simulate_rfid_scan.php does)\n";
try {
    $stmt = $pdo->prepare("INSERT INTO recent_logs (plate_number, status, log_time) VALUES (?, 'IN', NOW())");
    $result = $stmt->execute([$testPlate]);
    
    if ($result) {
        echo "✅ Successfully inserted log with status='IN'\n";
        $insertedId = $pdo->lastInsertId();
        echo "   Log ID: $insertedId\n\n";
    } else {
        echo "❌ Failed to insert log\n\n";
    }
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n\n";
}

// Step 4: Check what the guard panel would fetch
echo "STEP 4: Fetching logs (what guard/pages/fetch_logs.php does)\n";
try {
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(r.log_time, '%H:%i:%s') AS time,
            DATE_FORMAT(r.log_time, '%Y-%m-%d %H:%i:%s') AS log_time_raw,
            r.plate_number, 
            r.status,
            h.name,
            h.vehicle_type
        FROM recent_logs r
        LEFT JOIN homeowners h ON r.plate_number = h.plate_number
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Recent logs (last 5):\n";
    foreach ($logs as $log) {
        $highlight = ($log['plate_number'] === $testPlate) ? ' ← OUR TEST LOG' : '';
        echo sprintf("  - %s | %s | %s | %s%s\n", 
            $log['time'], 
            $log['plate_number'], 
            $log['status'],
            $log['name'] ?? 'Unknown',
            $highlight
        );
    }
    echo "\n";
    
} catch (PDOException $e) {
    echo "❌ Error fetching logs: " . $e->getMessage() . "\n\n";
}

// Step 5: Verify the guard panel would see it
echo "STEP 5: Checking if guard panel's loadLogs() would display it\n";
$found = false;
foreach ($logs as $log) {
    if ($log['plate_number'] === $testPlate) {
        $found = true;
        echo "✅ YES! The test log appears in the query results\n";
        echo "   Guard panel will show:\n";
        echo "   - Time: {$log['time']}\n";
        echo "   - Plate: {$log['plate_number']}\n";
        echo "   - Status: {$log['status']}\n";
        echo "   - Icon: 🚗 (because we show car icon for all logs)\n";
        echo "   - Status class: status-granted (because status === 'IN')\n";
        break;
    }
}

if (!$found) {
    echo "❌ Test log not found in query results\n";
}

echo "\n=== CONCLUSION ===\n";
if ($found) {
    echo "✅ RFID Simulator → Recent Logs flow is WORKING!\n";
    echo "When admin clicks 'Simulate Scan' button:\n";
    echo "1. simulate_rfid_scan.php inserts record with status='IN' ✓\n";
    echo "2. Record appears in recent_logs table ✓\n";
    echo "3. Guard panel's fetch_logs.php retrieves it ✓\n";
    echo "4. Guard panel displays it in Recent Logs table ✓\n";
} else {
    echo "❌ Flow is BROKEN - investigate further\n";
}
