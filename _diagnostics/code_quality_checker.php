<?php
/**
 * Code Quality & Error Detection Tool
 * Checks for overlapping code, duplicates, and common issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$baseDir = dirname(__DIR__);
$issues = [
    'duplicate_functions' => [],
    'unused_variables' => [],
    'security_issues' => [],
    'todo_comments' => [],
    'long_functions' => [],
    'code_smells' => []
];

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║       CODE QUALITY & ERROR DETECTION                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Get all PHP files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

$phpFiles = [];
foreach ($iterator as $file) {
    if ($file->getExtension() === 'php') {
        $path = $file->getPathname();
        // Skip vendor, node_modules, backups, diagnostics
        if (strpos($path, 'node_modules') !== false || 
            strpos($path, 'vendor') !== false ||
            strpos($path, 'backups') !== false ||
            strpos($path, '_diagnostics') !== false ||
            strpos($path, 'phpqrcode') !== false) {
            continue;
        }
        $phpFiles[] = $path;
    }
}

echo "📊 Analyzing " . count($phpFiles) . " PHP files...\n\n";

// 1. Check for duplicate function definitions
echo "[1/6] 🔍 Checking for duplicate function definitions...\n";
$functionDefs = [];
foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    $relPath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file);
    
    preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $content, $matches);
    
    foreach ($matches[1] as $funcName) {
        if (!isset($functionDefs[$funcName])) {
            $functionDefs[$funcName] = [];
        }
        $functionDefs[$funcName][] = $relPath;
    }
}

foreach ($functionDefs as $func => $files) {
    if (count($files) > 1) {
        $issues['duplicate_functions'][] = [
            'function' => $func,
            'files' => $files
        ];
    }
}

echo "   Found " . count($issues['duplicate_functions']) . " duplicate function names\n\n";

// 2. Check for security issues
echo "[2/6] 🔒 Scanning for security vulnerabilities...\n";
$securityPatterns = [
    '/\$_(GET|POST|REQUEST|COOKIE)\[.*?\](?!\s*\))/' => 'Direct user input usage (potential injection)',
    '/eval\s*\(/' => 'Dangerous eval() usage',
    '/exec\s*\(|shell_exec\s*\(|system\s*\(/' => 'Command execution detected',
    '/md5\s*\(.*password/i' => 'Weak password hashing (use password_hash)',
    '/mysqli_query\s*\([^,]+,\s*["\'].*\$/' => 'Potential SQL injection (use prepared statements)',
];

$securityIssueCount = 0;
foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    $relPath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file);
    $lines = explode("\n", $content);
    
    foreach ($securityPatterns as $pattern => $description) {
        if (preg_match($pattern, $content)) {
            foreach ($lines as $lineNum => $line) {
                if (preg_match($pattern, $line)) {
                    // Skip if it's in a comment
                    if (strpos(trim($line), '//') === 0 || strpos(trim($line), '#') === 0) {
                        continue;
                    }
                    
                    $issues['security_issues'][] = [
                        'file' => $relPath,
                        'line' => $lineNum + 1,
                        'issue' => $description,
                        'code' => trim($line)
                    ];
                    $securityIssueCount++;
                    break;
                }
            }
        }
    }
}

echo "   Found $securityIssueCount potential security issues\n\n";

// 3. Check for TODOs and FIXMEs
echo "[3/6] 📝 Scanning for TODO and FIXME comments...\n";
foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    $relPath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file);
    $lines = explode("\n", $content);
    
    foreach ($lines as $lineNum => $line) {
        if (preg_match('/(TODO|FIXME|XXX|HACK):\s*(.+)/i', $line, $match)) {
            $issues['todo_comments'][] = [
                'file' => $relPath,
                'line' => $lineNum + 1,
                'type' => strtoupper($match[1]),
                'comment' => trim($match[2])
            ];
        }
    }
}

echo "   Found " . count($issues['todo_comments']) . " TODO/FIXME comments\n\n";

// 4. Check for long functions (code smell)
echo "[4/6] 📏 Checking for long functions (>100 lines)...\n";
foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    $relPath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file);
    
    preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\([^)]*\)\s*{/', $content, $matches, PREG_OFFSET_CAPTURE);
    
    foreach ($matches[0] as $idx => $match) {
        $startPos = $match[1];
        $funcName = $matches[1][$idx][0];
        
        // Find matching closing brace
        $braceCount = 0;
        $inFunction = false;
        $lineCount = 0;
        
        for ($i = $startPos; $i < strlen($content); $i++) {
            if ($content[$i] === '{') {
                $braceCount++;
                $inFunction = true;
            }
            if ($content[$i] === '}') {
                $braceCount--;
            }
            if ($content[$i] === "\n" && $inFunction) {
                $lineCount++;
            }
            if ($braceCount === 0 && $inFunction) {
                break;
            }
        }
        
        if ($lineCount > 100) {
            $issues['long_functions'][] = [
                'file' => $relPath,
                'function' => $funcName,
                'lines' => $lineCount
            ];
        }
    }
}

echo "   Found " . count($issues['long_functions']) . " long functions\n\n";

// 5. Check for common code smells
echo "[5/6] 👃 Detecting code smells...\n";
$smellPatterns = [
    '/\$\$/' => 'Variable variables usage',
    '/@\$/' => 'Error suppression with @',
    '/die\s*\(|exit\s*\(/' => 'Hard exit detected (use proper error handling)',
];

foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    $relPath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file);
    
    foreach ($smellPatterns as $pattern => $description) {
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                // Count line number
                $lineNum = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                $issues['code_smells'][] = [
                    'file' => $relPath,
                    'line' => $lineNum,
                    'smell' => $description
                ];
            }
        }
    }
}

echo "   Found " . count($issues['code_smells']) . " code smells\n\n";

// 6. Generate detailed report
echo "[6/6] 📄 Generating detailed report...\n\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "                  CODE QUALITY REPORT                       \n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Duplicate functions
if (count($issues['duplicate_functions']) > 0) {
    echo "🔄 DUPLICATE FUNCTION DEFINITIONS (" . count($issues['duplicate_functions']) . "):\n";
    foreach (array_slice($issues['duplicate_functions'], 0, 5) as $dup) {
        echo "   Function: {$dup['function']}\n";
        echo "   Found in:\n";
        foreach ($dup['files'] as $file) {
            echo "      - $file\n";
        }
        echo "\n";
    }
    if (count($issues['duplicate_functions']) > 5) {
        echo "   ... and " . (count($issues['duplicate_functions']) - 5) . " more\n\n";
    }
}

// Security issues
if (count($issues['security_issues']) > 0) {
    echo "🔒 SECURITY CONCERNS (" . count($issues['security_issues']) . "):\n";
    foreach (array_slice($issues['security_issues'], 0, 10) as $issue) {
        echo "   {$issue['file']}:{$issue['line']}\n";
        echo "   Issue: {$issue['issue']}\n";
        echo "   Code: " . substr($issue['code'], 0, 80) . "...\n\n";
    }
    if (count($issues['security_issues']) > 10) {
        echo "   ... and " . (count($issues['security_issues']) - 10) . " more\n\n";
    }
}

// TODOs
if (count($issues['todo_comments']) > 0) {
    echo "📝 TODO/FIXME COMMENTS (" . count($issues['todo_comments']) . "):\n";
    foreach (array_slice($issues['todo_comments'], 0, 10) as $todo) {
        echo "   [{$todo['type']}] {$todo['file']}:{$todo['line']}\n";
        echo "   {$todo['comment']}\n\n";
    }
    if (count($issues['todo_comments']) > 10) {
        echo "   ... and " . (count($issues['todo_comments']) - 10) . " more\n\n";
    }
}

// Long functions
if (count($issues['long_functions']) > 0) {
    echo "📏 LONG FUNCTIONS (" . count($issues['long_functions']) . "):\n";
    foreach ($issues['long_functions'] as $func) {
        echo "   {$func['file']} - {$func['function']}() ({$func['lines']} lines)\n";
    }
    echo "\n";
}

// Save full report
$reportFile = $baseDir . '/_diagnostics/code_quality_report.json';
file_put_contents($reportFile, json_encode($issues, JSON_PRETTY_PRINT));

echo "═══════════════════════════════════════════════════════════\n";
echo "                      SUMMARY                               \n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "📊 Files analyzed: " . count($phpFiles) . "\n";
echo "🔄 Duplicate functions: " . count($issues['duplicate_functions']) . "\n";
echo "🔒 Security issues: " . count($issues['security_issues']) . "\n";
echo "📝 TODO comments: " . count($issues['todo_comments']) . "\n";
echo "📏 Long functions: " . count($issues['long_functions']) . "\n";
echo "👃 Code smells: " . count($issues['code_smells']) . "\n\n";

echo "📄 Full report saved to: _diagnostics/code_quality_report.json\n\n";

// Overall score
$totalIssues = count($issues['duplicate_functions']) + 
               count($issues['security_issues']) + 
               count($issues['long_functions']) + 
               count($issues['code_smells']);

if ($totalIssues === 0) {
    echo "✅ EXCELLENT: No critical issues found!\n";
} elseif ($totalIssues < 10) {
    echo "✅ GOOD: Few issues found - system is in good shape\n";
} elseif ($totalIssues < 50) {
    echo "⚠️  FAIR: Some issues found - consider addressing them\n";
} else {
    echo "❌ NEEDS ATTENTION: Many issues found - cleanup recommended\n";
}

echo "\n✨ Analysis complete!\n";
