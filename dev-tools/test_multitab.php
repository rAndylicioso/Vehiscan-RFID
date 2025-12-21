<?php
require_once __DIR__ . '/includes/session_config.php';

if (!isset($_SESSION['username'])) {
    header('Location: auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Multi-Tab Test - VehiScan</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f4f6f8;
        }
        .test-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .success {
            background: #d5f4e6;
            border-left: 4px solid #2ecc71;
            color: #27ae60;
        }
        .info {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            color: #1976d2;
        }
        .instruction {
            background: #fff9e6;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #f39c12;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin: 10px 5px;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #2980b9;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="test-card">
        <h1>✅ Multi-Tab Support Test</h1>
        
        <div class="status success">
            <strong>✓ Session Active</strong><br>
            User: <?= htmlspecialchars($_SESSION['username']) ?><br>
            Role: <?= htmlspecialchars($_SESSION['role']) ?><br>
            Last Activity: <?= date('H:i:s', $_SESSION['last_activity']) ?>
        </div>
        
        <div class="status info">
            <strong>Session ID:</strong> <?= session_id() ?><br>
            <strong>Session Status:</strong> Active (Multi-Tab Enabled)
        </div>
        
        <div class="instruction">
            <h3>🧪 How to Test Multi-Tab Support:</h3>
            <ol>
                <li>Keep this tab open</li>
                <li>Open a new tab/window</li>
                <li>Navigate to the same page (Admin or Guard panel)</li>
                <li>Both tabs should work simultaneously!</li>
                <li>Try refreshing both tabs - no session conflicts</li>
            </ol>
        </div>
        
        <h3>Quick Links:</h3>
        <a href="admin/admin_panel.php" class="btn" target="_blank">Open Admin Panel</a>
        <a href="guard/pages/guard_side.php" class="btn" target="_blank">Open Guard Panel</a>
        <a href="javascript:location.reload()" class="btn">Refresh This Page</a>
        
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
            <h4>What Changed:</h4>
            <ul>
                <li>✅ Removed session locking</li>
                <li>✅ Centralized session management</li>
                <li>✅ 30-minute inactivity timeout</li>
                <li>✅ CSRF protection maintained</li>
                <li>✅ Multiple users can access simultaneously</li>
            </ul>
        </div>
        
        <div style="margin-top: 20px; color: #7f8c8d; font-size: 14px;">
            <strong>Note:</strong> Session timeout is set to 30 minutes of inactivity. 
            Your session will remain active as long as you're using the system.
        </div>
    </div>
</body>
</html>