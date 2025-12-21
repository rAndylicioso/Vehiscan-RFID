<?php
// Standalone visitor pass viewer - NO security headers to test
require_once __DIR__ . '/../db.php';

$token = $_GET['token'] ?? '';
$error = null;
$pass = null;

if (!$token) {
    $error = 'Invalid Request';
} else {
    try {
        $stmt = $pdo->prepare("
            SELECT vp.*, h.name as homeowner_name, h.address as homeowner_address
            FROM visitor_passes vp
            JOIN homeowners h ON vp.homeowner_id = h.id
            WHERE vp.qr_token = ?
        ");
        $stmt->execute([$token]);
        $pass = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pass) {
            $error = 'Invalid or Expired Visitor Pass';
        }
    } catch (PDOException $e) {
        error_log("Visitor pass view error: " . $e->getMessage());
        $error = 'System Error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test Visitor Pass</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f0f0;
            padding: 20px;
        }
        .container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: green;
            font-size: 24px;
            text-align: center;
        }
        .error {
            color: red;
            font-size: 20px;
            text-align: center;
        }
        .info {
            margin: 10px 0;
            padding: 10px;
            background: #f9f9f9;
            border-left: 3px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="success">✓ No HTTPS Redirect!</h1>
        <p style="text-align: center; color: #666;">This is a test page without security headers</p>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php else: ?>
            <h2>Visitor Pass Details:</h2>
            <div class="info">
                <strong>Visitor:</strong> <?= htmlspecialchars($pass['visitor_name']) ?>
            </div>
            <div class="info">
                <strong>Purpose:</strong> <?= htmlspecialchars($pass['purpose']) ?>
            </div>
            <div class="info">
                <strong>Host:</strong> <?= htmlspecialchars($pass['homeowner_name']) ?>
            </div>
            <div class="info">
                <strong>Status:</strong> <?= htmlspecialchars($pass['status']) ?>
            </div>
        <?php endif; ?>
        
        <hr style="margin: 20px 0;">
        <p style="font-size: 12px; color: #999; text-align: center;">
            If you see this page, the issue is in security_headers.php<br>
            Current URL: <?= htmlspecialchars($_SERVER['REQUEST_URI']) ?><br>
            Host: <?= htmlspecialchars($_SERVER['HTTP_HOST']) ?>
        </p>
    </div>
</body>
</html>
