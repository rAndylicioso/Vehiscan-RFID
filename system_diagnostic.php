<?php
/**
 * Comprehensive System Diagnostic
 * Checks database, files, connections, and configurations
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><title>System Diagnostic</title>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .success { color: #22c55e; font-weight: bold; }
    .error { color: #ef4444; font-weight: bold; }
    .warning { color: #f59e0b; font-weight: bold; }
    h1 { color: #333; }
    h2 { color: #667eea; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
    ul { line-height: 1.8; }
    .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
    .stat-card { background: #f8f9fa; padding: 15px; border-radius: 6px; border-left: 4px solid #667eea; }
</style></head><body>";

echo "<h1>🔍 VehiScan RFID - System Diagnostic Report</h1>";
echo "<p>Generated: " . date('Y-m-d H:i:s') . "</p>";

$errors = [];
$warnings = [];
$success = [];

// 1. DATABASE CONNECTION TEST
echo "<div class='section'><h2>1. Database Connection</h2>";
try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/db.php';
    echo "<p class='success'>✅ Database connected successfully</p>";
    echo "<ul>";
    echo "<li>Host: " . DB_HOST . "</li>";
    echo "<li>Database: " . DB_NAME . "</li>";
    echo "<li>User: " . DB_USER . "</li>";
    echo "</ul>";
    $success[] = "Database connection working";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    $errors[] = "Database connection failed";
}
echo "</div>";

// 2. TABLE STRUCTURE CHECK
echo "<div class='section'><h2>2. Database Tables</h2>";
if (isset($pdo)) {
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "<p class='success'>✅ Found " . count($tables) . " tables</p>";
    echo "<div class='stats'>";
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "<div class='stat-card'><strong>$table</strong><br>$count records</div>";
    }
    echo "</div>";

    // Check critical tables
    $criticalTables = ['users', 'super_admin', 'homeowners', 'access_logs', 'visitor_passes'];
    foreach ($criticalTables as $table) {
        if (in_array($table, $tables)) {
            $success[] = "Table $table exists";
        } else {
            $errors[] = "Critical table $table is missing";
            echo "<p class='error'>❌ Missing critical table: $table</p>";
        }
    }
}
echo "</div>";

// 3. CRITICAL FILES CHECK
echo "<div class='section'><h2>3. Critical Files</h2>";
$criticalFiles = [
    'config.php' => 'Configuration',
    'db.php' => 'Database connection',
    'auth/login.php' => 'Login page',
    'admin/admin_panel.php' => 'Admin panel',
    'guard/pages/guard_side.php' => 'Guard panel',
    'homeowners/portal.php' => 'Homeowner portal',
    'assets/css/login.css' => 'Login CSS',
    'assets/js/login.js' => 'Login JS',
    'assets/js/libs/sweetalert2.all.min.js' => 'SweetAlert2'
];

echo "<ul>";
foreach ($criticalFiles as $file => $desc) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "<li class='success'>✅ $desc ($file)</li>";
        $success[] = "$desc file exists";
    } else {
        echo "<li class='error'>❌ Missing: $desc ($file)</li>";
        $errors[] = "Missing file: $file";
    }
}
echo "</ul></div>";

// 4. INCLUDES DIRECTORY CHECK
echo "<div class='section'><h2>4. Includes Directory</h2>";
$includesPath = __DIR__ . '/includes';
if (is_dir($includesPath)) {
    $includeFiles = scandir($includesPath);
    $includeFiles = array_diff($includeFiles, ['.', '..']);
    echo "<p class='success'>✅ Found " . count($includeFiles) . " files in includes/</p>";
    echo "<ul>";
    foreach ($includeFiles as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
} else {
    echo "<p class='error'>❌ Includes directory not found</p>";
    $errors[] = "Includes directory missing";
}
echo "</div>";

// 5. SESSION TEST
echo "<div class='section'><h2>5. Session Management</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p class='success'>✅ Sessions working</p>";
    echo "<ul>";
    echo "<li>Session ID: " . session_id() . "</li>";
    echo "<li>Session Name: " . session_name() . "</li>";
    echo "</ul>";
    $success[] = "Session management working";
} else {
    echo "<p class='error'>❌ Session not active</p>";
    $errors[] = "Session management failed";
}
echo "</div>";

// 6. PHP CONFIGURATION
echo "<div class='section'><h2>6. PHP Configuration</h2>";
echo "<ul>";
echo "<li>PHP Version: <strong>" . phpversion() . "</strong></li>";
echo "<li>PDO MySQL: " . (extension_loaded('pdo_mysql') ? "<span class='success'>✅ Enabled</span>" : "<span class='error'>❌ Disabled</span>") . "</li>";
echo "<li>GD Library: " . (extension_loaded('gd') ? "<span class='success'>✅ Enabled</span>" : "<span class='error'>❌ Disabled</span>") . "</li>";
echo "<li>Session Support: " . (extension_loaded('session') ? "<span class='success'>✅ Enabled</span>" : "<span class='error'>❌ Disabled</span>") . "</li>";
echo "<li>Max Upload Size: " . ini_get('upload_max_filesize') . "</li>";
echo "<li>Max POST Size: " . ini_get('post_max_size') . "</li>";
echo "</ul></div>";

// 7. PERMISSIONS CHECK
echo "<div class='section'><h2>7. Directory Permissions</h2>";
$checkDirs = ['assets', 'uploads', 'includes'];
echo "<ul>";
foreach ($checkDirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (is_dir($path)) {
        $writable = is_writable($path);
        if ($writable) {
            echo "<li class='success'>✅ $dir/ is writable</li>";
        } else {
            echo "<li class='warning'>⚠️ $dir/ is not writable</li>";
            $warnings[] = "$dir directory not writable";
        }
    } else {
        echo "<li class='error'>❌ $dir/ does not exist</li>";
        $errors[] = "$dir directory missing";
    }
}
echo "</ul></div>";

// SUMMARY
echo "<div class='section'><h2>📊 Summary</h2>";
echo "<div class='stats'>";
echo "<div class='stat-card' style='border-left-color: #22c55e;'>";
echo "<h3 style='margin:0; color: #22c55e;'>" . count($success) . "</h3>";
echo "<p style='margin:5px 0 0 0;'>Successful Checks</p>";
echo "</div>";
echo "<div class='stat-card' style='border-left-color: #f59e0b;'>";
echo "<h3 style='margin:0; color: #f59e0b;'>" . count($warnings) . "</h3>";
echo "<p style='margin:5px 0 0 0;'>Warnings</p>";
echo "</div>";
echo "<div class='stat-card' style='border-left-color: #ef4444;'>";
echo "<h3 style='margin:0; color: #ef4444;'>" . count($errors) . "</h3>";
echo "<p style='margin:5px 0 0 0;'>Errors</p>";
echo "</div>";
echo "</div>";

if (count($errors) === 0 && count($warnings) === 0) {
    echo "<p class='success' style='font-size: 1.2em;'>🎉 System is healthy! No critical issues found.</p>";
} elseif (count($errors) === 0) {
    echo "<p class='warning' style='font-size: 1.2em;'>⚠️ System is functional but has some warnings.</p>";
} else {
    echo "<p class='error' style='font-size: 1.2em;'>❌ System has critical errors that need attention.</p>";
}

if (count($errors) > 0) {
    echo "<h3>Critical Errors:</h3><ul>";
    foreach ($errors as $error) {
        echo "<li class='error'>$error</li>";
    }
    echo "</ul>";
}

if (count($warnings) > 0) {
    echo "<h3>Warnings:</h3><ul>";
    foreach ($warnings as $warning) {
        echo "<li class='warning'>$warning</li>";
    }
    echo "</ul>";
}

echo "</div>";

echo "</body></html>";
?>