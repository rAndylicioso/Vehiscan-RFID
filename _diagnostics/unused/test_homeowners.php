<?php
require_once 'db.php';

echo "Checking homeowners table...\n\n";

try {
    $stmt = $pdo->query("SELECT id, first_name, last_name, email, account_status, created_at FROM homeowners ORDER BY created_at DESC LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Recent homeowners:\n";
    foreach ($rows as $row) {
        echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\n\nPending count:\n";
    $pendingCount = $pdo->query("SELECT COUNT(*) FROM homeowners WHERE account_status = 'pending'")->fetchColumn();
    echo "Pending accounts: $pendingCount\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
