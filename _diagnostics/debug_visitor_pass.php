<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Visitor Pass Debug</h2>";

require_once __DIR__ . '/db.php';

echo "<h3>1. Database Connection</h3>";
echo "Status: " . ($pdo ? "✅ Connected" : "❌ Failed") . "<br>";

echo "<h3>2. Visitor Passes Table Structure</h3>";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM visitor_passes");
    echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}

echo "<h3>3. All Visitor Passes</h3>";
try {
    $stmt = $pdo->query("SELECT id, visitor_name, status, qr_token, valid_from, valid_until, created_at FROM visitor_passes ORDER BY id DESC LIMIT 5");
    echo "<table border='1' cellpadding='5'><tr><th>ID</th><th>Name</th><th>Status</th><th>Token (first 20 chars)</th><th>Valid From</th><th>Valid Until</th><th>Created</th></tr>";
    $count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['visitor_name']}</td>";
        echo "<td><strong>{$row['status']}</strong></td>";
        echo "<td>" . substr($row['qr_token'], 0, 20) . "...</td>";
        echo "<td>{$row['valid_from']}</td>";
        echo "<td>{$row['valid_until']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p>Total: $count passes found</p>";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}

echo "<h3>4. Test Query with Token</h3>";
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    echo "Testing token: <code>$token</code><br><br>";
    
    try {
        $stmt = $pdo->prepare("
            SELECT vp.*, h.name as homeowner_name, h.address as homeowner_address, h.contact
            FROM visitor_passes vp
            JOIN homeowners h ON vp.homeowner_id = h.id
            WHERE vp.qr_token = ?
        ");
        $stmt->execute([$token]);
        $pass = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($pass) {
            echo "✅ Pass found!<br>";
            echo "<pre>";
            print_r($pass);
            echo "</pre>";
        } else {
            echo "❌ No pass found with this token<br>";
            
            // Check if token exists at all
            $stmt2 = $pdo->prepare("SELECT id, visitor_name, status FROM visitor_passes WHERE qr_token = ?");
            $stmt2->execute([$token]);
            $check = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            if ($check) {
                echo "⚠️ Token exists but JOIN failed. Pass data: ";
                echo "<pre>";
                print_r($check);
                echo "</pre>";
            } else {
                echo "❌ Token doesn't exist in database at all";
            }
        }
    } catch (PDOException $e) {
        echo "❌ Query Error: " . $e->getMessage();
    }
} else {
    echo "Add <code>?token=YOUR_TOKEN_HERE</code> to test a specific token";
}
?>
