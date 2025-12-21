<?php
/**
 * Enhanced System Diagnostic
 * Checks for missing files, overlapping code, database consistency
 */

require_once __DIR__ . '/../db.php';

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         ENHANCED SYSTEM DIAGNOSTIC                         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$issues = [];
$warnings = [];

// 1. Check for duplicate session files
echo "[1/6] 🔍 Checking for duplicate/overlapping files...\n";
$sessionFiles = [
    'includes/session_admin.php',
    'includes/session_admin_unified.php',
    'includes/session_guard.php',
    'includes/session_homeowner.php',
    'includes/session_super_admin.php'
];

$foundSessions = [];
foreach ($sessionFiles as $file) {
    if (file_exists($file)) {
        $foundSessions[] = $file;
    }
}
echo "Found session files: " . count($foundSessions) . "\n";
foreach ($foundSessions as $file) {
    echo "  - $file\n";
}

// 2. Check database tables
echo "\n[2/6] 🗄️ Checking database structure...\n";
$requiredTables = [
    'homeowners', 'homeowner_auth', 'users', 'access_logs', 
    'visitor_passes', 'vehicles', 'audit_logs'
];

$stmt = $pdo->query("SHOW TABLES");
$existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($requiredTables as $table) {
    if (!in_array($table, $existingTables)) {
        $issues[] = "Missing table: $table";
        echo "❌ Missing: $table\n";
    } else {
        echo "✅ Found: $table\n";
    }
}

// 3. Check homeowners table structure
echo "\n[3/6] 📋 Checking homeowners table columns...\n";
$stmt = $pdo->query("DESCRIBE homeowners");
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
$requiredColumns = ['id', 'first_name', 'last_name', 'email', 'account_status', 'created_at'];

foreach ($requiredColumns as $col) {
    if (!in_array($col, $columns)) {
        $issues[] = "Missing column in homeowners: $col";
        echo "❌ Missing column: $col\n";
    } else {
        echo "✅ Column exists: $col\n";
    }
}

// 4. Check for pending accounts
echo "\n[4/6] 👤 Checking pending accounts...\n";
$pendingCount = $pdo->query("SELECT COUNT(*) FROM homeowners WHERE account_status = 'pending'")->fetchColumn();
$approvedCount = $pdo->query("SELECT COUNT(*) FROM homeowners WHERE account_status = 'approved'")->fetchColumn();
$rejectedCount = $pdo->query("SELECT COUNT(*) FROM homeowners WHERE account_status = 'rejected'")->fetchColumn();

echo "Pending: $pendingCount\n";
echo "Approved: $approvedCount\n";
echo "Rejected: $rejectedCount\n";

if ($pendingCount > 0) {
    $warnings[] = "$pendingCount pending accounts awaiting approval";
}

// 5. Check for orphaned records
echo "\n[5/6] 🔗 Checking data consistency...\n";

// Homeowners without auth
$stmt = $pdo->query("SELECT COUNT(*) FROM homeowners h 
                     LEFT JOIN homeowner_auth ha ON h.id = ha.homeowner_id 
                     WHERE ha.homeowner_id IS NULL");
$orphanedHomeowners = $stmt->fetchColumn();

if ($orphanedHomeowners > 0) {
    $issues[] = "$orphanedHomeowners homeowners without auth records";
    echo "❌ $orphanedHomeowners homeowners missing auth records\n";
} else {
    echo "✅ All homeowners have auth records\n";
}

// 6. Check critical files
echo "\n[6/6] 📁 Checking critical system files...\n";
$criticalFiles = [
    'admin/admin_panel.php',
    'admin/fetch/fetch_approvals.php',
    'admin/components/approvals_page.php',
    'admin/api/approve_user_account.php',
    'admin/api/get_pending_accounts.php',
    'auth/login.php',
    'homeowners/homeowner_registration.php',
    'guard/pages/guard_side.php',
    'includes/input_sanitizer.php',
    'includes/rate_limiter.php'
];

foreach ($criticalFiles as $file) {
    if (!file_exists($file)) {
        $issues[] = "Missing critical file: $file";
        echo "❌ Missing: $file\n";
    } else {
        echo "✅ Found: $file\n";
    }
}

// Summary
echo "\n═══════════════════════════════════════════════════════════\n";
echo "                    DIAGNOSTIC SUMMARY\n";
echo "═══════════════════════════════════════════════════════════\n\n";

echo "Issues Found: " . count($issues) . "\n";
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
    echo "\n🎉 System is clean! No issues or warnings found.\n";
} elseif (count($issues) === 0) {
    echo "\n✅ No critical issues found. Only warnings present.\n";
} else {
    echo "\n⚠️  Critical issues detected! Please review and fix.\n";
}
