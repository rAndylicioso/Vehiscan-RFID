<?php
// Test if we can manually insert a log
require_once __DIR__ . '/../db.php';

echo "<h2>Manual Log Insert Test</h2>";

// First check if table exists and its structure
try {
    $columns = $pdo->query("DESCRIBE recent_logs")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Table Structure:</h3>";
    echo "<pre>";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . " - " . $col['Null'] . " - " . $col['Key'] . " - Default: " . $col['Default'] . "\n";
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error checking table: " . $e->getMessage() . "</p>";
}

// Try to insert a test log
try {
    echo "<h3>Attempting to insert test log...</h3>";
    $stmt = $pdo->prepare("INSERT INTO recent_logs (plate_number, status, log_time) VALUES (?, ?, CURTIME())");
    $result = $stmt->execute(['TEST123', 'IN']);
    
    if ($result) {
        echo "<p style='color:green'>✓ Successfully inserted test log!</p>";
        $lastId = $pdo->lastInsertId();
        echo "<p>Insert ID: " . $lastId . "</p>";
        
        // Retrieve the inserted log
        $stmt = $pdo->prepare("SELECT * FROM recent_logs WHERE log_id = ?");
        $stmt->execute([$lastId]);
        $check = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($check);
        echo "</pre>";
    } else {
        echo "<p style='color:red'>✗ Insert failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Check total count now
$count = $pdo->query("SELECT COUNT(*) as count FROM recent_logs")->fetch()['count'];
echo "<h3>Total logs after insert: " . $count . "</h3>";
