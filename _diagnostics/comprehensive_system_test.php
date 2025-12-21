<?php
/**
 * Comprehensive System Test
 * Tests all major functionality after cleanup and fixes
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$baseDir = dirname(__DIR__);

// Load utility files for testing
if (file_exists($baseDir . '/includes/input_sanitizer.php')) {
    require_once $baseDir . '/includes/input_sanitizer.php';
}
if (file_exists($baseDir . '/includes/common_utilities.php')) {
    require_once $baseDir . '/includes/common_utilities.php';
}
$testResults = [];
$passedTests = 0;
$failedTests = 0;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         COMPREHENSIVE SYSTEM TEST                          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

/**
 * Test helper function
 */
function test($name, $callback) {
    global $testResults, $passedTests, $failedTests;
    
    try {
        $result = $callback();
        if ($result) {
            echo "✅ PASS: $name\n";
            $testResults[] = ['name' => $name, 'status' => 'PASS'];
            $passedTests++;
            return true;
        } else {
            echo "❌ FAIL: $name\n";
            $testResults[] = ['name' => $name, 'status' => 'FAIL', 'reason' => 'Test returned false'];
            $failedTests++;
            return false;
        }
    } catch (Exception $e) {
        echo "❌ FAIL: $name - " . $e->getMessage() . "\n";
        $testResults[] = ['name' => $name, 'status' => 'FAIL', 'reason' => $e->getMessage()];
        $failedTests++;
        return false;
    }
}

echo "[1/8] 🗄️  Testing database connection...\n";
test('Database connection established', function() {
    global $conn, $pdo;
    if (isset($pdo)) {
        return $pdo instanceof PDO;
    } elseif (isset($conn)) {
        return $conn && $conn->ping();
    }
    return false;
});

test('Database has required tables', function() {
    global $conn, $pdo;
    $requiredTables = [
        'homeowners', 'users', 'access_logs', 'homeowner_vehicles',
        'visitor_passes', 'super_admin', 'failed_login_attempts', 'system_installation'
    ];
    
    foreach ($requiredTables as $table) {
        if (isset($pdo)) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() === 0) {
                throw new Exception("Table '$table' not found");
            }
        } elseif (isset($conn)) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows === 0) {
                throw new Exception("Table '$table' not found");
            }
        }
    }
    return true;
});

echo "\n[2/8] 📁 Testing file structure...\n";
test('Essential config files exist', function() use ($baseDir) {
    $requiredFiles = [
        'config.php',
        'db.php',
        'index.php',
        'auth/login.php',
        'includes/input_validator.php',
        'includes/input_sanitizer.php',
        'includes/common_utilities.php'
    ];
    
    foreach ($requiredFiles as $file) {
        if (!file_exists($baseDir . '/' . $file)) {
            throw new Exception("Required file '$file' not found");
        }
    }
    return true;
});

test('Essential directories exist', function() use ($baseDir) {
    $requiredDirs = [
        'admin',
        'auth',
        'includes',
        'assets',
        'guard',
        'homeowners',
        'visitor',
        'uploads',
        '_diagnostics',
        'docs'
    ];
    
    foreach ($requiredDirs as $dir) {
        if (!is_dir($baseDir . '/' . $dir)) {
            throw new Exception("Required directory '$dir' not found");
        }
    }
    return true;
});

echo "\n[3/8] 🔒 Testing security features...\n";
test('Input sanitizer class loaded', function() {
    return class_exists('InputSanitizer');
});

test('Input sanitizer methods work', function() {
    $testString = '<script>alert("xss")</script>Test';
    $sanitized = InputSanitizer::sanitizeString($testString);
    return strpos($sanitized, '<script>') === false;
});

test('CSRF token generation works', function() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $token = InputSanitizer::generateCsrf();
    return !empty($token) && strlen($token) === 64;
});

echo "\n[4/8] 🔧 Testing utility functions...\n";
test('Common utilities file loaded', function() use ($baseDir) {
    require_once $baseDir . '/includes/common_utilities.php';
    return function_exists('formatContactNumber');
});

test('Contact number formatting works', function() {
    $number = '09123456789';
    $formatted = formatContactNumber($number);
    return $formatted === '0912-345-6789';
});

echo "\n[5/8] 🔐 Testing authentication system...\n";
test('Login page accessible', function() use ($baseDir) {
    $loginFile = $baseDir . '/auth/login.php';
    if (!file_exists($loginFile)) {
        return false;
    }
    
    $content = file_get_contents($loginFile);
    return strpos($content, 'password') !== false;
});

test('Homeowner registration accessible', function() use ($baseDir) {
    $registerFile = $baseDir . '/homeowners/homeowner_registration.php';
    if (!file_exists($registerFile)) {
        return false;
    }
    
    $content = file_get_contents($registerFile);
    return stripos($content, 'register') !== false || stripos($content, 'registration') !== false;
});

test('Logout mechanism exists', function() use ($baseDir) {
    return file_exists($baseDir . '/auth/logout.php');
});

echo "\n[6/8] 👤 Testing user management...\n";
test('Admin panel accessible', function() use ($baseDir) {
    return file_exists($baseDir . '/admin/admin_panel.php');
});

test('Homeowner registration accessible', function() use ($baseDir) {
    return file_exists($baseDir . '/homeowners/homeowner_registration.php');
});

test('Employee management accessible', function() use ($baseDir) {
    $files = [
        'admin/employee_list.php',
        'admin/employee_registration.php',
        'admin/employee_edit.php',
        'admin/employee_delete.php'
    ];
    
    foreach ($files as $file) {
        if (!file_exists($baseDir . '/' . $file)) {
            throw new Exception("Employee file '$file' not found");
        }
    }
    return true;
});

echo "\n[7/8] 🚗 Testing vehicle and visitor management...\n";
test('Guard panel accessible', function() use ($baseDir) {
    return file_exists($baseDir . '/guard/pages/guard_side.php');
});

test('Visitor pass system accessible', function() use ($baseDir) {
    $files = [
        'admin/api/create_visitor_pass.php',
        'homeowners/api/create_visitor_pass.php'
    ];
    
    foreach ($files as $file) {
        if (!file_exists($baseDir . '/' . $file)) {
            throw new Exception("Visitor file '$file' not found");
        }
    }
    return true;
});

test('QR code library available', function() use ($baseDir) {
    return is_dir($baseDir . '/phpqrcode');
});

echo "\n[8/8] 📊 Testing API endpoints...\n";
test('Admin API endpoints exist', function() use ($baseDir) {
    $apiFiles = [
        'admin/api/approve_user_account.php',
        'admin/api/employee_save.php'
    ];
    
    foreach ($apiFiles as $file) {
        if (!file_exists($baseDir . '/' . $file)) {
            throw new Exception("API file '$file' not found");
        }
    }
    return true;
});

test('Homeowner API endpoints exist', function() use ($baseDir) {
    $apiFiles = [
        'api/homeowner_save.php',
        'api/homeowners_get.php'
    ];
    
    foreach ($apiFiles as $file) {
        if (!file_exists($baseDir . '/' . $file)) {
            throw new Exception("API file '$file' not found");
        }
    }
    return true;
});

echo "\n═══════════════════════════════════════════════════════════\n";
echo "                    TEST SUMMARY                            \n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Total Tests: " . ($passedTests + $failedTests) . "\n";
echo "✅ Passed: $passedTests\n";
echo "❌ Failed: $failedTests\n\n";

if ($failedTests === 0) {
    echo "🎉 ALL TESTS PASSED! System is healthy.\n";
} else {
    echo "⚠️  Some tests failed. Review the results above.\n";
}

// Save detailed report
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_tests' => $passedTests + $failedTests,
    'passed' => $passedTests,
    'failed' => $failedTests,
    'results' => $testResults
];

file_put_contents(
    __DIR__ . '/system_test_report.json',
    json_encode($report, JSON_PRETTY_PRINT)
);

echo "\n📄 Detailed report saved to: _diagnostics/system_test_report.json\n";
