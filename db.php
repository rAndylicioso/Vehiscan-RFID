<?php
/**
 * Database Connection with Environment Configuration Support
 * 
 * This file establishes a secure PDO connection to the database.
 * Configuration can be loaded from .env file or uses defaults.
 */

// Load configuration
require_once __DIR__ . '/config.php';

// Database connection parameters
$host = DB_HOST;
$db   = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;
$charset = DB_CHARSET;

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Use native prepared statements for better security
    PDO::ATTR_EMULATE_PREPARES   => false,
    // Set timeout for connection
    PDO::ATTR_TIMEOUT            => 5,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Log error securely without exposing details
    error_log("Database connection failed: " . $e->getMessage());
    
    // Show different messages based on environment
    if (APP_DEBUG) {
        die("Database connection error: " . $e->getMessage());
    } else {
        die("Database connection error. Please contact the system administrator.");
    }
}
?>
