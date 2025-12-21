<?php
require_once __DIR__ . '/../db.php';

echo "=== USERS TABLE ===\n";
$stmt = $pdo->query('SELECT id, username, role FROM users ORDER BY id');
while($row = $stmt->fetch()) {
    echo sprintf("ID: %d | Username: %s | Role: %s\n", $row['id'], $row['username'], $row['role']);
}

echo "\n=== SUPER ADMIN TABLE ===\n";
try {
    $stmt = $pdo->query('SELECT id, username, email FROM super_admin ORDER BY id');
    while($row = $stmt->fetch()) {
        echo sprintf("ID: %d | Username: %s | Email: %s\n", $row['id'], $row['username'], $row['email']);
    }
} catch (Exception $e) {
    echo "No super_admin table\n";
}
