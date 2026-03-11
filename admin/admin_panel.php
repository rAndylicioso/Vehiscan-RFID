<?php
require_once __DIR__ . '/../includes/security_headers.php';
require_once __DIR__ . '/../includes/session_admin_unified.php';

// Check if user is Super Admin or regular Admin
$isSuperAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin');

// Verify user has admin or super_admin role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
  header('Location: ../auth/login.php');
  exit();
}

// CSRF token is set by session_admin_unified.php
$csrf = $_SESSION['csrf_token'];

require_once __DIR__ . '/../db.php';

$page = 'dashboard';
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin Panel — VehiScan</title>

  <!-- CSS Files - Load in Order -->
  <link rel="stylesheet" href="../assets/css/tailwind.css">
  <link rel="stylesheet" href="../assets/css/system.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/system.css'); ?>">

  <!-- Skeleton loaders are now centralized in system.css -->

  <link rel="stylesheet" href="../assets/css/admin/admin.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/admin/admin.css'); ?>">
  <link rel="stylesheet" href="../assets/css/tailadmin-components.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/tailadmin-components.css'); ?>">
  <link rel="stylesheet" href="../assets/css/button-system.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/button-system.css'); ?>">
  <link rel="stylesheet" href="css/visitor-passes.css?v=<?php echo @filemtime(__DIR__ . '/css/visitor-passes.css') ?: time(); ?>">

  <!-- External Libraries - CDN (Must load before custom scripts) -->
  <script src="../assets/js/libs/jquery-3.7.1.min.js"></script>
  <script src="../assets/js/libs/sweetalert2.all.min.js"></script>
  <script src="../assets/js/libs/chart.umd.min.js"></script>
  <script defer src="../assets/js/libs/alpine.min.js"></script>

  <!-- Core Utilities - Load before main scripts -->
  <script src="../assets/js/toast.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/toast.js'); ?>"></script>
  <script src="../assets/js/session-timeout.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/session-timeout.js'); ?>"></script>

  <style>
    /* Session timeout warning modal styling */
    .swal2-popup {
      font-family: system-ui, -apple-system, sans-serif;
    }

    #sessionCountdown {
      font-size: 2.5rem !important;
      font-weight: bold !important;
      color: #f59e0b !important;
      margin: 20px 0 !important;
      font-variant-numeric: tabular-nums;
    }
  </style>
</head>

<body class="m-0 p-0 overflow-hidden bg-gray-100 dark:bg-slate-950 transition-colors duration-300">

  <!-- User Dropdown (Fixed Position) -->
  <div id="user-dropdown" class="hidden fixed w-56 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 shadow-lg"
    style="z-index: 9999;">
    <div class="p-1">
      <button id="signOutBtn"
        class="flex w-full items-center gap-2 rounded-sm px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 font-medium transition-colors">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
        </svg>
        <span>Sign Out</span>
      </button>
    </div>
  </div>

  <!-- Mobile Menu Overlay -->
  <div id="mobile-overlay"></div>

  <div class="flex h-screen w-full">
    <!-- Shadcn Sidebar -->
    <!-- Shadcn Sidebar -->
    <aside id="sidebar"
      class="sidebar-transition sidebar-open relative flex flex-col border-r bg-white dark:bg-slate-900 dark:border-slate-700 text-gray-900 dark:text-gray-100 overflow-x-hidden"
      role="navigation" aria-label="Main navigation">
      <!-- Brand Header -->
      <div id="brand-header" class="flex h-14 items-center border-b border-gray-100 dark:border-slate-700 px-4">
        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center">
          <img src="../assets/images/vehiscan-logo.png" alt="VehiScan Logo" class="h-full w-full object-contain"
            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
          <span style="display:none;" class="text-2xl text-gray-700 dark:text-gray-300 font-bold">V</span>
        </div>
        <span id="brand-name" class="sidebar-text ml-3 text-left font-bold text-lg">VehiScan</span>
      </div>

      <!-- Navigation Menu -->
      <div class="flex-1 overflow-y-auto hide-scrollbar py-2">
        <div class="mb-4 px-3">
          <div id="main-label" class="sidebar-text mb-2 px-2 text-xs font-semibold text-gray-500 opacity-70">
            MAIN MENU
          </div>
          <div class="space-y-1">
            <a href="#"
              class="menu-item flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-all text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800 active"
              data-page="dashboard">
              <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                </path>
              </svg>
              <span>Dashboard</span>
            </a>

            <a href="#"
              class="menu-item flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-all text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800"
              data-page="manage">
              <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                </path>
              </svg>
              <span class="sidebar-text">Manage Records</span>
            </a>

            <a href="#"
              class="menu-item flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-all text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800"
              data-page="logs">
              <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
              </svg>
              <span class="sidebar-text">Access Logs</span>
            </a>

            <a href="#"
              class="menu-item flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-all text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800"
              data-page="audit">
              <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                </path>
              </svg>
              <span class="sidebar-text">Audit Logs</span>
            </a>

            <a href="#"
              class="menu-item flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-all text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800"
              data-page="visitors">
              <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z">
                </path>
              </svg>
              <span class="sidebar-text">Visitor Passes</span>
              <span class="ta-sidebar-badge blue sidebar-text ml-auto" id="pendingPassesBadge" style="display:none;">0</span>
            </a>

            <a href="#"
              class="menu-item flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-all text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800"
              data-page="employees">
              <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                </path>
              </svg>
              <span class="sidebar-text">Employee Management</span>
            </a>

            <a href="#"
              class="menu-item flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-all text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800"
              data-page="profile_requests">
              <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
              <span class="sidebar-text">Profile Requests</span>
            </a>

            <?php if ($isSuperAdmin): ?>
              <a href="#"
                class="menu-item flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-all text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800"
                data-page="approvals">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="sidebar-text">Account Approvals</span>
                <span class="ta-sidebar-badge amber sidebar-text ml-auto" id="pendingApprovalsBadge" style="display:none;">0</span>
              </a>
            <?php endif; ?>

            <a href="#"
              class="menu-item flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-all text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800"
              data-page="rfid">
              <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z">
                </path>
              </svg>
              <span class="sidebar-text">RFID Management</span>
            </a>

            <a href="#"
              class="menu-item flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-all text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800"
              data-page="simulator">
              <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                </path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              <span class="sidebar-text">RFID Simulator</span>
            </a>
          </div>
        </div>

        <!-- System Section -->
        <div class="px-3">
          <div id="system-label" class="sidebar-text mb-2 px-2 text-xs font-semibold text-gray-500 dark:text-gray-400 opacity-70">
            SYSTEM
          </div>
          <div class="space-y-1">
            <button id="backupBtn"
              class="menu-item flex w-full items-center gap-3 rounded-md px-3 py-2.5 text-sm transition-all text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-800">
              <svg class="h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4">
                </path>
              </svg>
              <span class="sidebar-text">Database Backup</span>
            </button>
          </div>
        </div>
      </div>

      <!-- User Section -->
      <div class="mt-auto border-t border-gray-100 dark:border-slate-700 p-4">
        <div class="relative">
          <button id="user-trigger"
            class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-sm hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors">
            <div
              class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 dark:bg-slate-700 flex-shrink-0 border border-gray-200 dark:border-slate-600">
              <svg class="h-4 w-4 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <div class="sidebar-text flex flex-col items-start flex-1">
              <span
                class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin User'); ?></span>
              <span
                class="text-xs text-gray-500 dark:text-gray-400 opacity-70"><?php echo htmlspecialchars($_SESSION['role'] ?? 'admin'); ?></span>
            </div>
            <svg id="user-chevron" class="sidebar-text ml-auto h-4 w-4 text-gray-500 transition-transform rotate-180"
              fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col overflow-hidden bg-transparent">
      <!-- Header -->
      <header
        class="flex h-14 items-center gap-4 border-b border-gray-300 dark:border-slate-700 px-6 bg-white dark:bg-slate-900">
        <!-- Mobile Menu Button -->
        <button id="mobile-menu-btn"
          class="flex h-9 w-9 items-center justify-center rounded-md hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors md:hidden"
          aria-label="Toggle mobile menu">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>

        <button id="sidebar-toggle"
          class="hidden md:flex h-9 w-9 items-center justify-center rounded-md hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors"
          aria-label="Toggle sidebar">
          <svg id="hamburger-icon" class="h-5 w-5 transition-transform duration-300" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>
        <h1 id="page-title" class="text-lg font-semibold text-gray-900 dark:text-white">Dashboard</h1>
        <div class="ml-auto flex items-center gap-4">
          <!-- Notification Bell -->
          <div class="ta-notification-bell relative" id="notificationBellWrapper">
            <button id="notificationBellBtn" class="relative flex h-9 w-9 items-center justify-center rounded-md hover:bg-gray-100 dark:hover:bg-slate-700 transition-colors" aria-label="Notifications">
              <svg class="h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
              </svg>
              <span class="ta-notification-dot hidden" id="notifDot"></span>
            </button>
            <div class="ta-notification-panel hidden" id="notificationPanel">
              <div class="ta-notification-header">
                <span class="font-semibold text-sm">Notifications</span>
                <button class="text-xs text-blue-600 dark:text-blue-400 hover:underline" id="markAllReadBtn">Mark all read</button>
              </div>
              <div class="ta-notification-list" id="notificationList">
                <div class="text-center py-6 text-gray-400 dark:text-gray-500 text-sm">No new notifications</div>
              </div>
              <div class="ta-notification-footer">
                <a href="#" class="text-xs text-blue-600 dark:text-blue-400 hover:underline" onclick="loadPage('logs'); document.getElementById('notificationPanel').classList.add('hidden'); return false;">View all activity</a>
              </div>
            </div>
          </div>

          <!-- Dark Mode Toggle -->
          <button id="darkModeToggle" class="theme-toggle-btn" aria-label="Toggle Dark Mode">
            <svg class="theme-icon sun-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
              </path>
            </svg>
            <svg class="theme-icon moon-icon h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
            </svg>
          </button>
          <span id="liveTime" class="text-gray-600 dark:text-gray-300 text-sm font-medium"></span>
        </div>
      </header>

      <!-- Content Area -->
      <div class="flex-1 overflow-y-auto p-6" id="content-area" role="region" aria-live="polite"
        aria-label="Main content"
        style="transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); opacity: 1;">
        <div class="text-center py-8 text-gray-500 loading">Loading...</div>
      </div>
    </main>
  </div>

  <!-- Modal - Completely independent overlay -->
  <div id="editModal" class="hidden fixed inset-0 z-[9999]" aria-hidden="true" role="dialog" aria-modal="true"
    aria-labelledby="modal-title">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeModal()"></div>

    <!-- Modal Content -->
    <div class="relative h-full w-full flex items-center justify-center p-6">
      <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden"
        role="document">
        <button type="button"
          class="absolute top-4 right-4 w-9 h-9 flex items-center justify-center text-2xl text-gray-400 hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-700 rounded-full cursor-pointer transition-all z-10"
          onclick="closeModal()" aria-label="Close modal">&times;</button>
        <div class="overflow-y-auto max-h-[90vh] p-8" id="modal-body"></div>
      </div>
    </div>
  </div>

  <!-- Global Variables -->
  <script>window.__ADMIN_CSRF__ = <?php echo json_encode($csrf); ?>;</script>

  <script>
  // Notification bell toggle
  document.addEventListener('DOMContentLoaded', function() {
    const bellBtn = document.getElementById('notificationBellBtn');
    const panel = document.getElementById('notificationPanel');
    if (bellBtn && panel) {
      bellBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        panel.classList.toggle('hidden');
      });
      document.addEventListener('click', function(e) {
        if (!panel.contains(e.target) && !bellBtn.contains(e.target)) {
          panel.classList.add('hidden');
        }
      });
    }

    // Sidebar badge polling for pending approvals
    function updateSidebarBadges() {
      fetch('api/check_pending_approvals.php')
        .then(r => r.json())
        .then(data => {
          const approvalBadge = document.getElementById('pendingApprovalsBadge');
          if (approvalBadge && typeof data.pending_count !== 'undefined') {
            if (data.pending_count > 0) {
              approvalBadge.textContent = data.pending_count;
              approvalBadge.style.display = '';
            } else {
              approvalBadge.style.display = 'none';
            }
          }
        }).catch(() => {});
    }
    // Initial check + poll every 30s
    updateSidebarBadges();
    setInterval(updateSidebarBadges, 30000);
  });
  </script>

  <!-- Main Application Scripts - Load in order: core -> handlers -> features -->
  <script src="../assets/js/admin/realtime-updates.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/admin/realtime-updates.js'); ?>"></script>
  <script src="../assets/js/admin/admin_panel.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/admin/admin_panel.js'); ?>"></script>
  <script src="../assets/js/admin/modal-handler.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/admin/modal-handler.js'); ?>"></script>
  <script src="js/qr-modal.js?v=<?php echo @filemtime(__DIR__ . '/js/qr-modal.js') ?: time(); ?>"></script>
  <script src="../assets/js/keyboard-shortcuts.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/keyboard-shortcuts.js'); ?>"></script>
  <script src="../assets/js/mobile-gestures.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/mobile-gestures.js'); ?>"></script>
  <script src="js/admin-dark-mode.js?v=<?php echo @filemtime(__DIR__ . '/js/admin-dark-mode.js') ?: time(); ?>"></script>

  <!-- TailAdmin Action Dropdown Handler -->
  <script>
  (function(){
    function closeDropdown(dd) {
      dd.classList.remove('open');
      const menu = dd.querySelector('.ta-action-menu');
      if (menu) menu.removeAttribute('style');
    }
    function closeAllDropdowns(except) {
      document.querySelectorAll('.ta-action-dropdown.open').forEach(function(d) {
        if (d !== except) closeDropdown(d);
      });
    }
    function positionDrop(dd) {
      const menu = dd.querySelector('.ta-action-menu');
      const trigger = dd.querySelector('.ta-action-btn');
      if (!menu || !trigger) return;
      // Measure actual menu width by temporarily showing it off-screen
      menu.style.cssText = 'position:fixed;visibility:hidden;display:block;right:auto;width:auto;left:-9999px;top:0;';
      var menuWidth = Math.max(menu.offsetWidth, 160);
      var rect = trigger.getBoundingClientRect();
      var spaceBelow = window.innerHeight - rect.bottom;
      var dropUp = spaceBelow < 180 && rect.top > 180;
      var leftPos = rect.right - menuWidth;
      if (leftPos < 4) leftPos = 4;
      if (leftPos + menuWidth > window.innerWidth - 4) leftPos = window.innerWidth - menuWidth - 4;
      menu.style.cssText = [
        'position:fixed',
        'z-index:9999',
        'width:' + menuWidth + 'px',
        'right:auto',
        'margin:0',
        'left:' + leftPos + 'px',
        dropUp
          ? 'top:auto;bottom:' + (window.innerHeight - rect.top + 4) + 'px'
          : 'top:' + (rect.bottom + 4) + 'px;bottom:auto'
      ].join(';');
      dd.classList.toggle('drop-up', dropUp);
    }
    document.addEventListener('click', function(e) {
      var btn = e.target.closest('.ta-action-btn');
      if (btn) {
        e.stopPropagation();
        var dd = btn.closest('.ta-action-dropdown');
        var wasOpen = dd.classList.contains('open');
        closeAllDropdowns(dd);
        if (wasOpen) { closeDropdown(dd); } else { dd.classList.add('open'); positionDrop(dd); }
        return;
      }
      var item = e.target.closest('.ta-action-menu-item');
      if (item) {
        closeDropdown(item.closest('.ta-action-dropdown'));
        return;
      }
      closeAllDropdowns(null);
    });
    // Reposition on scroll/resize so fixed menu tracks the trigger
    ['scroll', 'resize'].forEach(function(ev) {
      window.addEventListener(ev, function() {
        var open = document.querySelector('.ta-action-dropdown.open');
        if (open) positionDrop(open);
      }, { passive: true });
    });
    // Close dropdowns when content-area scrolls (fixed menu won't track)
    var contentArea = document.getElementById('content-area');
    if (contentArea) {
      contentArea.addEventListener('scroll', function() {
        closeAllDropdowns(null);
      }, { passive: true });
    }
  })();
  </script>
</body>
</html>