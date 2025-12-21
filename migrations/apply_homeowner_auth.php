<?php
/**
 * Migration Script: Add homeowner authentication fields
 * Run this once to add username and password_hash to homeowners table
 */

require_once __DIR__ . '/../db.php';

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Database Migration - Homeowner Auth</title>\n<style>\nbody { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }\n.success { color: #10b981; background: #d1fae5; padding: 15px; border-radius: 8px; margin: 10px 0; }\n.error { color: #ef4444; background: #fee2e2; padding: 15px; border-radius: 8px; margin: 10px 0; }\n.info { color: #3b82f6; background: #dbeafe; padding: 15px; border-radius: 8px; margin: 10px 0; }\ncode { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-family: monospace; }\n</style>\n</head>\n<body>\n";

echo "<h1>🔄 Database Migration: Homeowner Authentication</h1>\n";
echo "<p>This migration adds <code>username</code> and <code>password_hash</code> columns to the homeowners table.</p>\n";

try {
    // Check if columns already exist
    $checkStmt = $pdo->query("SHOW COLUMNS FROM `homeowners` LIKE 'username'");
    if ($checkStmt->rowCount() > 0) {
        echo "<div class='info'>ℹ️ Migration already applied. Username and password columns exist.</div>";
        
        // Show existing homeowners
        $homeowners = $pdo->query("SELECT id, name, username FROM homeowners ORDER BY id")->fetchAll();
        if ($homeowners) {
            echo "<h3>Existing Homeowners:</h3>";
            echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>Name</th><th>Username</th></tr>";
            foreach ($homeowners as $h) {
                echo "<tr><td>{$h['id']}</td><td>" . htmlspecialchars($h['name']) . "</td><td>" . htmlspecialchars($h['username']) . "</td></tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<div class='info'>⏳ Starting migration...</div>\n";
        
        // Read and execute migration
        $sql = file_get_contents(__DIR__ . '/004_add_homeowner_auth.sql');
        
        // Split by semicolons and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            // Skip comments and empty statements
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                $pdo->exec($statement);
                // Extract table name or action from statement
                if (preg_match('/ALTER TABLE.*?`(\w+)`/i', $statement, $matches)) {
                    echo "<div class='success'>✅ Modified table: {$matches[1]}</div>\n";
                } elseif (preg_match('/CREATE INDEX.*?ON.*?`(\w+)`/i', $statement, $matches)) {
                    echo "<div class='success'>✅ Created index on: {$matches[1]}</div>\n";
                } elseif (preg_match('/UPDATE.*?`(\w+)`/i', $statement, $matches)) {
                    echo "<div class='success'>✅ Updated records in: {$matches[1]}</div>\n";
                }
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                    echo "<div class='info'>ℹ️ Column already exists, skipping...</div>\n";
                } else {
                    throw $e;
                }
            }
        }
        
        echo "<div class='success'>✅ Migration completed successfully!</div>\n";
        
        // Verify changes
        $columns = $pdo->query("SHOW COLUMNS FROM `homeowners`")->fetchAll(PDO::FETCH_ASSOC);
        $hasUsername = false;
        $hasPassword = false;
        
        foreach ($columns as $col) {
            if ($col['Field'] === 'username') $hasUsername = true;
            if ($col['Field'] === 'password_hash') $hasPassword = true;
        }
        
        if ($hasUsername && $hasPassword) {
            echo "<div class='success'>✅ Verified: Both username and password_hash columns exist.</div>\n";
            
            // Check existing homeowners
            $count = $pdo->query("SELECT COUNT(*) FROM homeowners WHERE username IS NOT NULL")->fetchColumn();
            echo "<div class='info'>ℹ️ Total homeowners with usernames: {$count}</div>\n";
            
            if ($count > 0) {
                echo "<div class='info'>";
                echo "<strong>⚠️ Important:</strong> Existing homeowners have been assigned temporary usernames (homeowner_1, homeowner_2, etc.) ";
                echo "with the default password <code>password</code>. They should update their credentials upon first login.";
                echo "</div>\n";
            }
        } else {
            echo "<div class='error'>❌ Verification failed: Columns may not have been created properly.</div>\n";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>\n";
    echo "<div class='info'>Please check your database connection and permissions.</div>\n";
}

echo "\n<hr>\n<p><a href='../admin/admin_panel.php'>← Back to Admin Panel</a></p>\n";
echo "</body>\n</html>";
