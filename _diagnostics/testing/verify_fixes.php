<?php
/**
 * Comprehensive System Verification
 * Checks all recent fixes and system health
 */

require_once __DIR__ . '/../db.php';

echo "========================================\n";
echo "   SYSTEM VERIFICATION REPORT\n";
echo "========================================\n\n";

// 1. Database Schema Verification
echo "1. DATABASE SCHEMA CHECK\n";
echo "   Homeowners Table:\n";
$cols = $pdo->query("SHOW COLUMNS FROM homeowners WHERE Field LIKE '%contact%'")->fetchAll(PDO::FETCH_COLUMN);
echo "   ✓ Contact column: " . implode(', ', $cols) . "\n";

$sample = $pdo->query("SELECT contact_number FROM homeowners LIMIT 1")->fetch();
echo "   ✓ Sample data accessible: " . ($sample ? 'YES' : 'NO') . "\n\n";

// 2. Chart Data Verification
echo "2. CHART DATA AVAILABILITY\n";
$totalLogs = $pdo->query("SELECT COUNT(*) FROM recent_logs")->fetchColumn();
$inToday = $pdo->query("SELECT COUNT(*) FROM recent_logs WHERE DATE(log_time) = CURDATE() AND status='IN'")->fetchColumn();
$outToday = $pdo->query("SELECT COUNT(*) FROM recent_logs WHERE DATE(log_time) = CURDATE() AND status='OUT'")->fetchColumn();

echo "   Total Logs: $totalLogs\n";
echo "   Check IN Today: $inToday\n";
echo "   Check OUT Today: $outToday\n";
echo "   ✓ Chart data: " . ($inToday > 0 || $outToday > 0 ? 'AVAILABLE' : 'NO DATA TODAY') . "\n\n";

// 3. File Integrity Check
echo "3. CRITICAL FILES CHECK\n";
$files = [
    'admin/homeowners/homeowner_edit.php',
    'admin/fetch/fetch_manage.php',
    'admin/fetch/fetch_dashboard.php',
    'admin/fetch/fetch_audit.php',
    'admin/fetch/fetch_logs.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/../' . $file;
    $exists = file_exists($path);
    $size = $exists ? filesize($path) : 0;
    echo "   " . ($exists ? '✓' : '✗') . " $file (" . number_format($size) . " bytes)\n";
}

echo "\n4. BUTTON STYLING VERIFICATION\n";
$manage = file_get_contents(__DIR__ . '/../admin/fetch/fetch_manage.php');
$hasGrayButton = strpos($manage, 'bg-gray-700') !== false;
$hasRedButton = strpos($manage, 'bg-red-500') !== false;
echo "   ✓ Dark Edit buttons: " . ($hasGrayButton ? 'YES' : 'NO') . "\n";
echo "   ✓ Red Delete buttons: " . ($hasRedButton ? 'YES' : 'NO') . "\n\n";

echo "5. AUDIT TABLE CHECK\n";
$audit = file_get_contents(__DIR__ . '/../admin/fetch/fetch_audit.php');
$hasIPColumn = strpos($audit, 'IP Address') !== false;
echo "   ✓ IP Address removed: " . ($hasIPColumn ? 'NO (STILL EXISTS)' : 'YES') . "\n\n";

echo "========================================\n";
echo "   VERIFICATION COMPLETE\n";
echo "========================================\n";
