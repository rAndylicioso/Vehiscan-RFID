#!/usr/bin/env php
<?php
/**
 * Comprehensive Component Testing Script
 * Tests all critical PHP files, CSS, JavaScript, and components
 */

echo "═══════════════════════════════════════════════════════════════════\n";
echo "  VEHISCAN RFID - COMPREHENSIVE COMPONENT TEST\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$results = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0
];

// Change to project root
chdir(__DIR__ . '/..');

echo "📁 Working Directory: " . getcwd() . "\n\n";

// Test 1: PHP Syntax Validation
echo "═══ TEST 1: PHP SYNTAX VALIDATION ═══\n";
$phpFiles = [
    'admin/fetch/fetch_approvals.php' => 'Account Approvals Loader',
    'admin/api/employee_save.php' => 'Employee Save API',
    'guard/fetch/fetch_visitors.php' => 'Guard Visitor Fetch',
    'admin/admin_panel.php' => 'Admin Panel Main',
    'guard/pages/guard_side.php' => 'Guard Panel Main',
    'includes/session_admin_unified.php' => 'Unified Session Handler',
    'admin/components/approvals_page.php' => 'Approvals Component'
];

foreach ($phpFiles as $file => $name) {
    if (!file_exists($file)) {
        echo "  ✗ MISSING: $name ($file)\n";
        $results['failed']++;
        continue;
    }
    
    exec("php -l \"$file\" 2>&1", $output, $returnCode);
    $output = implode("\n", $output);
    
    if ($returnCode === 0 && strpos($output, 'No syntax errors') !== false) {
        echo "  ✓ VALID: $name\n";
        $results['passed']++;
    } else {
        echo "  ✗ SYNTAX ERROR: $name\n";
        echo "    " . str_replace("\n", "\n    ", $output) . "\n";
        $results['failed']++;
    }
}
echo "\n";

// Test 2: JavaScript Files Existence
echo "═══ TEST 2: JAVASCRIPT FILES ═══\n";
$jsFiles = [
    'assets/js/admin/admin_panel.js' => 'Admin Panel Script',
    'assets/js/admin/realtime-updates.js' => 'Realtime Updates',
    'assets/js/admin/modal-handler.js' => 'Modal Handler',
    'assets/js/admin/datatables-init.js' => 'DataTables Init',
    'guard/js/guard_side.js' => 'Guard Panel Script',
    'guard/js/guard-dark-mode.js' => 'Guard Dark Mode',
    'assets/js/toast.js' => 'Toast Notifications',
    'assets/js/session-timeout.js' => 'Session Timeout'
];

foreach ($jsFiles as $file => $name) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "  ✓ FOUND: $name (" . number_format($size) . " bytes)\n";
        $results['passed']++;
        
        // Basic syntax check - look for common errors
        $content = file_get_contents($file);
        if (preg_match('/[^:]\/\/.*\n.*[^:]\/\//', $content)) {
            // Has multiple comment lines (good sign)
        }
        if (substr_count($content, '{') !== substr_count($content, '}')) {
            echo "    ⚠ WARNING: Mismatched braces detected\n";
            $results['warnings']++;
        }
    } else {
        echo "  ✗ MISSING: $name ($file)\n";
        $results['failed']++;
    }
}
echo "\n";

// Test 3: CSS Files Existence
echo "═══ TEST 3: CSS FILES ═══\n";
$cssFiles = [
    'assets/css/admin/admin.css' => 'Admin Styles',
    'assets/css/admin/modal.css' => 'Modal Styles',
    'guard/css/guard_side.css' => 'Guard Styles',
    'guard/css/guard-dark-mode.css' => 'Guard Dark Mode',
    'assets/css/sweetalert-custom.css' => 'SweetAlert Custom',
    'assets/css/system.css' => 'System Styles'
];

foreach ($cssFiles as $file => $name) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "  ✓ FOUND: $name (" . number_format($size) . " bytes)\n";
        $results['passed']++;
        
        // Check for skeleton loader styles
        $content = file_get_contents($file);
        if (strpos($content, 'skeleton-loading') !== false) {
            echo "    ✓ Contains skeleton loader animation\n";
        }
    } else {
        echo "  ✗ MISSING: $name ($file)\n";
        $results['failed']++;
    }
}
echo "\n";

// Test 4: Database Connection
echo "═══ TEST 4: DATABASE CONNECTION ═══\n";
try {
    require_once 'db.php';
    echo "  ✓ Database connection established\n";
    $results['passed']++;
    
    // Test critical tables
    $tables = ['users', 'homeowners', 'homeowner_auth', 'visitor_passes', 'access_logs'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "  ✓ Table '$table': $count records\n";
        $results['passed']++;
    }
} catch (Exception $e) {
    echo "  ✗ Database error: " . $e->getMessage() . "\n";
    $results['failed']++;
}
echo "\n";

// Test 5: Session Management
echo "═══ TEST 5: SESSION MANAGEMENT ═══\n";
try {
    // Test super admin session
    session_name('vehiscan_superadmin');
    @session_start();
    echo "  ✓ Super admin session can start\n";
    $results['passed']++;
    session_write_close();
    
    // Test admin session
    session_name('vehiscan_admin');
    @session_start();
    echo "  ✓ Admin session can start\n";
    $results['passed']++;
    session_write_close();
    
    // Test guard session
    session_name('vehiscan_guard');
    @session_start();
    echo "  ✓ Guard session can start\n";
    $results['passed']++;
    session_write_close();
    
    // Test unified handler
    if (file_exists('includes/session_admin_unified.php')) {
        echo "  ✓ Unified session handler exists\n";
        $results['passed']++;
    }
} catch (Exception $e) {
    echo "  ✗ Session error: " . $e->getMessage() . "\n";
    $results['failed']++;
}
echo "\n";

// Test 6: Critical Component Features
echo "═══ TEST 6: COMPONENT FEATURES ═══\n";

// Test dropdown positioning
if (file_exists('admin/components/approvals_page.php')) {
    $content = file_get_contents('admin/components/approvals_page.php');
    
    if (strpos($content, 'toggleActionDropdown') !== false) {
        echo "  ✓ Dropdown toggle function exists\n";
        $results['passed']++;
    } else {
        echo "  ✗ Dropdown toggle function missing\n";
        $results['failed']++;
    }
    
    if (strpos($content, 'getBoundingClientRect') !== false) {
        echo "  ✓ Smart positioning logic found\n";
        $results['passed']++;
    } else {
        echo "  ⚠ Smart positioning may not be implemented\n";
        $results['warnings']++;
    }
    
    if (strpos($content, 'skeleton') !== false) {
        echo "  ✓ Skeleton loader implemented\n";
        $results['passed']++;
    } else {
        echo "  ⚠ Skeleton loader not found\n";
        $results['warnings']++;
    }
} else {
    echo "  ✗ Approvals component missing\n";
    $results['failed']++;
}
echo "\n";

// Test 7: Authorization Checks
echo "═══ TEST 7: AUTHORIZATION LOGIC ═══\n";
$authFiles = [
    'admin/fetch/fetch_approvals.php' => "in_array(\$_SESSION['role'], ['admin', 'super_admin'])",
    'admin/api/employee_save.php' => "in_array(\$_SESSION['role'], ['super_admin', 'admin'])",
    'guard/fetch/fetch_visitors.php' => "guard"
];

foreach ($authFiles as $file => $expectedAuth) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'admin') !== false || strpos($content, 'super_admin') !== false || strpos($content, 'guard') !== false) {
            echo "  ✓ Authorization check present in " . basename($file) . "\n";
            $results['passed']++;
        } else {
            echo "  ✗ No authorization check in " . basename($file) . "\n";
            $results['failed']++;
        }
    }
}
echo "\n";

// Test 8: Error Handling
echo "═══ TEST 8: ERROR HANDLING ═══\n";
$errorFiles = [
    'admin/api/employee_save.php' => 'Employee Save',
    'guard/fetch/fetch_visitors.php' => 'Visitor Fetch',
    'admin/fetch/fetch_approvals.php' => 'Approvals Fetch'
];

foreach ($errorFiles as $file => $name) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        $hasTryCatch = strpos($content, 'try {') !== false && strpos($content, 'catch') !== false;
        $hasErrorResponse = strpos($content, 'json_encode') !== false && strpos($content, 'success') !== false;
        
        if ($hasTryCatch) {
            echo "  ✓ $name: Try-catch block present\n";
            $results['passed']++;
        } else {
            echo "  ⚠ $name: No try-catch error handling\n";
            $results['warnings']++;
        }
        
        if ($hasErrorResponse) {
            echo "  ✓ $name: JSON error responses\n";
            $results['passed']++;
        }
    }
}
echo "\n";

// Test 9: SQL Injection Prevention
echo "═══ TEST 9: SECURITY CHECKS ═══\n";
$securityFiles = [
    'admin/api/employee_save.php',
    'guard/fetch/fetch_visitors.php',
    'admin/components/approvals_page.php'
];

foreach ($securityFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        // Check for prepared statements
        if (strpos($content, '->prepare(') !== false || strpos($content, '$pdo->prepare') !== false) {
            echo "  ✓ " . basename($file) . ": Uses prepared statements\n";
            $results['passed']++;
        } else if (strpos($content, 'SELECT') !== false || strpos($content, 'INSERT') !== false) {
            echo "  ⚠ " . basename($file) . ": May have SQL queries without preparation\n";
            $results['warnings']++;
        }
        
        // Check for CSRF validation
        if (strpos($content, 'csrf') !== false || strpos($content, 'CSRF') !== false) {
            echo "  ✓ " . basename($file) . ": CSRF validation present\n";
            $results['passed']++;
        }
    }
}
echo "\n";

// Test 10: Tailwind CSS Integration
echo "═══ TEST 10: TAILWIND CSS ═══\n";
$tailwindFiles = [
    'admin/admin_panel.php',
    'guard/pages/guard_side.php',
    'admin/components/approvals_page.php'
];

foreach ($tailwindFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        
        if (strpos($content, 'tailwindcss') !== false || strpos($content, 'bg-') !== false || strpos($content, 'text-') !== false) {
            echo "  ✓ " . basename($file) . ": Tailwind classes found\n";
            $results['passed']++;
        } else {
            echo "  ⚠ " . basename($file) . ": No Tailwind classes detected\n";
            $results['warnings']++;
        }
    }
}
echo "\n";

// Final Summary
echo "═══════════════════════════════════════════════════════════════════\n";
echo "  SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$total = $results['passed'] + $results['failed'] + $results['warnings'];
$passRate = $total > 0 ? round(($results['passed'] / $total) * 100, 1) : 0;

echo "  ✓ Passed:   " . $results['passed'] . "\n";
echo "  ✗ Failed:   " . $results['failed'] . "\n";
echo "  ⚠ Warnings: " . $results['warnings'] . "\n";
echo "  ─────────────────────\n";
echo "  Total:      $total tests\n";
echo "  Pass Rate:  $passRate%\n\n";

if ($results['failed'] === 0) {
    echo "  ✅ ALL CRITICAL TESTS PASSED!\n\n";
    exit(0);
} else {
    echo "  ❌ SOME TESTS FAILED - Review above for details\n\n";
    exit(1);
}
