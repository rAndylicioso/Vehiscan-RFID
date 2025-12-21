<?php
// Debug script to check logs loading (moved)
require_once __DIR__ . '/../../guard/includes/session_guard.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Logs Debug (moved)</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #0f0; border-radius: 5px; }
        .ok { color: #0f0; }
        .error { color: #f00; }
        .info { color: #ff0; }
        pre { background: #000; padding: 10px; overflow-x: auto; }
        button { background: #0f0; color: #000; border: none; padding: 10px 20px; cursor: pointer; font-weight: bold; margin: 5px; }
        button:hover { background: #0a0; }
    </style>
</head>
<body>
    <h1>🔍 Guard Panel Debug Console (moved to _testing)</h1>
    
    <div class="section">
        <h2>1. Session Check</h2>
        <?php
        echo "<p class='ok'>✓ Session active: " . session_id() . "</p>";
        echo "<p class='ok'>✓ Session name: " . session_name() . "</p>";
        echo "<p class='ok'>✓ Role: " . ($_SESSION['role'] ?? 'NOT SET') . "</p>";
        echo "<p class='ok'>✓ Guard ID: " . ($_SESSION['guard_id'] ?? 'NOT SET') . "</p>";
        echo "<p class='ok'>✓ Username: " . ($_SESSION['username'] ?? 'NOT SET') . "</p>";
        
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guard') {
            echo "<p class='error'>✗ ERROR: Not logged in as guard!</p>";
            echo "<p class='info'>→ You need to log in at: <a href='../../auth/login.php' style='color:#0ff'>../../auth/login.php</a></p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>2. Database Connection</h2>
        <?php
        try {
            $test = $pdo->query("SELECT 1");
            echo "<p class='ok'>✓ Database connected</p>";
        } catch (Exception $e) {
            echo "<p class='error'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>3. Recent Logs Query</h2>
        <?php
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM recent_logs");
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p class='ok'>✓ Total logs in database: " . $count['total'] . "</p>";
            
            if ($count['total'] > 0) {
                $stmt = $pdo->query("
                    SELECT 
                        r.log_id,
                        r.log_time,
                        r.plate_number,
                        r.status,
                        h.name,
                        h.vehicle_type
                    FROM recent_logs r
                    LEFT JOIN homeowners h ON r.plate_number = h.plate_number
                    ORDER BY r.log_id DESC
                    LIMIT 5
                ");
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<p class='ok'>✓ Sample logs (latest 5):</p>";
                echo "<pre>" . json_encode($logs, JSON_PRETTY_PRINT) . "</pre>";
            } else {
                echo "<p class='info'>→ No logs in database yet. The logs table is empty.</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>✗ Query error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>4. Fetch Logs API Test</h2>
        <button onclick="testFetchAPI()">Test fetch_logs.php API</button>
        <div id="apiResult"></div>
    </div>

    <div class="section">
        <h2>5. JavaScript Console Test</h2>
        <p class='info'>Open browser DevTools (F12) and check console for messages.</p>
        <button onclick="testConsole()">Test Console Logging</button>
        <button onclick="testLoadLogs()">Simulate loadLogs()</button>
    </div>

    <div class="section">
        <h2>6. Element Check</h2>
        <button onclick="checkElements()">Check Page Elements</button>
        <div id="elementResult"></div>
    </div>

    <script>
        // debug logs are preserved here, moved file
        async function testFetchAPI() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = '<p style="color:#ff0">⏳ Fetching...</p>';
            
            try {
                const response = await fetch('fetch_logs.php');
                const data = await response.json();
                if (response.ok) {
                    resultDiv.innerHTML = `
                        <p class="ok">✓ API Response OK (${response.status})</p>
                        <p class="ok">✓ Logs count: ${Array.isArray(data) ? data.length : 'NOT AN ARRAY'}</p>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <p class="error">✗ API Error (${response.status})</p>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    `;
                }
            } catch (err) {
                resultDiv.innerHTML = `<p class="error">✗ Network error: ${err.message}</p>`;
            }
        }
        
        function testConsole() {
            alert('Check the console (F12) for test messages!');
        }
        
        async function testLoadLogs() {
            try {
                const response = await fetch('fetch_logs.php');
                const logs = await response.json();
                console.log('[DEBUG] Fetched logs:', logs);
            } catch (err) {
                console.error('[DEBUG] loadLogs simulation error:', err);
            }
        }
        
        function checkElements() {
            const resultDiv = document.getElementById('elementResult');
            const elementsToCheck = [
                'logsContainer',
                'filterIn',
                'filterOut',
                'filterVisitors',
                'user-dropdown',
                'signOutBtn'
            ];
            
            let html = '<pre>';
            elementsToCheck.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    const styles = window.getComputedStyle(el);
                    html += `✓ #${id} EXISTS\n`;
                    html += `  display: ${styles.display}\n`;
                    html += `  visibility: ${styles.visibility}\n`;
                    html += `  opacity: ${styles.opacity}\n`;
                    html += `  position: ${styles.position}\n\n`;
                } else {
                    html += `✗ #${id} NOT FOUND\n\n`;
                }
            });
            html += '</pre>';
            
            resultDiv.innerHTML = html;
        }
    </script>

    <div class="section">
        <h2>7. Quick Actions</h2>
        <button onclick="location.href='../../guard/guard_side.php'">← Back to Guard Panel</button>
        <button onclick="location.reload()">🔄 Reload Debug</button>
    </div>
</body>
</html>
