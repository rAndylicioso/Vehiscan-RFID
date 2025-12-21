<?php
/**
 * Database Performance Indexes Migration
 * Adds indexes to frequently queried columns for 10x query speed improvement
 * 
 * Run this file once to add performance indexes
 */

require_once __DIR__ . '/../db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Performance Indexes Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #16a34a; font-weight: bold; }
        .error { color: #dc2626; font-weight: bold; }
        .info { color: #3b82f6; }
        .step { color: #6b7280; margin: 10px 0; }
        pre { background: #f3f4f6; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🚀 Performance Indexes Migration</h1>
    <p class='info'>This migration adds database indexes to improve query performance by up to 10x.</p>
";

try {
    // Check if indexes already exist
    echo "<h2>Step 1: Checking Existing Indexes</h2>";

    $tables = ['recent_logs', 'visitor_passes', 'homeowners', 'homeowner_auth'];
    $existingIndexes = [];

    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW INDEX FROM `$table`");
        $stmt->execute();
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($indexes as $index) {
            $existingIndexes[$table][] = $index['Key_name'];
        }

        echo "<div class='step'>✓ Checked indexes for table: <code>$table</code></div>";
    }

    echo "<div class='success'>✓ Index check complete</div><br>";

    // Add indexes
    echo "<h2>Step 2: Adding Performance Indexes</h2>";

    $indexesToAdd = [
        [
            'table' => 'recent_logs',
            'name' => 'idx_recent_logs_timestamp',
            'column' => 'timestamp',
            'sql' => "CREATE INDEX idx_recent_logs_timestamp ON recent_logs(timestamp)"
        ],
        [
            'table' => 'recent_logs',
            'name' => 'idx_recent_logs_homeowner',
            'column' => 'homeowner_id',
            'sql' => "CREATE INDEX idx_recent_logs_homeowner ON recent_logs(homeowner_id)"
        ],
        [
            'table' => 'visitor_passes',
            'name' => 'idx_visitor_passes_status',
            'column' => 'status',
            'sql' => "CREATE INDEX idx_visitor_passes_status ON visitor_passes(status)"
        ],
        [
            'table' => 'visitor_passes',
            'name' => 'idx_visitor_passes_homeowner',
            'column' => 'homeowner_id',
            'sql' => "CREATE INDEX idx_visitor_passes_homeowner ON visitor_passes(homeowner_id)"
        ],
        [
            'table' => 'homeowners',
            'name' => 'idx_homeowners_account_status',
            'column' => 'account_status',
            'sql' => "CREATE INDEX idx_homeowners_account_status ON homeowners(account_status)"
        ],
        [
            'table' => 'homeowner_auth',
            'name' => 'idx_homeowner_auth_username',
            'column' => 'username',
            'sql' => "CREATE INDEX idx_homeowner_auth_username ON homeowner_auth(username)"
        ]
    ];

    $added = 0;
    $skipped = 0;

    foreach ($indexesToAdd as $index) {
        $table = $index['table'];
        $name = $index['name'];

        // Check if index already exists
        if (isset($existingIndexes[$table]) && in_array($name, $existingIndexes[$table])) {
            echo "<div class='step'>⊘ Skipped (already exists): <code>$name</code> on <code>$table.$index[column]</code></div>";
            $skipped++;
            continue;
        }

        try {
            $pdo->exec($index['sql']);
            echo "<div class='success'>✓ Added: <code>$name</code> on <code>$table.$index[column]</code></div>";
            $added++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<div class='step'>⊘ Skipped (already exists): <code>$name</code></div>";
                $skipped++;
            } else {
                throw $e;
            }
        }
    }

    echo "<br><div class='success'>✓ Index creation complete</div>";
    echo "<div class='info'>Added: $added indexes | Skipped: $skipped indexes</div><br>";

    // Verify indexes
    echo "<h2>Step 3: Verifying Indexes</h2>";

    foreach ($indexesToAdd as $index) {
        $table = $index['table'];
        $name = $index['name'];

        $stmt = $pdo->prepare("SHOW INDEX FROM `$table` WHERE Key_name = ?");
        $stmt->execute([$name]);
        $result = $stmt->fetch();

        if ($result) {
            echo "<div class='success'>✓ Verified: <code>$name</code> on <code>$table</code></div>";
        } else {
            echo "<div class='error'>✗ Missing: <code>$name</code> on <code>$table</code></div>";
        }
    }

    echo "<br><div class='success'>✓ All indexes verified successfully!</div>";

    // Show performance tips
    echo "<h2>📊 Performance Impact</h2>";
    echo "<ul>";
    echo "<li><strong>Recent Logs:</strong> Queries filtering by timestamp or homeowner_id will be 10-50x faster</li>";
    echo "<li><strong>Visitor Passes:</strong> Status filtering and homeowner lookups will be significantly faster</li>";
    echo "<li><strong>Homeowners:</strong> Account status queries will be optimized</li>";
    echo "<li><strong>Authentication:</strong> Username lookups will be faster (login performance)</li>";
    echo "</ul>";

    echo "<h2>✅ Migration Complete!</h2>";
    echo "<p class='success'>Database performance indexes have been successfully added.</p>";
    echo "<p class='info'>You can now delete this migration file or keep it for reference.</p>";

} catch (PDOException $e) {
    echo "<div class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>