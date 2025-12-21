#!/usr/bin/env php
<?php
/**
 * COMPREHENSIVE SYSTEM AUDIT SCRIPT
 * Checks for syntax errors, unused files, and code issues
 */

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║     VEHISCAN RFID - COMPREHENSIVE SYSTEM AUDIT            ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$baseDir = __DIR__;
$errors = [];
$warnings = [];
$phpFiles = [];
$stats = [
    'total_files' => 0,
    'syntax_errors' => 0,
    'warnings' => 0,
    'unused_files' => 0
];

// 1. FIND ALL PHP FILES
echo "[1/7] 🔍 Scanning for PHP files...\n";
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $phpFiles[] = $file->getPathname();
    }
}
$stats['total_files'] = count($phpFiles);
echo "   ✅ Found {$stats['total_files']} PHP files\n\n";

// 2. CHECK PHP SYNTAX
echo "[2/7] 🔎 Checking PHP syntax...\n";
foreach ($phpFiles as $file) {
    $output = [];
    $return = 0;
    exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return);
    
    if ($return !== 0) {
        $errors[] = "SYNTAX ERROR: $file\n   " . implode("\n   ", $output);
        $stats['syntax_errors']++;
    }
}
if ($stats['syntax_errors'] === 0) {
    echo "   ✅ No syntax errors found\n\n";
} else {
    echo "   ❌ Found {$stats['syntax_errors']} syntax errors\n\n";
}

// 3. CHECK FOR COMMON ISSUES
echo "[3/7] ⚠️  Checking for common code issues...\n";
foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    $relPath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file);
    
    // Check for inline SQL without prepared statements
    if (preg_match('/\$pdo->query\s*\(\s*["\'].*\$/', $content)) {
        $warnings[] = "SQL INJECTION RISK: $relPath - Uses query() with variables";
        $stats['warnings']++;
    }
    
    // Check for PHP short tags (but allow <?= which is valid)
    if (preg_match('/<\?\s[^p]|<\?\s*$/', $content)) {
        $warnings[] = "SHORT TAG: $relPath - Uses PHP short tags (not recommended)";
        $stats['warnings']++;
    }
    
    // Check for error suppression
    if (preg_match('/@(include|require|fopen|file_get_contents)/', $content)) {
        $warnings[] = "ERROR SUPPRESSION: $relPath - Uses @ operator";
        $stats['warnings']++;
    }
    
    // Check for eval()
    if (preg_match('/\beval\s*\(/', $content)) {
        $warnings[] = "SECURITY RISK: $relPath - Uses eval()";
        $stats['warnings']++;
    }
}
if ($stats['warnings'] === 0) {
    echo "   ✅ No common issues found\n\n";
} else {
    echo "   ⚠️  Found {$stats['warnings']} warnings\n\n";
}

// 4. CHECK DATABASE SCHEMA
echo "[4/7] 🗄️  Checking database schema...\n";
try {
    require_once $baseDir . '/db.php';
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "   ✅ Database connected ({pdo->getAttribute(PDO::ATTR_DRIVER_NAME)})\n";
    echo "   📊 Tables: " . count($tables) . " found\n";
    
    // Check for access_logs table
    if (!in_array('access_logs', $tables)) {
        $errors[] = "DATABASE: Missing 'access_logs' table (will cause errors in guard panel)";
    }
    
    // Check users table structure
    $userCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('email', $userCols)) {
        $errors[] = "DATABASE: users table missing 'email' column";
    }
    if (!in_array('account_status', $userCols)) {
        $errors[] = "DATABASE: users table missing 'account_status' column";
    }
    
    // Check homeowners table
    $homeCols = $pdo->query("SHOW COLUMNS FROM homeowners")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('email', $homeCols)) {
        $errors[] = "DATABASE: homeowners table missing 'email' column";
    }
    if (!in_array('username', $homeCols)) {
        $errors[] = "DATABASE: homeowners table missing 'username' column";
    }
    
    // Check for vehicles table
    if (!in_array('vehicles', $tables)) {
        $warnings[] = "DATABASE: 'vehicles' table not found (multi-vehicle feature won't work)";
        $stats['warnings']++;
    }
    
    echo "   ✅ Database schema check complete\n\n";
} catch (PDOException $e) {
    $errors[] = "DATABASE CONNECTION FAILED: " . $e->getMessage();
}

// 5. CHECK FOR UNREFERENCED FILES
echo "[5/7] 📁 Checking for potentially unused files...\n";
$unusedSuspects = [];

// Files that might be directly accessed (not included)
$directAccessPatterns = [
    '/admin_panel.php$/',
    '/login.php$/',
    '/register.php$/',
    '/portal.php$/',
    '/guard_side.php$/',
    '/index.php$/'
];

foreach ($phpFiles as $file) {
    $relPath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file);
    $basename = basename($file);
    
    // Skip known direct-access files
    $isDirect = false;
    foreach ($directAccessPatterns as $pattern) {
        if (preg_match($pattern, $relPath)) {
            $isDirect = true;
            break;
        }
    }
    
    if ($isDirect) continue;
    
    // Check if file is referenced anywhere
    $isReferenced = false;
    $searchName = str_replace('\\', '/', $relPath);
    
    foreach ($phpFiles as $checkFile) {
        if ($checkFile === $file) continue;
        
        $content = file_get_contents($checkFile);
        if (strpos($content, $basename) !== false) {
            $isReferenced = true;
            break;
        }
    }
    
    if (!$isReferenced) {
        $unusedSuspects[] = $relPath;
        $stats['unused_files']++;
    }
}

if (count($unusedSuspects) === 0) {
    echo "   ✅ All files appear to be referenced\n\n";
} else {
    echo "   ⚠️  Found " . count($unusedSuspects) . " potentially unused files\n\n";
}

// 6. CHECK REQUIRED FILES EXIST
echo "[6/7] 📋 Checking required system files...\n";
$requiredFiles = [
    'db.php' => 'Database configuration',
    'config.php' => 'System configuration',
    'auth/login.php' => 'Login page',
    'homeowners/homeowner_registration.php' => 'Homeowner registration page',
    'admin/admin_panel.php' => 'Admin panel',
    'guard/pages/guard_side.php' => 'Guard interface',
    'homeowners/portal.php' => 'Homeowner portal',
    'ville_de_palme.png' => 'Subdivision logo',
];

$missingFiles = [];
foreach ($requiredFiles as $file => $desc) {
    if (!file_exists($baseDir . '/' . $file)) {
        $missingFiles[] = "$file ($desc)";
        $errors[] = "MISSING FILE: $file - $desc";
    }
}

if (count($missingFiles) === 0) {
    echo "   ✅ All required files present\n\n";
} else {
    echo "   ❌ Missing " . count($missingFiles) . " required files\n\n";
}

// 7. GENERATE REPORT
echo "[7/7] 📄 Generating report...\n\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║                    AUDIT SUMMARY                          ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "📊 STATISTICS:\n";
echo "   Total PHP files scanned: {$stats['total_files']}\n";
echo "   Syntax errors: {$stats['syntax_errors']}\n";
echo "   Warnings: {$stats['warnings']}\n";
echo "   Potentially unused files: {$stats['unused_files']}\n\n";

if (count($errors) > 0) {
    echo "❌ ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $i => $error) {
        echo "   " . ($i + 1) . ". $error\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠️  WARNINGS (first 10):\n";
    foreach (array_slice($warnings, 0, 10) as $i => $warning) {
        echo "   " . ($i + 1) . ". $warning\n";
    }
    if (count($warnings) > 10) {
        echo "   ... and " . (count($warnings) - 10) . " more\n";
    }
    echo "\n";
}

if (count($unusedSuspects) > 0 && count($unusedSuspects) <= 20) {
    echo "📁 POTENTIALLY UNUSED FILES:\n";
    foreach ($unusedSuspects as $file) {
        echo "   - $file\n";
    }
    echo "\n";
}

if (count($errors) === 0 && count($warnings) === 0) {
    echo "✅ ═══════════════════════════════════════════════════════════\n";
    echo "   SYSTEM STATUS: HEALTHY\n";
    echo "   No critical issues detected.\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
} else {
    echo "⚠️  ═══════════════════════════════════════════════════════════\n";
    echo "   SYSTEM STATUS: NEEDS ATTENTION\n";
    echo "   Please review errors and warnings above.\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
}

// Save report
$report = ob_get_contents();
file_put_contents($baseDir . '/SYSTEM_AUDIT_REPORT.md', "# System Audit Report\n\nGenerated: " . date('Y-m-d H:i:s') . "\n\n```\n" . implode("\n", array_merge($errors, $warnings)) . "\n```\n");

echo "📝 Full report saved to: SYSTEM_AUDIT_REPORT.md\n";
