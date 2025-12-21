<?php
require_once __DIR__ . '/../includes/security_headers.php';

// Configure session for local network testing
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
// Enable secure cookie if HTTPS is active
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
ini_set('session.cookie_secure', $isHttps ? 1 : 0);

// Use the same session name as login
session_name('vehiscan_session');
session_start();

// Check if homeowner is logged in
if (!isset($_SESSION['homeowner_id']) || $_SESSION['role'] !== 'homeowner') {
    header("Location: ../auth/login.php");
    exit();
}

// Session timeout check (30 minutes)
$session_lifetime = 1800; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_lifetime)) {
    // Log session timeout for security audit
    error_log("Homeowner session timeout: " . $_SESSION['username'] . " (ID: " . $_SESSION['homeowner_id'] . ")");
    session_unset();
    session_destroy();
    header('Location: ../auth/login.php?timeout=1');
    exit();
}

// Regenerate session ID periodically for security (every 10 minutes)
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 600) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

$_SESSION['last_activity'] = time();

require_once __DIR__ . '/../db.php';

// Get homeowner data
$stmt = $pdo->prepare("
    SELECT h.* 
    FROM homeowners h
    WHERE h.id = ?
");
$stmt->execute([$_SESSION['homeowner_id']]);
$homeowner = $stmt->fetch();

if (!$homeowner) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}

// Get visitor passes for statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' AND NOW() BETWEEN valid_from AND valid_until THEN 1 ELSE 0 END) as active
    FROM visitor_passes 
    WHERE homeowner_id = ?
");
$stmt->execute([$_SESSION['homeowner_id']]);
$stats = $stmt->fetch();

$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title>Homeowner Portal — VehiScan</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <link rel="stylesheet" href="../assets/css/system.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/homeowner.css?v=<?php echo time(); ?>">
    <script src="../assets/js/libs/sweetalert2.all.min.js"></script>
    <script src="../assets/js/libs/chart.umd.min.js"></script>
    <script src="../assets/js/toast.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/session-timeout.js?v=<?php echo time(); ?>"></script>
</head>

<body class="m-0 p-0 overflow-hidden bg-gray-50 dark:bg-slate-900 transition-colors duration-300">

    <!-- User Dropdown (Fixed Position) -->
    <div id="user-dropdown" class="hidden fixed w-56 rounded-md border border-gray-300 bg-white shadow-lg"
        style="z-index: 9999;">
        <div class="p-1">
            <button id="signOutBtn"
                class="flex w-full items-center gap-2 rounded-sm px-3 py-2 text-sm text-red-600 hover:bg-red-50 font-medium transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                    </path>
                </svg>
                <span>Sign Out</span>
            </button>
        </div>
    </div>

    <div class="flex h-screen w-full">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Mobile Overlay -->
        <div id="mobile-overlay"></div>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden bg-gray-50 dark:bg-slate-950 transition-colors duration-300">
            <!-- Header -->
            <?php include __DIR__ . '/includes/header.php'; ?>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-6">
                <!-- Dashboard Page -->
                <div id="page-dashboard" class="page-content active">
                    <div class="space-y-6">
                        <!-- Welcome Card -->
                        <div class="relative rounded-2xl overflow-hidden shadow-2xl"
                            style="background: linear-gradient(135deg, #4f46e5 0%, #2563eb 50%, #7c3aed 100%);">
                            <!-- Decorative corner accent -->
                            <div
                                class="absolute top-0 right-0 w-64 h-64 bg-white/10 rounded-full -mr-32 -mt-32 blur-3xl">
                            </div>
                            <div
                                class="absolute bottom-0 left-0 w-48 h-48 bg-purple-500/20 rounded-full -ml-24 -mb-24 blur-2xl">
                            </div>

                            <div class="relative z-10 p-8">
                                <div class="flex items-center gap-6">
                                    <!-- Avatar with glow effect -->
                                    <div class="relative flex-shrink-0">
                                        <div class="absolute inset-0 bg-white/30 rounded-2xl blur-lg"></div>
                                        <div
                                            class="relative p-4 bg-white rounded-2xl shadow-2xl transform transition-transform duration-300 hover:scale-110">
                                            <svg class="h-10 w-10 text-indigo-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                                </path>
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- Text content -->
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h2 class="text-3xl lg:text-4xl font-black text-white tracking-tight"
                                                style="text-shadow: 0 4px 6px rgba(0,0,0,0.3), 0 2px 4px rgba(0,0,0,0.2);">
                                                Welcome back, <?= htmlspecialchars($homeowner['name']) ?>!
                                            </h2>
                                            <span
                                                class="hidden sm:inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-white/20 text-white backdrop-blur-sm border border-white/30">
                                                <span
                                                    class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                                                Online
                                            </span>
                                        </div>
                                        <p class="text-white/95 font-medium text-base lg:text-lg"
                                            style="text-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                            Manage your visitor passes and profile information
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="stat-card group">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-2">
                                            Total Passes</h3>
                                        <p class="text-4xl font-extrabold text-gray-900"><?= $stats['total'] ?? 0 ?></p>
                                        <p class="text-xs text-gray-500 mt-2">All time requests</p>
                                    </div>
                                    <div
                                        class="p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl group-hover:from-blue-100 group-hover:to-blue-200 transition-all duration-300">
                                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z">
                                            </path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="stat-card group">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-2">
                                            Active Passes</h3>
                                        <p class="text-4xl font-extrabold text-green-600"><?= $stats['active'] ?? 0 ?>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-2">Currently valid</p>
                                    </div>
                                    <div
                                        class="p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl group-hover:from-green-100 group-hover:to-green-200 transition-all duration-300">
                                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="stat-card group">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-2">
                                            Pending Approval</h3>
                                        <p class="text-4xl font-extrabold text-amber-600"><?= $stats['pending'] ?? 0 ?>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-2">Awaiting review</p>
                                    </div>
                                    <div
                                        class="p-4 bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl group-hover:from-amber-100 group-hover:to-amber-200 transition-all duration-300">
                                        <svg class="h-8 w-8 text-amber-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div
                            class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm dark:bg-slate-800 dark:border-slate-700 transition-colors duration-300">
                            <div class="flex items-center gap-2 mb-5">
                                <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Quick Actions</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <button onclick="showAddVisitorPassModal()"
                                    class="group relative flex items-center gap-4 p-5 bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 rounded-xl transition-all duration-300 text-left shadow-sm hover:shadow-md overflow-hidden">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700">
                                    </div>
                                    <div
                                        class="relative p-3 bg-blue-600 rounded-xl shadow-lg group-hover:scale-110 transition-transform duration-300">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </div>
                                    <div class="relative">
                                        <p class="font-bold text-gray-900 text-base mb-1">Create Visitor Pass</p>
                                        <p class="text-sm text-gray-600">Request a new visitor pass</p>
                                    </div>
                                </button>

                                <button onclick="loadPage('passes')"
                                    class="group relative flex items-center gap-4 p-5 bg-gradient-to-br from-gray-50 to-gray-100 hover:from-gray-100 hover:to-gray-200 rounded-xl transition-all duration-300 text-left shadow-sm hover:shadow-md overflow-hidden">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-700">
                                    </div>
                                    <div
                                        class="relative p-3 bg-gray-700 rounded-xl shadow-lg group-hover:scale-110 transition-transform duration-300">
                                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                            </path>
                                        </svg>
                                    </div>
                                    <div class="relative">
                                        <p class="font-bold text-gray-900 text-base mb-1">View All Passes</p>
                                        <p class="text-sm text-gray-600">Manage your visitor passes</p>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visitor Passes Page -->
                <div id="page-passes" class="page-content">
                    <div class="space-y-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">My Visitor Passes</h2>
                                <p class="text-sm text-gray-600 mt-1">Request and manage visitor passes for your guests
                                </p>
                            </div>
                            <button onclick="showAddVisitorPassModal()"
                                class="btn-primary flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 active:bg-blue-800 shadow-lg hover:shadow-xl transition-all duration-200 font-semibold">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Add Visitor Pass</span>
                            </button>
                        </div>

                        <!-- Passes List Container -->
                        <div id="passes-list" class="space-y-3">
                            <div class="loading">Loading visitor passes...</div>
                        </div>
                    </div>
                </div>

                <!-- My Vehicles Page -->
                <div id="page-vehicles" class="page-content">
                    <div class="space-y-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">My Vehicles</h2>
                                <p class="text-sm text-gray-600 mt-1">Manage your registered vehicles</p>
                            </div>
                            <button id="addVehicleBtn"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Add Vehicle
                            </button>
                        </div>

                        <div id="vehiclesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="loading col-span-full text-center py-8">Loading vehicles...</div>
                        </div>
                    </div>
                </div>

                <!-- Vehicle Activity Page -->
                <div id="page-activity" class="page-content">
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">Vehicle Activity</h2>
                            <p class="text-sm text-gray-600 mt-1">Track your vehicle entry and exit history</p>
                        </div>

                        <!-- Time Period Selector -->
                        <div class="gap-2 bg-white p-1 rounded-lg border border-gray-200 inline-flex">
                            <button class="period-btn active px-4 py-2 rounded-md text-sm font-medium"
                                data-period="day">Today</button>
                            <button class="period-btn px-4 py-2 rounded-md text-sm font-medium" data-period="week">This
                                Week</button>
                            <button class="period-btn px-4 py-2 rounded-md text-sm font-medium" data-period="month">This
                                Month</button>
                        </div>

                        <!-- Summary Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-white rounded-lg p-6 border border-gray-200">
                                <div class="flex items-center gap-3">
                                    <div class="p-3 bg-green-100 rounded-lg">
                                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Total Entries</p>
                                        <p class="text-2xl font-bold text-gray-900" id="totalEntries">0</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg p-6 border border-gray-200">
                                <div class="flex items-center gap-3">
                                    <div class="p-3 bg-red-100 rounded-lg">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Total Exits</p>
                                        <p class="text-2xl font-bold text-gray-900" id="totalExits">0</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg p-6 border border-gray-200">
                                <div class="flex items-center gap-3">
                                    <div class="p-3 bg-blue-100 rounded-lg">
                                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                            </path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Total Activity</p>
                                        <p class="text-2xl font-bold text-gray-900" id="totalActivity">0</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Activity Chart -->
                        <div class="bg-white rounded-lg p-6 border border-gray-200">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Activity Timeline</h3>
                            <canvas id="activityChart" height="80"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Profile Page -->
                <div id="page-profile" class="page-content">
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">My Profile</h2>
                            <p class="text-sm text-gray-600 mt-1">Your personal and vehicle information</p>
                        </div>

                        <!-- Images Section -->
                        <div class="bg-white rounded-lg p-6 border border-gray-200">
                            <div class="flex items-center gap-2 mb-5">
                                <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                <h3 class="text-lg font-bold text-gray-900">Registered Images</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Owner Image -->
                                <div class="space-y-3">
                                    <span class="block text-sm font-semibold text-gray-700">Owner Photo</span>
                                    <div class="relative group">
                                        <?php if (!empty($homeowner['owner_img'])): ?>
                                            <?php
                                            $ownerImgPath = '../uploads/' . $homeowner['owner_img'];
                                            if (file_exists($ownerImgPath)):
                                                ?>
                                                <div
                                                    class="aspect-square rounded-xl overflow-hidden border-2 border-gray-200 bg-gray-50 shadow-md hover:shadow-xl transition-shadow duration-300">
                                                    <img src="<?= htmlspecialchars($ownerImgPath) ?>" alt="Owner Photo"
                                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 cursor-pointer"
                                                        onclick="viewImage('<?= htmlspecialchars($ownerImgPath) ?>', 'Owner Photo')">
                                                </div>
                                                <div
                                                    class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                                    <button
                                                        onclick="viewImage('<?= htmlspecialchars($ownerImgPath) ?>', 'Owner Photo')"
                                                        class="p-2 bg-white/90 rounded-lg shadow-lg hover:bg-white transition-colors">
                                                        <svg class="h-5 w-5 text-gray-700" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7">
                                                            </path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <div
                                                    class="aspect-square rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
                                                    <div class="text-center p-4">
                                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                            </path>
                                                        </svg>
                                                        <p class="mt-2 text-sm text-gray-500">Image file not found</p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div
                                                class="aspect-square rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
                                                <div class="text-center p-4">
                                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                                        </path>
                                                    </svg>
                                                    <p class="mt-2 text-sm text-gray-500">No owner photo</p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Vehicle Image -->
                                <div class="space-y-3">
                                    <span class="block text-sm font-semibold text-gray-700">Vehicle Photo</span>
                                    <div class="relative group">
                                        <?php if (!empty($homeowner['car_img'])): ?>
                                            <?php
                                            $carImgPath = '../uploads/' . $homeowner['car_img'];
                                            if (file_exists($carImgPath)):
                                                ?>
                                                <div
                                                    class="aspect-square rounded-xl overflow-hidden border-2 border-gray-200 bg-gray-50 shadow-md hover:shadow-xl transition-shadow duration-300">
                                                    <img src="<?= htmlspecialchars($carImgPath) ?>" alt="Vehicle Photo"
                                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 cursor-pointer"
                                                        onclick="viewImage('<?= htmlspecialchars($carImgPath) ?>', 'Vehicle Photo')">
                                                </div>
                                                <div
                                                    class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                                    <button
                                                        onclick="viewImage('<?= htmlspecialchars($carImgPath) ?>', 'Vehicle Photo')"
                                                        class="p-2 bg-white/90 rounded-lg shadow-lg hover:bg-white transition-colors">
                                                        <svg class="h-5 w-5 text-gray-700" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7">
                                                            </path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <div
                                                    class="aspect-square rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
                                                    <div class="text-center p-4">
                                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                            </path>
                                                        </svg>
                                                        <p class="mt-2 text-sm text-gray-500">Image file not found</p>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div
                                                class="aspect-square rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center">
                                                <div class="text-center p-4">
                                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2">
                                                        </path>
                                                    </svg>
                                                    <p class="mt-2 text-sm text-gray-500">No vehicle photo</p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <div class="bg-white rounded-lg p-6 border border-gray-200">
                            <div class="flex items-center gap-2 mb-5">
                                <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <h3 class="text-lg font-bold text-gray-900">Personal Information</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <span class="block text-sm font-medium text-gray-500 mb-1">Full Name</span>
                                    <p class="text-base text-gray-900"><?= htmlspecialchars($homeowner['name']) ?></p>
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-500 mb-1">Address</span>
                                    <p class="text-base text-gray-900"><?= htmlspecialchars($homeowner['address']) ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-500 mb-1">Contact Number</span>
                                    <p class="text-base text-gray-900">
                                        <?= htmlspecialchars($homeowner['contact'] ?? 'N/A') ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-500 mb-1">Plate Number</span>
                                    <p
                                        class="text-base text-gray-900 font-mono bg-gray-50 px-3 py-2 rounded inline-block">
                                        <?= htmlspecialchars($homeowner['plate_number']) ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-500 mb-1">Vehicle Type</span>
                                    <p class="text-base text-gray-900">
                                        <?= htmlspecialchars($homeowner['vehicle_type']) ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-500 mb-1">Vehicle Color</span>
                                    <p class="text-base text-gray-900"><?= htmlspecialchars($homeowner['color']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/homeowner/homeowner_portal.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/keyboard-shortcuts.js?v=<?php echo time(); ?>"></script>
    <script src="../assets/js/mobile-gestures.js?v=<?php echo time(); ?>"></script>
    <script src="js/homeowner-dark-mode.js?v=<?php echo time(); ?>"></script>
    <script src="js/vehicle-management.js?v=<?php echo time(); ?>"></script>
</body>

</html>