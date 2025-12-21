<?php
/**
 * COMPREHENSIVE SYSTEM FIX SCRIPT
 * Auto-fixes common issues in the VehiScan system
 */

require_once __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=============================================================\n";
echo "  VEHISCAN COMPREHENSIVE SYSTEM FIX & CLEANUP\n";
echo "=============================================================\n\n";

$fixes_applied = 0;
$issues_found = 0;

// FIX 1: Ensure homeowner_auth table exists with correct structure
echo "[1/10] Checking homeowner_auth table...\n";
try {
    $pdo->query("SELECT 1 FROM homeowner_auth LIMIT 1");
    echo "  ✓ homeowner_auth table exists\n";
} catch (PDOException $e) {
    echo "  → Creating homeowner_auth table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS homeowner_auth (
            id INT AUTO_INCREMENT PRIMARY KEY,
            homeowner_id INT NOT NULL,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            is_active TINYINT(1) DEFAULT 0,
            last_login DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            failed_login_attempts INT DEFAULT 0,
            locked_until DATETIME NULL,
            last_failed_login DATETIME NULL,
            FOREIGN KEY (homeowner_id) REFERENCES homeowners(id) ON DELETE CASCADE,
            INDEX idx_username (username),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "  ✓ Created homeowner_auth table\n";
    $fixes_applied++;
}

// FIX 2: Ensure homeowners table has all required columns
echo "\n[2/10] Checking homeowners table structure...\n";
$required_columns = [
    'account_status' => "ENUM('pending','approved','rejected') DEFAULT 'approved'",
    'first_name' => "VARCHAR(100)",
    'middle_name' => "VARCHAR(100)",
    'last_name' => "VARCHAR(100)",
    'suffix' => "VARCHAR(20)",
    'email' => "VARCHAR(255)",
];

$existing_columns = $pdo->query("SHOW COLUMNS FROM homeowners")->fetchAll(PDO::FETCH_COLUMN);

foreach ($required_columns as $col => $type) {
    if (!in_array($col, $existing_columns)) {
        echo "  → Adding column: $col\n";
        $pdo->exec("ALTER TABLE homeowners ADD COLUMN $col $type");
        $fixes_applied++;
    }
}
echo "  ✓ homeowners table structure verified\n";

// FIX 3: Clean up duplicate CSS/JS definitions
echo "\n[3/10] Analyzing CSS/JS for duplicates...\n";

$css_files = glob(__DIR__ . '/{admin,guard,homeowners,assets}/css/*.css', GLOB_BRACE);
$duplicate_selectors = [];

foreach ($css_files as $file) {
    $content = file_get_contents($file);
    // Check for duplicate .toast definitions
    if (strpos($file, 'system.css') !== false && strpos($content, '.toast {') !== false) {
        echo "  ⚠ Found toast definitions in system.css (should only be in toast.css)\n";
        $issues_found++;
    }
}

// FIX 4: Remove inline transition-all from HTML
echo "\n[4/10] Checking for inline Tailwind conflicts...\n";
$html_files = array_merge(
    glob(__DIR__ . '/guard/pages/*.php'),
    glob(__DIR__ . '/admin/*.php')
);

foreach ($html_files as $file) {
    $content = file_get_contents($file);
    if (preg_match('/class="[^"]*transition-all[^"]*hover:shadow/', $content)) {
        $relative = str_replace(__DIR__ . '\\', '', $file);
        echo "  ⚠ Found transition-all with hover:shadow in: $relative\n";
        $issues_found++;
    }
}

// FIX 5: Check QR code configuration
echo "\n[5/10] Verifying QR code configuration...\n";
if (defined('APP_URL')) {
    echo "  ✓ APP_URL defined: " . APP_URL . "\n";
    
    // Check if QR helper uses APP_URL
    if (file_exists(__DIR__ . '/admin/api/qr_helper.php')) {
        $qr_content = file_get_contents(__DIR__ . '/admin/api/qr_helper.php');
        if (strpos($qr_content, 'APP_URL') !== false) {
            echo "  ✓ QR helper uses dynamic APP_URL\n";
        } else {
            echo "  ⚠ QR helper may have hardcoded IP\n";
            $issues_found++;
        }
    }
} else {
    echo "  ⚠ APP_URL not defined in config\n";
    $issues_found++;
}

// FIX 6: Verify visitor_passes table structure
echo "\n[6/10] Checking visitor_passes table...\n";
try {
    $visitor_columns = $pdo->query("SHOW COLUMNS FROM visitor_passes")->fetchAll(PDO::FETCH_COLUMN);
    
    $required_visitor_cols = ['qr_token', 'status', 'subdivision', 'logo_path'];
    foreach ($required_visitor_cols as $col) {
        if (in_array($col, $visitor_columns)) {
            echo "  ✓ $col exists\n";
        } else {
            echo "  ✗ $col MISSING\n";
            $issues_found++;
        }
    }
} catch (PDOException $e) {
    echo "  ✗ visitor_passes table error: " . $e->getMessage() . "\n";
    $issues_found++;
}

// FIX 7: Check upload directories permissions
echo "\n[7/10] Checking upload directories...\n";
$upload_dirs = [
    __DIR__ . '/uploads',
    __DIR__ . '/uploads/homeowners',
    __DIR__ . '/uploads/vehicles',
    __DIR__ . '/uploads/qr_codes',
];

foreach ($upload_dirs as $dir) {
    if (!is_dir($dir)) {
        echo "  → Creating: $dir\n";
        mkdir($dir, 0755, true);
        $fixes_applied++;
    }
    if (!is_writable($dir)) {
        echo "  ⚠ NOT writable: $dir\n";
        $issues_found++;
    } else {
        echo "  ✓ Writable: " . basename($dir) . "\n";
    }
}

// FIX 8: Clean up test/debug files
echo "\n[8/10] Identifying test/debug files...\n";
$test_patterns = [
    __DIR__ . '/test*.php',
    __DIR__ . '/_testing/*.php',
    __DIR__ . '/debug*.php',
];

$test_files = [];
foreach ($test_patterns as $pattern) {
    $test_files = array_merge($test_files, glob($pattern));
}

echo "  Found " . count($test_files) . " test/debug files\n";
echo "  (These should be removed before deployment)\n";

// FIX 9: Check for missing API endpoints
echo "\n[9/10] Verifying critical API endpoints...\n";
$api_endpoints = [
    'admin/api/get_pending_accounts.php',
    'admin/api/approve_user_account.php',
    'guard/fetch_logs.php',
    'guard/export_logs.php',
    'api/homeowners_get.php',
];

foreach ($api_endpoints as $endpoint) {
    if (file_exists(__DIR__ . '/' . $endpoint)) {
        echo "  ✓ $endpoint\n";
    } else {
        echo "  ✗ MISSING: $endpoint\n";
        $issues_found++;
    }
}

// FIX 10: Database integrity check
echo "\n[10/10] Running database integrity check...\n";
try {
    // Check for orphaned records
    $orphaned_auth = $pdo->query("
        SELECT COUNT(*) FROM homeowner_auth ha
        LEFT JOIN homeowners h ON ha.homeowner_id = h.id
        WHERE h.id IS NULL
    ")->fetchColumn();
    
    if ($orphaned_auth > 0) {
        echo "  ⚠ Found $orphaned_auth orphaned auth records\n";
        echo "  → Cleaning up...\n";
        $pdo->exec("DELETE ha FROM homeowner_auth ha LEFT JOIN homeowners h ON ha.homeowner_id = h.id WHERE h.id IS NULL");
        $fixes_applied++;
    } else {
        echo "  ✓ No orphaned auth records\n";
    }
    
    // Check for pending accounts
    $pending = $pdo->query("SELECT COUNT(*) FROM homeowners WHERE account_status = 'pending'")->fetchColumn();
    echo "  ℹ $pending pending account(s) awaiting approval\n";
    
} catch (PDOException $e) {
    echo "  ⚠ Database check error: " . $e->getMessage() . "\n";
}

// SUMMARY
echo "\n=============================================================\n";
echo "  SUMMARY\n";
echo "=============================================================\n\n";
echo "Fixes Applied: $fixes_applied\n";
echo "Issues Found: $issues_found\n\n";

if ($issues_found == 0 && $fixes_applied == 0) {
    echo "✓ SYSTEM IS CLEAN!\n";
} elseif ($issues_found == 0) {
    echo "✓ ALL ISSUES FIXED!\n";
} else {
    echo "⚠ Some issues require manual attention\n\n";
    echo "RECOMMENDED ACTIONS:\n";
    echo "1. Review warnings above\n";
    echo "2. Test registration at: homeowners/homeowner_registration.php\n";
    echo "3. Test login at: auth/login.php\n";
    echo "4. Check admin panel: admin/admin_panel.php\n";
    echo "5. Check guard panel: guard/pages/guard_side.php\n";
}

echo "\n=============================================================\n";
