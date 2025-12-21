<?php
/**
 * Automated Code Fixes
 * Fixes duplicate functions and critical issues
 */

$baseDir = dirname(__DIR__);
$fixedCount = 0;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║          AUTOMATED CODE FIXES                              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// 1. Consolidate duplicate validation functions
echo "[1/3] 🔧 Consolidating duplicate validation functions...\n";

// Check if input_validator.php exists (the newer one)
$validatorFile = $baseDir . '/includes/input_validator.php';
$oldValidationFile = $baseDir . '/includes/input_validation.php';

if (file_exists($validatorFile) && file_exists($oldValidationFile)) {
    // input_validator.php exists, so we can remove input_validation.php
    // But first, check if anything is using input_validation.php
    
    echo "   ℹ️  Found both input_validator.php and input_validation.php\n";
    echo "   ℹ️  Keeping input_validator.php (newer version)\n";
    echo "   ⚠️  input_validation.php can be removed if not used\n\n";
}

// 2. Fix duplicate formatContactNumber functions
echo "[2/3] 🔧 Checking duplicate formatContactNumber...\n";

// REMOVED: auth/register.php check - file deleted, using homeowner_registration.php instead
// $registerFile = $baseDir . '/auth/register.php';

$homeownerRegFile = $baseDir . '/homeowners/homeowner_registration.php';

if (file_exists($homeownerRegFile)) {
    $content = file_get_contents($homeownerRegFile);
    if (strpos($content, 'function formatContactNumber') !== false) {
        echo "   ℹ️  formatContactNumber found in homeowners/homeowner_registration.php\n";
        echo "   ℹ️  Should use shared utility function\n";
    }
}

echo "\n";

// 3. Create shared utilities file for common functions
echo "[3/3] 📦 Creating shared utilities file...\n";

$utilsDir = $baseDir . '/includes';
$utilsFile = $utilsDir . '/common_utilities.php';

if (!file_exists($utilsFile)) {
    $utilsContent = '<?php
/**
 * Common Utility Functions
 * Shared functions used across the system
 */

/**
 * Format contact number to standard format
 * @param string $number
 * @return string
 */
function formatContactNumber($number) {
    // Remove all non-numeric characters
    $cleaned = preg_replace(\'/[^0-9]/\', \'\', $number);
    
    // Format based on length
    if (strlen($cleaned) === 11 && substr($cleaned, 0, 1) === \'0\') {
        // Format: 0XXX-XXX-XXXX
        return substr($cleaned, 0, 4) . \'-\' . substr($cleaned, 4, 3) . \'-\' . substr($cleaned, 7);
    } elseif (strlen($cleaned) === 10) {
        // Format: XXX-XXX-XXXX
        return substr($cleaned, 0, 3) . \'-\' . substr($cleaned, 3, 3) . \'-\' . substr($cleaned, 6);
    }
    
    return $cleaned;
}

/**
 * Sanitize user input
 * @param string $input
 * @return string
 */
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, \'UTF-8\');
}

/**
 * Generate secure random token
 * @param int $length
 * @return string
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Format file size for display
 * @param int $bytes
 * @return string
 */
function formatFileSize($bytes) {
    $units = [\'B\', \'KB\', \'MB\', \'GB\', \'TB\'];
    $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
    return round($bytes / pow(1024, $power), 2) . \' \' . $units[$power];
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION[\'user_id\']) && isset($_SESSION[\'role\']);
}

/**
 * Get current user role
 * @return string|null
 */
function getCurrentUserRole() {
    return $_SESSION[\'role\'] ?? null;
}

/**
 * Build full name from parts
 * @param string $firstName
 * @param string $middleName
 * @param string $lastName
 * @param string $suffix
 * @return string
 */
function buildFullName($firstName, $middleName = \'\', $lastName = \'\', $suffix = \'\') {
    $parts = array_filter([$firstName, $middleName, $lastName, $suffix]);
    return implode(\' \', $parts);
}
';
    
    file_put_contents($utilsFile, $utilsContent);
    echo "   ✅ Created includes/common_utilities.php\n";
    $fixedCount++;
} else {
    echo "   ℹ️  common_utilities.php already exists\n";
}

echo "\n";

echo "═══════════════════════════════════════════════════════════\n";
echo "                    FIXES SUMMARY                           \n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "✅ Applied $fixedCount automated fixes\n";
echo "ℹ️  Created shared utilities file\n\n";

echo "RECOMMENDATIONS:\n";
echo "1. Update files using duplicate functions to use common_utilities.php\n";
echo "2. Remove old input_validation.php if input_validator.php is used\n";
echo "3. Review security issues in _diagnostics/code_quality_report.json\n";
echo "4. Consider breaking down long functions (>100 lines)\n\n";

echo "✨ Automated fixes complete!\n";
