<?php
/**
 * Automated System Cleanup Script
 * Moves test files and organizes the system
 */

$baseDir = dirname(__DIR__);
$moved = [];
$errors = [];

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         EXECUTING SYSTEM CLEANUP                           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// 1. Move test files from root to _diagnostics
echo "[1/4] 📦 Moving test files to _diagnostics/...\n";

$testFiles = [
    'audit_report.html',
    'audit_results.txt',
    'check_db_structure.php',
    'check_homeowners_columns.php',
    'comprehensive_fix.php',
    'debug_visitor_pass.php',
    'quick_check_homeowners.php',
    'system_audit.php',
    'test_login_system.html',
    'test_token.php',
    'test_visitor_filter.php'
];

foreach ($testFiles as $file) {
    $source = $baseDir . DIRECTORY_SEPARATOR . $file;
    $dest = $baseDir . DIRECTORY_SEPARATOR . '_diagnostics' . DIRECTORY_SEPARATOR . $file;
    
    if (file_exists($source)) {
        if (rename($source, $dest)) {
            $moved[] = $file;
            echo "   ✅ Moved: $file\n";
        } else {
            $errors[] = "Failed to move: $file";
            echo "   ❌ Failed: $file\n";
        }
    }
}

echo "   Moved " . count($moved) . " files\n\n";

// 2. Move _testing folder contents to _diagnostics/testing
echo "[2/4] 📁 Moving _testing/ folder to _diagnostics/testing/...\n";

$testingSource = $baseDir . DIRECTORY_SEPARATOR . '_testing';
$testingDest = $baseDir . DIRECTORY_SEPARATOR . '_diagnostics' . DIRECTORY_SEPARATOR . 'testing';

if (is_dir($testingSource)) {
    if (!is_dir($testingDest)) {
        mkdir($testingDest, 0755, true);
    }
    
    $testingFiles = scandir($testingSource);
    $movedTesting = 0;
    
    foreach ($testingFiles as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $source = $testingSource . DIRECTORY_SEPARATOR . $file;
        $dest = $testingDest . DIRECTORY_SEPARATOR . $file;
        
        if (is_file($source)) {
            if (rename($source, $dest)) {
                $movedTesting++;
                echo "   ✅ Moved: $file\n";
            }
        }
    }
    
    // Remove empty _testing folder
    if (count(scandir($testingSource)) === 2) {
        rmdir($testingSource);
        echo "   🗑️  Removed empty _testing/ folder\n";
    }
    
    echo "   Moved $movedTesting files\n\n";
}

// 3. Move documentation to docs/
echo "[3/4] 📚 Organizing documentation...\n";

$docsDir = $baseDir . DIRECTORY_SEPARATOR . 'docs';
if (!is_dir($docsDir)) {
    mkdir($docsDir, 0755, true);
}

$docFiles = [
    'FUNCTIONALITY_REVIEW_AND_FIXES.md',
    'SYSTEM_IMPROVEMENTS_IMPLEMENTATION_GUIDE.md',
    'CODE_QUALITY_FIXES_SUMMARY.md',
    'LOGIN_FIXES_SUMMARY.md',
    'SYSTEM_AUDIT_REPORT.md'
];

$movedDocs = 0;
foreach ($docFiles as $file) {
    $source = $baseDir . DIRECTORY_SEPARATOR . $file;
    $dest = $docsDir . DIRECTORY_SEPARATOR . $file;
    
    if (file_exists($source)) {
        if (rename($source, $dest)) {
            $movedDocs++;
            echo "   ✅ Moved: $file\n";
        }
    }
}

echo "   Moved $movedDocs documentation files\n\n";

// 4. Move image to proper location
echo "[4/4] 🖼️  Moving assets to proper location...\n";

$imagesDir = $baseDir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images';
if (!is_dir($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

$imageFile = 'ville_de_palme.png';
$source = $baseDir . DIRECTORY_SEPARATOR . $imageFile;
$dest = $imagesDir . DIRECTORY_SEPARATOR . $imageFile;

if (file_exists($source)) {
    if (rename($source, $dest)) {
        echo "   ✅ Moved: $imageFile to assets/images/\n";
    }
}

echo "\n";

// Summary
echo "═══════════════════════════════════════════════════════════\n";
echo "                    CLEANUP SUMMARY                         \n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "✅ Moved " . count($moved) . " test files to _diagnostics/\n";
echo "✅ Organized documentation in docs/\n";
echo "✅ Moved assets to proper folders\n\n";

if (count($errors) > 0) {
    echo "⚠️  Errors encountered:\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
    echo "\n";
}

echo "📁 Root directory is now clean and organized!\n";
echo "📂 _diagnostics/ - Contains all test/diagnostic files\n";
echo "📂 docs/ - Contains all documentation\n\n";

echo "✨ Cleanup complete!\n";
