<?php
require __DIR__ . '/../db.php';

try {
    $sql = file_get_contents(__DIR__ . '/../migrations/006_add_account_lockout.sql');
    $pdo->exec($sql);
    echo "✓ Account lockout columns added successfully\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "✓ Columns already exist\n";
    } else {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}
