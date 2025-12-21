<?php
require_once __DIR__ . '/../../guard/includes/session_guard.php';
require_once __DIR__ . '/../../db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guard') {
    echo "Please login as guard first!";
    exit;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Guard API Test (moved)</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .test { margin: 20px 0; padding: 15px; background: #2d2d2d; border-left: 4px solid #4ec9b0; }
        .success { border-left-color: #4ec9b0; }
        .error { border-left-color: #f48771; }
        pre { background: #1e1e1e; padding: 10px; overflow-x: auto; }
        button { padding: 10px 20px; background: #0e639c; color: white; border: none; cursor: pointer; }
        button:hover { background: #1177bb; }
    </style>
</head>
<body>
    <h1>🧪 Guard Panel API Test (moved to _testing)</h1>
    
    <div class="test">
        <h3>Test 1: Fetch Logs</h3>
        <button onclick="testFetchLogs()">Test fetch_logs.php</button>
        <pre id="logs-result">Click button to test...</pre>
    </div>
    
    <div class="test">
        <h3>Test 2: Fetch Homeowners</h3>
        <button onclick="testFetchHomeowners()">Test fetch_homeowners.php</button>
        <pre id="homeowners-result">Click button to test...</pre>
    </div>
    
    <div class="test">
        <h3>Test 3: Check Recent Logs in Database</h3>
        <?php
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM recent_logs");
            $count = $stmt->fetchColumn();
            echo "<p style='color: #4ec9b0;'>✓ Found $count logs in database</p>";
            
            $stmt = $pdo->query("SELECT * FROM recent_logs ORDER BY created_at DESC LIMIT 5");
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre>" . json_encode($logs, JSON_PRETTY_PRINT) . "</pre>";
        } catch (Exception $e) {
            echo "<p style='color: #f48771;'>❌ Error: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="test">
        <h3>Test 4: Check Homeowners in Database</h3>
        <?php
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM homeowners");
            $count = $stmt->fetchColumn();
            echo "<p style='color: #4ec9b0;'>✓ Found $count homeowners in database</p>";
            
            $stmt = $pdo->query("SELECT name, plate_number, owner_img, car_img FROM homeowners LIMIT 3");
            $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre>" . json_encode($owners, JSON_PRETTY_PRINT) . "</pre>";
        } catch (Exception $e) {
            echo "<p style='color: #f48771;'>❌ Error: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <script>
    async function testFetchLogs() {
        const result = document.getElementById('logs-result');
        result.textContent = 'Testing...';
        
        try {
            const res = await fetch('../../guard/pages/fetch_logs.php?t=' + Date.now());
            const data = await res.json();
            result.textContent = JSON.stringify(data, null, 2);
            result.parentElement.className = 'test success';
        } catch (err) {
            result.textContent = 'ERROR: ' + err.message;
            result.parentElement.className = 'test error';
        }
    }
    
    async function testFetchHomeowners() {
        const result = document.getElementById('homeowners-result');
        result.textContent = 'Testing...';
        
        try {
            const res = await fetch('../../guard/pages/fetch_homeowners.php?t=' + Date.now());
            const data = await res.json();
            result.textContent = JSON.stringify(data, null, 2);
            result.parentElement.className = 'test success';
        } catch (err) {
            result.textContent = 'ERROR: ' + err.message;
            result.parentElement.className = 'test error';
        }
    }
    </script>
</body>
</html>
