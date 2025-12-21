<?php
/**
 * RFID SIMULATOR DIAGNOSTIC TOOL
 * Place this file in your admin folder and access it directly
 * Example: http://localhost/Vehiscan-RFID/admin/RFID_DIAGNOSTIC.php
 */

echo "<h1>🔍 RFID Simulator Diagnostic</h1>";
echo "<pre>";

require_once __DIR__ . '/../../db.php';

echo "\n=== DATABASE CONNECTION ===\n";
try {
    $pdo->query("SELECT 1");
    echo "✅ Database connected successfully\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

echo "\n=== TABLE STRUCTURE CHECK ===\n";

// Check recent_logs table
try {
    $stmt = $pdo->query("DESCRIBE recent_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ recent_logs table exists\n";
    echo "   Columns: " . implode(", ", $columns) . "\n";
    
    if (!in_array('log_time', $columns)) {
        echo "   ⚠️  WARNING: 'log_time' column not found!\n";
    }
    if (!in_array('created_at', $columns)) {
        echo "   ℹ️  INFO: 'created_at' column not found (this is OK)\n";
    }
} catch (Exception $e) {
    echo "❌ recent_logs table error: " . $e->getMessage() . "\n";
}

// Check rfid_simulator table
try {
    $stmt = $pdo->query("DESCRIBE rfid_simulator");
    echo "✅ rfid_simulator table exists\n";
} catch (Exception $e) {
    echo "❌ rfid_simulator table does NOT exist: " . $e->getMessage() . "\n";
}

// Check homeowners table
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM homeowners");
    $count = $stmt->fetchColumn();
    echo "✅ homeowners table exists ($count homeowners)\n";
} catch (Exception $e) {
    echo "❌ homeowners table error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST INSERT ===\n";

// Try to insert a test log
try {
    $testPlate = 'TEST-' . time();
    
    // First, get a real plate from homeowners
    $stmt = $pdo->query("SELECT plate_number FROM homeowners LIMIT 1");
    $realPlate = $stmt->fetchColumn();
    
    if ($realPlate) {
        echo "Using real plate: $realPlate\n";
        
        // Try insert
        $insertStmt = $pdo->prepare("INSERT INTO recent_logs (plate_number, status, log_time) VALUES (?, ?, NOW())");
        $success = $insertStmt->execute([$realPlate, 'ALLOWED']);
        
        if ($success) {
            echo "✅ Successfully inserted test log!\n";
            $insertId = $pdo->lastInsertId();
            echo "   Insert ID: $insertId\n";
            
            // Verify it appears
            $verifyStmt = $pdo->prepare("SELECT * FROM recent_logs WHERE id = ?");
            $verifyStmt->execute([$insertId]);
            $row = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                echo "✅ Log entry verified:\n";
                echo "   " . print_r($row, true) . "\n";
                
                // Clean up test entry
                $pdo->prepare("DELETE FROM recent_logs WHERE id = ?")->execute([$insertId]);
                echo "✅ Test entry cleaned up\n";
            }
        } else {
            echo "❌ Insert failed\n";
        }
    } else {
        echo "⚠️  No homeowners found in database\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test insert failed: " . $e->getMessage() . "\n";
}

echo "\n=== FETCH LOGS SIMULATION ===\n";

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
    
    if (count($logs) > 0) {
        echo "✅ Found " . count($logs) . " recent logs:\n";
        foreach ($logs as $log) {
            echo "   - {$log['time']} | {$log['plate_number']} | {$log['status']} | " . ($log['name'] ?? 'Unknown') . "\n";
        }
    } else {
        echo "ℹ️  No logs in database yet\n";
    }
} catch (Exception $e) {
    echo "❌ Fetch logs failed: " . $e->getMessage() . "\n";
}

echo "\n=== FILE CHECKS ===\n";

$files = [
    'simulate_rfid_scan.php' => __DIR__ . '/simulate_rfid_scan.php',
    'get_recent_simulations.php' => __DIR__ . '/get_recent_simulations.php',
    'admin_panel.js' => __DIR__ . '/admin_panel.js',
    'fetch_simulator.php' => __DIR__ . '/fetch/fetch_simulator.php'
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "✅ $name exists\n";
    } else {
        echo "❌ $name NOT FOUND at: $path\n";
    }
}

echo "\n=== SESSION CHECK ===\n";
session_start();
if (isset($_SESSION['role'])) {
    echo "✅ Session active\n";
    echo "   Role: {$_SESSION['role']}\n";
    echo "   Username: " . ($_SESSION['username'] ?? 'N/A') . "\n";
} else {
    echo "⚠️  No active session\n";
}

echo "\n=== RECOMMENDATIONS ===\n";
echo "1. If tables are missing, run feature_implementation.php\n";
echo "2. If insert works but guard panel doesn't show logs, check fetch_logs.php path\n";
echo "3. If RFID simulator button is grey, check browser console for JavaScript errors\n";
echo "4. Verify admin_panel.js has been updated with the latest version\n";

echo "\n</pre>";
?>

<hr>
<h2>🧪 Manual RFID Scan Test</h2>
<form method="POST" action="simulate_rfid_scan.php" target="_blank">
    <label>Select Plate:</label>
    <select name="plate_number" required>
        <?php
        try {
            $stmt = $pdo->query("SELECT plate_number, name FROM homeowners ORDER BY name");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<option value='{$row['plate_number']}'>{$row['plate_number']} - {$row['name']}</option>";
            }
        } catch (Exception $e) {
            echo "<option>Error loading homeowners</option>";
        }
        ?>
    </select>
    <button type="submit">🧪 Test Scan</button>
</form>

<style>
    body { font-family: monospace; padding: 20px; background: #f5f5f5; }
    h1, h2 { color: #2c3e50; }
    form { background: white; padding: 20px; border-radius: 8px; margin-top: 20px; }
    select, button { padding: 10px; margin: 5px; font-size: 16px; }
    button { background: #3498db; color: white; border: none; border-radius: 6px; cursor: pointer; }
    button:hover { background: #2980b9; }
</style>
