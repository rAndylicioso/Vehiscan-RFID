<?php
/**
 * Test Chart API Endpoint
 * This will help diagnose why charts aren't displaying
 */
require_once __DIR__ . '/../includes/session_admin_unified.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    die('Please login as admin first: <a href="../auth/login.php">Login</a>');
}

require_once __DIR__ . '/../db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chart API Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Chart API Diagnostic Test</h1>
        
        <!-- Session Info -->
        <div class="bg-white rounded-lg border p-4 mb-6">
            <h2 class="font-semibold mb-2">Session Information</h2>
            <div class="text-sm space-y-1">
                <p><strong>User:</strong> <?php echo htmlspecialchars($_SESSION['username'] ?? 'N/A'); ?></p>
                <p><strong>Role:</strong> <?php echo htmlspecialchars($_SESSION['role'] ?? 'N/A'); ?></p>
                <p><strong>Session Name:</strong> <?php echo session_name(); ?></p>
            </div>
        </div>
        
        <!-- Database Check -->
        <div class="bg-white rounded-lg border p-4 mb-6">
            <h2 class="font-semibold mb-2">Database Check</h2>
            <div class="text-sm space-y-1">
                <?php
                try {
                    $total = $pdo->query('SELECT COUNT(*) FROM recent_logs')->fetchColumn();
                    $today = $pdo->query('SELECT COUNT(*) FROM recent_logs WHERE DATE(log_time) = CURDATE()')->fetchColumn();
                    $checkIn = $pdo->query("SELECT COUNT(*) FROM recent_logs WHERE DATE(log_time) = CURDATE() AND status = 'IN'")->fetchColumn();
                    $checkOut = $pdo->query("SELECT COUNT(*) FROM recent_logs WHERE DATE(log_time) = CURDATE() AND status = 'OUT'")->fetchColumn();
                    
                    echo "<p class='text-green-600'>✅ Database connected</p>";
                    echo "<p><strong>Total logs:</strong> $total</p>";
                    echo "<p><strong>Logs today:</strong> $today</p>";
                    echo "<p><strong>Check In:</strong> $checkIn</p>";
                    echo "<p><strong>Check Out:</strong> $checkOut</p>";
                } catch (Exception $e) {
                    echo "<p class='text-red-600'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                ?>
            </div>
        </div>
        
        <!-- API Test Buttons -->
        <div class="bg-white rounded-lg border p-4 mb-6">
            <h2 class="font-semibold mb-3">API Endpoint Test</h2>
            <button onclick="testAPI()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                Test get_weekly_stats.php
            </button>
        </div>
        
        <!-- Results -->
        <div id="results" class="bg-white rounded-lg border p-4 hidden">
            <h2 class="font-semibold mb-2">API Response</h2>
            <pre id="response" class="bg-gray-100 p-3 rounded text-xs overflow-auto max-h-96"></pre>
        </div>
    </div>
    
    <script>
    function testAPI() {
        const resultsDiv = document.getElementById('results');
        const responseDiv = document.getElementById('response');
        
        resultsDiv.classList.remove('hidden');
        responseDiv.textContent = 'Loading...';
        
        fetch('api/get_weekly_stats.php', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(res => {
            console.log('Status:', res.status);
            return res.json();
        })
        .then(data => {
            responseDiv.textContent = JSON.stringify(data, null, 2);
            console.log('Data:', data);
        })
        .catch(err => {
            responseDiv.textContent = 'Error: ' + err.message;
            console.error('Error:', err);
        });
    }
    </script>
</body>
</html>
