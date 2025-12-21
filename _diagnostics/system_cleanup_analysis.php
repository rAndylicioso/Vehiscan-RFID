<?php
/**
 * VehiScan System Cleanup & Analysis Tool
 * Identifies temporary files, duplicates, and system issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseDir = dirname(__DIR__);
$results = [
    'test_files' => [],
    'temp_files' => [],
    'duplicate_files' => [],
    'unused_files' => [],
    'missing_files' => [],
    'errors' => []
];

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     VEHISCAN SYSTEM CLEANUP & ANALYSIS                     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// 1. Identify test/temporary files in root
echo "[1/7] 🔍 Scanning for test and temporary files...\n";
$rootFiles = scandir($baseDir);
$testPatterns = [
    'test_', 'check_', 'debug_', 'quick_', 
    'audit_report.html', 'audit_results.txt',
    'comprehensive_fix.php', 'system_audit.php'
];

foreach ($rootFiles as $file) {
    if ($file === '.' || $file === '..') continue;
    
    $filePath = $baseDir . DIRECTORY_SEPARATOR . $file;
    if (!is_file($filePath)) continue;
    
    foreach ($testPatterns as $pattern) {
        if (stripos($file, $pattern) === 0 || $file === $pattern) {
            $results['test_files'][] = [
                'file' => $file,
                'size' => filesize($filePath),
                'modified' => date('Y-m-d H:i:s', filemtime($filePath))
            ];
            break;
        }
    }
}

echo "   Found " . count($results['test_files']) . " test/diagnostic files\n\n";

// 2. Check for PHP syntax errors in all files
echo "[2/7] 🐛 Checking PHP syntax errors...\n";
$phpFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$errorCount = 0;
foreach ($phpFiles as $file) {
    if ($file->getExtension() !== 'php') continue;
    
    // Skip vendor, node_modules, backups
    $path = $file->getPathname();
    if (strpos($path, 'node_modules') !== false || 
        strpos($path, 'vendor') !== false ||
        strpos($path, 'backups') !== false) {
        continue;
    }
    
    $output = [];
    $returnVar = 0;
    exec('php -l "' . $path . '" 2>&1', $output, $returnVar);
    
    if ($returnVar !== 0) {
        $results['errors'][] = [
            'file' => str_replace($baseDir . DIRECTORY_SEPARATOR, '', $path),
            'error' => implode("\n", $output)
        ];
        $errorCount++;
    }
}

echo "   Found $errorCount syntax errors\n\n";

// 3. Identify potentially unused files
echo "[3/7] 📁 Identifying potentially unused files...\n";

$potentiallyUnused = [
    'ville_de_palme.png' => 'Image file in root (should be in assets/images/)',
    'FUNCTIONALITY_REVIEW_AND_FIXES.md' => 'Old documentation',
    'SYSTEM_IMPROVEMENTS_IMPLEMENTATION_GUIDE.md' => 'Old documentation',
];

foreach ($potentiallyUnused as $file => $reason) {
    if (file_exists($baseDir . DIRECTORY_SEPARATOR . $file)) {
        $results['unused_files'][] = [
            'file' => $file,
            'reason' => $reason
        ];
    }
}

echo "   Found " . count($results['unused_files']) . " potentially unused files\n\n";

// 4. Check for required files
echo "[4/7] ✅ Checking for required system files...\n";

$requiredFiles = [
    'config.php' => 'Database configuration',
    'db.php' => 'Database connection',
    'index.php' => 'Entry point',
    '.htaccess' => 'Apache configuration',
    'robots.txt' => 'SEO configuration',
    'README.md' => 'Documentation',
    'auth/login.php' => 'Login page',
    'auth/logout.php' => 'Logout handler',
    'admin/admin_panel.php' => 'Admin dashboard',
    'guard/pages/guard_side.php' => 'Guard dashboard',
    'homeowners/portal.php' => 'Homeowner portal',
    'includes/security_headers.php' => 'Security headers',
    'includes/session_admin.php' => 'Admin session handler',
    'includes/session_guard.php' => 'Guard session handler',
];

foreach ($requiredFiles as $file => $description) {
    $filePath = $baseDir . DIRECTORY_SEPARATOR . $file;
    if (!file_exists($filePath)) {
        $results['missing_files'][] = [
            'file' => $file,
            'description' => $description
        ];
    }
}

echo "   " . (count($requiredFiles) - count($results['missing_files'])) . "/" . count($requiredFiles) . " required files present\n";
if (count($results['missing_files']) > 0) {
    echo "   ⚠️  Missing " . count($results['missing_files']) . " required files\n";
}
echo "\n";

// 5. Check for duplicate/overlapping functionality
echo "[5/7] 🔄 Checking for duplicate functionality...\n";

// Check for multiple config files
$configFiles = glob($baseDir . '/*.php');
$configCount = 0;
foreach ($configFiles as $file) {
    if (stripos(basename($file), 'config') !== false) {
        $configCount++;
    }
}

if ($configCount > 1) {
    echo "   ⚠️  Multiple config files found\n";
}

// Check _testing folder
$testingDir = $baseDir . DIRECTORY_SEPARATOR . '_testing';
if (is_dir($testingDir)) {
    $testFiles = scandir($testingDir);
    $testFileCount = count(array_filter($testFiles, function($f) use ($testingDir) {
        return $f !== '.' && $f !== '..' && is_file($testingDir . DIRECTORY_SEPARATOR . $f);
    }));
    echo "   Found $testFileCount files in _testing/ folder\n";
}

echo "\n";

// 6. Check database connectivity
echo "[6/7] 🗄️  Testing database connection...\n";
try {
    require_once $baseDir . '/db.php';
    echo "   ✅ Database connection successful\n";
    
    // Check tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "   📊 Found " . count($tables) . " database tables\n";
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
    $results['errors'][] = ['file' => 'Database', 'error' => $e->getMessage()];
}
echo "\n";

// 7. Generate cleanup recommendations
echo "[7/7] 📋 Generating cleanup recommendations...\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "                    CLEANUP REPORT                          \n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Test files
if (count($results['test_files']) > 0) {
    echo "🧪 TEST/DIAGNOSTIC FILES TO MOVE TO _diagnostics/ (" . count($results['test_files']) . "):\n";
    foreach ($results['test_files'] as $file) {
        echo "   - {$file['file']} (" . round($file['size']/1024, 2) . " KB)\n";
    }
    echo "\n";
}

// Unused files
if (count($results['unused_files']) > 0) {
    echo "📦 POTENTIALLY UNUSED FILES (" . count($results['unused_files']) . "):\n";
    foreach ($results['unused_files'] as $file) {
        echo "   - {$file['file']}\n";
        echo "     Reason: {$file['reason']}\n";
    }
    echo "\n";
}

// Missing files
if (count($results['missing_files']) > 0) {
    echo "⚠️  MISSING REQUIRED FILES (" . count($results['missing_files']) . "):\n";
    foreach ($results['missing_files'] as $file) {
        echo "   - {$file['file']} - {$file['description']}\n";
    }
    echo "\n";
}

// Errors
if (count($results['errors']) > 0) {
    echo "❌ ERRORS FOUND (" . count($results['errors']) . "):\n";
    foreach ($results['errors'] as $error) {
        echo "   - {$error['file']}\n";
        echo "     " . substr($error['error'], 0, 100) . "...\n";
    }
    echo "\n";
}

// Recommendations
echo "═══════════════════════════════════════════════════════════\n";
echo "                  RECOMMENDATIONS                           \n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "1. Move test files to _diagnostics/ folder:\n";
echo "   • Keeps root clean and organized\n";
echo "   • Easy to delete when done testing\n\n";

echo "2. Move _testing/ folder contents to _diagnostics/:\n";
echo "   • Consolidate all diagnostic files\n";
echo "   • Single location for cleanup\n\n";

echo "3. Move documentation files to docs/ folder:\n";
echo "   • Better organization\n";
echo "   • Separate code from documentation\n\n";

echo "4. Create missing essential files if needed\n\n";

echo "5. Fix any PHP syntax errors found\n\n";

// Save results to JSON
$reportFile = $baseDir . '/_diagnostics/cleanup_report.json';
file_put_contents($reportFile, json_encode($results, JSON_PRETTY_PRINT));
echo "📄 Full report saved to: _diagnostics/cleanup_report.json\n\n";

echo "✅ Analysis complete!\n";
