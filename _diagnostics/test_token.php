<?php
// Simple test - no includes, direct connection
$token = $_GET['token'] ?? '';

if (!$token) {
    die("<h1>❌ No token provided</h1><p>Add ?token=YOUR_TOKEN to the URL</p>");
}

echo "<h1>Testing Token: " . htmlspecialchars($token) . "</h1>";

// Direct database connection
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=vehiscan_vdp;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<p>✅ Database connected</p>";
    
    // Check if token exists
    $stmt = $pdo->prepare("SELECT * FROM visitor_passes WHERE qr_token = ?");
    $stmt->execute([$token]);
    $pass = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pass) {
        echo "<h2>✅ Visitor Pass Found!</h2>";
        echo "<table border='1' cellpadding='10'>";
        foreach ($pass as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
        }
        echo "</table>";
        
        // Check if homeowner exists
        if ($pass['homeowner_id']) {
            echo "<h3>Checking Homeowner...</h3>";
            $stmt2 = $pdo->prepare("SELECT * FROM homeowners WHERE id = ?");
            $stmt2->execute([$pass['homeowner_id']]);
            $homeowner = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            if ($homeowner) {
                echo "<p>✅ Homeowner found: " . htmlspecialchars($homeowner['name']) . "</p>";
            } else {
                echo "<p>❌ Homeowner NOT found (ID: {$pass['homeowner_id']})</p>";
                echo "<p><strong>THIS IS THE PROBLEM!</strong> The visitor pass references a homeowner that doesn't exist.</p>";
            }
        }
    } else {
        echo "<h2>❌ No visitor pass found with this token</h2>";
        
        // Show all tokens for reference
        echo "<h3>Available tokens in database:</h3>";
        $stmt = $pdo->query("SELECT id, visitor_name, LEFT(qr_token, 30) as token_preview, status FROM visitor_passes ORDER BY id DESC LIMIT 10");
        echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Visitor Name</th><th>Token Preview</th><th>Status</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>{$row['id']}</td><td>{$row['visitor_name']}</td><td>{$row['token_preview']}...</td><td>{$row['status']}</td></tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<h2>❌ Database Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
