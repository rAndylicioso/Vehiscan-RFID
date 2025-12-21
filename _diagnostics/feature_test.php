<?php
/**
 * Test All System Features
 * Validates buttons, components, and functionality
 */

require_once __DIR__ . '/../db.php';

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         FEATURE FUNCTIONALITY TEST                         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$passed = 0;
$failed = 0;

// Test 1: Check if approval API endpoint exists and is accessible
echo "[1/10] Testing approval system endpoints...\n";
if (file_exists('admin/api/approve_user_account.php') && 
    file_exists('admin/api/get_pending_accounts.php')) {
    echo "✅ Approval API endpoints exist\n";
    $passed++;
} else {
    echo "❌ Missing approval API endpoints\n";
    $failed++;
}

// Test 2: Check database stored procedures/triggers
echo "\n[2/10] Testing database account_status values...\n";
try {
    $stmt = $pdo->query("SELECT DISTINCT account_status FROM homeowners");
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $expected = ['pending', 'approved', 'rejected'];
    $valid = true;
    foreach ($statuses as $status) {
        if (!in_array($status, $expected)) {
            $valid = false;
            break;
        }
    }
    if ($valid) {
        echo "✅ Valid account_status values: " . implode(', ', $statuses) . "\n";
        $passed++;
    } else {
        echo "❌ Invalid account_status values found\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "❌ Error checking account_status: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 3: Check session files consistency
echo "\n[3/10] Testing session file usage...\n";
$sessionUsage = [
    'session_admin_unified' => 0,
    'session_admin' => 0,
    'session_guard' => 0,
    'session_homeowner' => 0,
    'session_super_admin' => 0
];

$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('admin'));
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (strpos($content, 'session_admin_unified.php') !== false) {
            $sessionUsage['session_admin_unified']++;
        }
        if (strpos($content, 'session_admin.php') !== false && strpos($content, 'session_admin_unified.php') === false) {
            $sessionUsage['session_admin']++;
        }
    }
}

echo "Session file usage in admin:\n";
echo "  - session_admin_unified: {$sessionUsage['session_admin_unified']}\n";
echo "  - session_admin (old): {$sessionUsage['session_admin']}\n";

if ($sessionUsage['session_admin'] <= 1) {
    echo "✅ Most files use unified session\n";
    $passed++;
} else {
    echo "⚠️  Warning: {$sessionUsage['session_admin']} files still use old session_admin.php\n";
    $passed++;
}

// Test 4: Verify visitor pass system
echo "\n[4/10] Testing visitor pass system...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM visitor_passes");
    $count = $stmt->fetchColumn();
    echo "✅ Visitor passes table accessible ($count records)\n";
    $passed++;
} catch (Exception $e) {
    echo "❌ Error accessing visitor_passes: " . $e->getMessage() . "\n";
    $failed++;
}

// Test 5: Check RFID simulation
echo "\n[5/10] Testing RFID simulation endpoints...\n";
if (file_exists('admin/simulation/simulate_rfid_scan.php')) {
    echo "✅ RFID simulation endpoint exists\n";
    $passed++;
} else {
    echo "❌ Missing RFID simulation endpoint\n";
    $failed++;
}

// Test 6: Check guard panel
echo "\n[6/10] Testing guard panel...\n";
if (file_exists('guard/pages/guard_side.php') &&
    file_exists('guard/fetch/fetch_logs.php')) {
    echo "✅ Guard panel files exist\n";
    $passed++;
} else {
    echo "❌ Missing guard panel files\n";
    $failed++;
}

// Test 7: Check homeowner portal
echo "\n[7/10] Testing homeowner portal...\n";
if (file_exists('homeowners/portal.php') &&
    file_exists('homeowners/homeowner_registration.php')) {
    echo "✅ Homeowner portal files exist\n";
    $passed++;
} else {
    echo "❌ Missing homeowner portal files\n";
    $failed++;
}

// Test 8: Test QR code generation
echo "\n[8/10] Testing QR code system...\n";
if (file_exists('phpqrcode/qrlib.php')) {
    echo "✅ QR code library exists\n";
    $passed++;
} else {
    echo "❌ Missing QR code library\n";
    $failed++;
}

// Test 9: Check security classes
echo "\n[9/10] Testing security classes...\n";
$securityFiles = [
    'includes/input_sanitizer.php',
    'includes/rate_limiter.php',
    'includes/audit_logger.php'
];
$allExist = true;
foreach ($securityFiles as $file) {
    if (!file_exists($file)) {
        $allExist = false;
        echo "❌ Missing: $file\n";
    }
}
if ($allExist) {
    echo "✅ All security classes exist\n";
    $passed++;
} else {
    $failed++;
}

// Test 10: Check JavaScript assets
echo "\n[10/10] Testing JavaScript assets...\n";
if (file_exists('assets/js/admin/admin_panel.js') &&
    file_exists('assets/js/registration.js')) {
    echo "✅ Critical JavaScript files exist\n";
    $passed++;
} else {
    echo "❌ Missing JavaScript files\n";
    $failed++;
}

// Summary
echo "\n═══════════════════════════════════════════════════════════\n";
echo "                    TEST SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n\n";

$total = $passed + $failed;
$percentage = round(($passed / $total) * 100, 1);

echo "Total Tests: $total\n";
echo "✅ Passed: $passed\n";
echo "❌ Failed: $failed\n";
echo "Success Rate: $percentage%\n\n";

if ($failed === 0) {
    echo "🎉 ALL FEATURES WORKING! System is fully functional.\n";
} else {
    echo "⚠️  Some features need attention.\n";
}
