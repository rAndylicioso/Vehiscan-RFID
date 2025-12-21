<?php
/**
 * COMPREHENSIVE SYSTEM FILE INTEGRITY CHECK
 * Checks for bugs, errors, miscalled files, missing dependencies
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n╔══════════════════════════════════════════════════════════╗\n";
echo "║      COMPREHENSIVE FILE INTEGRITY CHECK                  ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$issues = [];
$warnings = [];
$fixes = [];

// 1. Check for PHP syntax errors in all files
echo "[1/8] 🔍 Checking PHP syntax errors...\n";
$phpFiles = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/../', RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        // Skip vendor, node_modules, backups
        if (strpos($path, 'vendor') !== false || 
            strpos($path, 'node_modules') !== false ||
            strpos($path, 'backups') !== false ||
            strpos($path, 'phpqrcode\\phpqrcode') !== false) {
            continue;
        }
        
        exec("php -l \"$path\" 2>&1", $output, $return);
        if ($return !== 0) {
            $issues[] = "Syntax error in: $path";
            echo "❌ $path\n";
        }
        $phpFiles[] = str_replace(__DIR__ . '/../', '', $path);
    }
}
echo "✅ Checked " . count($phpFiles) . " PHP files\n";

// 2. Check for broken require/include statements
echo "\n[2/8] 📦 Checking require/include statements...\n";
$brokenIncludes = [];
foreach ($phpFiles as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    $content = file_get_contents($fullPath);
    
    // Find all require/include statements
    preg_match_all("/(?:require|include)(?:_once)?\s*['\"]([^'\"]+)['\"]/", $content, $matches);
    
    foreach ($matches[1] as $includedFile) {
        // Resolve path
        $dir = dirname($fullPath);
        $resolvedPath = realpath($dir . '/' . $includedFile);
        
        if (!$resolvedPath || !file_exists($resolvedPath)) {
            $brokenIncludes[] = "$file includes missing: $includedFile";
            echo "❌ $file → $includedFile (NOT FOUND)\n";
        }
    }
}

if (count($brokenIncludes) === 0) {
    echo "✅ All includes valid\n";
} else {
    $issues = array_merge($issues, $brokenIncludes);
}

// 3. Check for duplicate function definitions
echo "\n[3/8] 🔄 Checking for duplicate functions...\n";
$functions = [];
$duplicates = [];

foreach ($phpFiles as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    $content = file_get_contents($fullPath);
    
    preg_match_all("/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/", $content, $matches);
    
    foreach ($matches[1] as $funcName) {
        if (!isset($functions[$funcName])) {
            $functions[$funcName] = [];
        }
        $functions[$funcName][] = $file;
    }
}

foreach ($functions as $name => $files) {
    if (count($files) > 1 && !in_array($name, ['logAudit', 'sanitizeInput'])) {
        $duplicates[] = "Function '$name' defined in: " . implode(', ', $files);
        echo "⚠️  $name: " . count($files) . " definitions\n";
    }
}

if (count($duplicates) === 0) {
    echo "✅ No duplicate functions (except allowed)\n";
} else {
    $warnings = array_merge($warnings, $duplicates);
}

// 4. Check for orphaned JavaScript/CSS references
echo "\n[4/8] 🎨 Checking for orphaned asset references...\n";
$assetIssues = [];

// Check admin panel for JS/CSS files
$adminPanel = file_get_contents(__DIR__ . '/../admin/admin_panel.php');
preg_match_all("/(?:src|href)=['\"]([^'\"]+\.(?:js|css))['\"]/",$adminPanel, $assetMatches);

foreach ($assetMatches[1] as $asset) {
    $assetPath = __DIR__ . '/../admin/' . $asset;
    if (!file_exists($assetPath)) {
        $assetIssues[] = "Admin panel references missing: $asset";
        echo "❌ Missing: $asset\n";
    }
}

// Check guard panel
if (file_exists(__DIR__ . '/../guard/pages/guard_side.php')) {
    $guardPanel = file_get_contents(__DIR__ . '/../guard/pages/guard_side.php');
    preg_match_all("/(?:src|href)=['\"]([^'\"]+\.(?:js|css))['\"]/",$guardPanel, $guardAssets);
    
    foreach ($guardAssets[1] as $asset) {
        if (strpos($asset, 'http') === 0 || strpos($asset, '//') === 0) continue;
        $assetPath = __DIR__ . '/../guard/pages/' . $asset;
        if (!file_exists($assetPath)) {
            $assetIssues[] = "Guard panel references missing: $asset";
            echo "❌ Missing: $asset\n";
        }
    }
}

if (count($assetIssues) === 0) {
    echo "✅ All asset references valid\n";
} else {
    $issues = array_merge($issues, $assetIssues);
}

// 5. Check fetch endpoint consistency
echo "\n[5/8] 🌐 Checking fetch endpoints...\n";
$fetchEndpoints = [
    'admin/fetch/fetch_dashboard.php',
    'admin/fetch/fetch_manage.php',
    'admin/fetch/fetch_logs.php',
    'admin/fetch/fetch_approvals.php',
    'admin/fetch/fetch_employees.php',
    'admin/fetch/fetch_simulator.php',
    'admin/fetch/fetch_visitors.php',
    'admin/fetch/fetch_audit.php',
    'guard/fetch/fetch_visitors.php',
    'guard/fetch/fetch_logs.php'
];

foreach ($fetchEndpoints as $endpoint) {
    if (!file_exists(__DIR__ . '/../' . $endpoint)) {
        $issues[] = "Missing fetch endpoint: $endpoint";
        echo "❌ $endpoint\n";
    } else {
        echo "✅ $endpoint\n";
    }
}

// 6. Check API endpoints
echo "\n[6/8] 🔌 Checking API endpoints...\n";
$apiEndpoints = [
    'admin/api/get_pending_accounts.php',
    'admin/api/approve_user_account.php',
    'admin/api/get_homeowner_stats.php',
    'admin/api/check_new_logs.php',
    'admin/api/check_pending_approvals.php',
    'homeowners/api/get_vehicles.php',
    'homeowners/api/add_vehicle.php',
    'homeowners/api/delete_vehicle.php'
];

foreach ($apiEndpoints as $endpoint) {
    if (!file_exists(__DIR__ . '/../' . $endpoint)) {
        $issues[] = "Missing API endpoint: $endpoint";
        echo "❌ $endpoint\n";
    } else {
        echo "✅ $endpoint\n";
    }
}

// 7. Check session file consistency
echo "\n[7/8] 🔐 Checking session files...\n";
$sessionFiles = [
    'includes/session_admin.php',
    'includes/session_admin_unified.php',
    'includes/session_guard.php',
    'includes/session_homeowner.php'
];

foreach ($sessionFiles as $file) {
    if (!file_exists(__DIR__ . '/../' . $file)) {
        $issues[] = "Missing session file: $file";
        echo "❌ $file\n";
    } else {
        // Check if file has session_start()
        $content = file_get_contents(__DIR__ . '/../' . $file);
        if (strpos($content, 'session_start()') === false && strpos($content, '@session_start()') === false) {
            $warnings[] = "$file doesn't call session_start()";
            echo "⚠️  $file (no session_start)\n";
        } else {
            echo "✅ $file\n";
        }
    }
}

// 8. Check database connectivity
echo "\n[8/8] 🗄️  Checking database connection...\n";
try {
    require_once __DIR__ . '/../db.php';
    echo "✅ Database connection successful\n";
    
    // Check for required tables
    $requiredTables = ['homeowners', 'users', 'access_logs', 'visitor_passes', 'homeowner_auth'];
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($requiredTables as $table) {
        if (!in_array($table, $existingTables)) {
            $issues[] = "Missing database table: $table";
            echo "❌ Table: $table\n";
        } else {
            echo "✅ Table: $table\n";
        }
    }
} catch (Exception $e) {
    $issues[] = "Database connection failed: " . $e->getMessage();
    echo "❌ Database error\n";
}

// SUMMARY
echo "\n╔══════════════════════════════════════════════════════════╗\n";
echo "║                     SUMMARY                              ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

echo "Critical Issues: " . count($issues) . "\n";
if (count($issues) > 0) {
    foreach ($issues as $issue) {
        echo "  ❌ $issue\n";
    }
}

echo "\nWarnings: " . count($warnings) . "\n";
if (count($warnings) > 0) {
    foreach ($warnings as $warning) {
        echo "  ⚠️  $warning\n";
    }
}

if (count($issues) === 0 && count($warnings) === 0) {
    echo "\n✅ NO ISSUES FOUND - System integrity verified!\n";
} else {
    echo "\n⚠️  Issues found - review and fix above\n";
}

// Save report
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_files_checked' => count($phpFiles),
    'issues' => $issues,
    'warnings' => $warnings
];
file_put_contents(__DIR__ . '/file_integrity_report.json', json_encode($report, JSON_PRETTY_PRINT));
echo "\n📄 Report saved to: _diagnostics/file_integrity_report.json\n";
