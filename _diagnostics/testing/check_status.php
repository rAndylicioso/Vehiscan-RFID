<?php
require_once __DIR__ . '/../db.php';

echo "Status Values in Database:\n";
$statuses = $pdo->query("SELECT DISTINCT status FROM recent_logs")->fetchAll(PDO::FETCH_COLUMN);
print_r($statuses);

echo "\nLog Statistics:\n";
echo "Total: " . $pdo->query("SELECT COUNT(*) FROM recent_logs")->fetchColumn() . "\n";
echo "IN: " . $pdo->query("SELECT COUNT(*) FROM recent_logs WHERE status='IN'")->fetchColumn() . "\n";
echo "OUT: " . $pdo->query("SELECT COUNT(*) FROM recent_logs WHERE status='OUT'")->fetchColumn() . "\n";
echo "ALLOWED: " . $pdo->query("SELECT COUNT(*) FROM recent_logs WHERE status='ALLOWED'")->fetchColumn() . "\n";
echo "DENIED: " . $pdo->query("SELECT COUNT(*) FROM recent_logs WHERE status='DENIED'")->fetchColumn() . "\n";

echo "\nToday's Stats:\n";
echo "Total Today: " . $pdo->query("SELECT COUNT(*) FROM recent_logs WHERE DATE(log_time) = CURDATE()")->fetchColumn() . "\n";
echo "IN Today: " . $pdo->query("SELECT COUNT(*) FROM recent_logs WHERE DATE(log_time) = CURDATE() AND status='IN'")->fetchColumn() . "\n";
echo "OUT Today: " . $pdo->query("SELECT COUNT(*) FROM recent_logs WHERE DATE(log_time) = CURDATE() AND status='OUT'")->fetchColumn() . "\n";
