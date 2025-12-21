<?php
require_once __DIR__ . '/../../includes/security_headers.php';
require_once __DIR__ . '/../../includes/session_guard.php';

// Ensure proper session state
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guard') {
  header("Location: ../../auth/login.php");
  exit();
}

// Refresh session data
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

require_once __DIR__ . '/../../db.php';
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Guard Panel — VehiScan</title>

  <!-- CSS Files - Load in Order -->
  <link rel="stylesheet" href="../../assets/css/tailwind.css">
  <link rel="stylesheet" href="../../assets/css/system.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../css/guard_side.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../css/guard-dark-mode.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../css/guard-components.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="../css/guard-qr-modal.css?v=<?php echo time(); ?>">

  <style>
    /* Skeleton Loader */
    .skeleton {
      background: linear-gradient(90deg, #2a2a2a 25%, #3a3a3a 50%, #2a2a2a 75%);
      background-size: 200% 100%;
      animation: skeleton-loading 1.5s infinite;
    }

    @keyframes skeleton-loading {
      0% {
        background-position: 200% 0;
      }

      100% {
        background-position: -200% 0;
      }
    }

    .skeleton-card {
      height: 200px;
      border-radius: 8px;
      margin-bottom: 1rem;
    }
  </style>

  <!-- External Libraries - Must load before custom scripts -->
  <script src="../../assets/js/libs/sweetalert2.all.min.js"></script>

  <!-- Core Utilities -->
  <script src="../../assets/js/toast.js?v=<?php echo time(); ?>"></script>
  <!-- Session timeout disabled for guard - 24/7 operation -->
</head>

<body class="m-0 p-0 overflow-hidden bg-guard-bg">

  <!-- User Dropdown (Fixed Position) -->
  <div id="user-dropdown" class="fixed rounded-md border border-gray-300 bg-white shadow-lg"
    style="z-index: var(--z-swal); display: none;">
    <div class="p-1">
      <button id="signOutBtn"
        class="flex w-full items-center gap-2 rounded-sm px-3 py-2 text-sm text-red-600 hover:bg-red-50 font-medium transition-colors">
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
    <!-- Fixed Sidebar -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col" style="background-color: #F5F5F5;">
      <?php include __DIR__ . '/../includes/header.php'; ?>

      <!-- Content Area -->
      <div class="flex-1 overflow-auto p-6" style="background-color: #F5F5F5;">
        <!-- Access Logs Page -->
        <div id="page-logs" class="page-content active">
          <div class="space-y-6">
            <!-- Filters Bar -->
            <div class="bg-white rounded-lg p-4 border border-gray-200"
              style="box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);">
              <div class="flex flex-wrap gap-3 items-center">
                <div class="flex-1 min-w-[220px]">
                  <div class="relative">
                    <input type="text" id="logsSearch" placeholder="Search logs by name, plate, or action..."
                      class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white transition-all">
                    <svg class="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                      viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                  </div>
                </div>

                <!-- Toggle Group -->
                <div class="filter-toggle-group" data-type="multiple">
                  <button id="filterToday" class="filter-toggle-item" data-variant="today" data-value="today"
                    aria-label="Filter today's logs">
                    <span>📅</span>
                    <span>Today</span>
                  </button>
                  <button id="filterIn" class="filter-toggle-item" data-variant="in" data-value="in"
                    aria-label="Filter IN logs">
                    <span>🟢</span>
                    <span>IN Only</span>
                  </button>
                  <button id="filterOut" class="filter-toggle-item" data-variant="out" data-value="out"
                    aria-label="Filter OUT logs">
                    <span>🔴</span>
                    <span>OUT Only</span>
                  </button>
                  <button id="filterVisitors" class="filter-toggle-item" data-variant="visitors" data-value="visitors"
                    aria-label="Filter visitor logs">
                    <span>🎫</span>
                    <span>Visitors</span>
                  </button>
                </div>

                <button id="clearLogsFilter"
                  class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                  </svg>
                  <span>Clear</span>
                </button>
                <!-- REMOVED: Guards can no longer delete logs - security restriction -->
                <button id="refreshLogs"
                  class="ml-auto inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition-colors">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                    </path>
                  </svg>
                  <span>Refresh</span>
                </button>
              </div>
            </div>

            <!-- Logs Container - Server renders HTML here (matching admin panel) -->
            <div id="logsContainerWrapper">
              <div class="logs-table-container">
                <div class="text-center py-12">
                  <div
                    class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-gray-300 border-t-gray-600">
                  </div>
                  <p class="mt-2 text-gray-500">Loading logs...</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Homeowners Page -->
        <div id="page-homeowners" class="page-content hidden">
          <div class="space-y-6">
            <!-- Search Bar -->
            <div class="bg-white rounded-lg border border-gray-200 p-4"
              style="box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);">
              <div class="flex gap-3">
                <input type="text" id="homeownerSearch" placeholder="Search by name, plate, or address..."
                  class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button id="clearSearch"
                  class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                  Clear
                </button>
                <button id="reloadHomeowners"
                  class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition-colors">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                    </path>
                  </svg>
                  Refresh
                </button>
              </div>
            </div>

            <!-- Homeowner Details Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 bg-guard-bg">
              <!-- Vehicle Card -->
              <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 font-semibold"
                  style="background: var(--card, #fff); color: var(--guard-accent-dark, #222);">
                  🚗 Vehicle Information
                </div>
                <div class="p-4">
                  <div class="aspect-video bg-white rounded-lg overflow-hidden mb-4 cursor-zoom-in"
                    onclick="openImageZoom(document.getElementById('carImage').src)">
                    <img id="carImage" src="" alt="Vehicle" class="w-full h-full object-contain">
                  </div>
                  <div class="space-y-2 text-sm">
                    <p id="vehicleType" class="font-medium text-gray-700">Vehicle Type: -</p>
                    <p id="vehicleColor" class="font-medium text-gray-700">Color: -</p>
                    <p id="plateNumber" class="font-medium text-gray-700">Plate Number: -</p>
                  </div>
                </div>
              </div>
              <!-- Owner Card -->
              <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-4 py-3 fonts-semibold"
                  style="background: var(--card, #fff); color: var(--guard-accent-dark, #222);">
                  👤 Owner Information
                </div>
                <div class="p-4">
                  <div class="aspect-video bg-white rounded-lg overflow-hidden mb-4 cursor-zoom-in"
                    onclick="openImageZoom(document.getElementById('ownerImage').src)">
                    <img id="ownerImage" src="" alt="Owner" class="w-full h-full object-contain">
                  </div>
                  <div class="space-y-2 text-sm">
                    <p id="ownerName" class="font-medium text-gray-700">Name: -</p>
                    <p id="ownerAddress" class="font-medium text-gray-700">Address: -</p>
                    <p id="ownerContact" class="font-medium text-gray-700">Contact: -</p>
                  </div>
                </div>
                <div class="bg-white px-4 py-3 border-t border-gray-200 flex justify-between items-center">
                  <button id="prevOwner"
                    class="px-4 py-2 bg-gray-600 text-white rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors">
                    ◄ Prev
                  </button>
                  <span id="ownerCounter" class="text-sm font-medium text-gray-600">-/-</span>
                  <button id="nextOwner"
                    class="px-4 py-2 bg-gray-600 text-white rounded-lg text-sm font-medium hover:bg-gray-700 transition-colors">
                    Next ►
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Camera Page -->
        <div id="page-camera" class="page-content hidden">
          <div class="space-y-6 bg-guard-bg">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
              <div
                class="bg-gradient-to-r from-purple-500 to-purple-600 text-white px-4 py-3 flex justify-between items-center">
                <div class="flex items-center gap-2 font-semibold">
                  <span>📹</span>
                  <span>Live Camera Feed</span>
                </div>
                <div class="flex items-center gap-3">
                  <select id="cameraSelect"
                    class="hidden text-xs bg-gray-700 border border-gray-600 rounded px-2 py-1 text-white">
                    <option value="">Select Camera</option>
                  </select>
                  <button id="fullscreenCamera" class="hidden p-2 hover:bg-gray-700 rounded transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4">
                      </path>
                    </svg>
                  </button>
                  <div class="flex items-center gap-2" id="cameraStatus">
                    <span class="w-2 h-2 rounded-full bg-gray-400 animate-pulse"></span>
                    <span class="text-xs font-semibold uppercase">Offline</span>
                  </div>
                </div>
              </div>
              <div class="p-6 bg-white">
                <div class="max-w-4xl mx-auto">
                  <div class="aspect-video bg-black rounded-xl overflow-hidden relative shadow-lg">
                    <video id="liveCamera" autoplay playsinline muted class="w-full h-full object-cover"></video>
                    <canvas id="cameraCanvas" class="hidden"></canvas>

                    <div
                      class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-gray-700 to-gray-900 flex justify-center items-center"
                      id="cameraOverlay">
                      <div class="text-center text-gray-400">
                        <i class="fas fa-video-slash text-6xl mb-4 opacity-50"></i>
                        <p class="text-lg font-semibold uppercase tracking-wide">Camera is off</p>
                        <p class="text-sm mt-2 opacity-75">Click Start Camera to begin</p>
                      </div>
                    </div>

                    <div id="recordingIndicator"
                      class="absolute top-4 right-4 flex items-center gap-2 bg-red-600 text-white px-3 py-2 rounded-full text-sm font-bold shadow-lg"
                      style="display: none;">
                      <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                      <span>REC</span>
                    </div>

                    <div id="cameraTimestamp"
                      class="hidden absolute bottom-4 left-4 bg-black bg-opacity-70 text-white px-3 py-2 rounded text-sm font-mono">
                      --:--:--
                    </div>

                    <div id="snapshotFlash" class="hidden absolute inset-0 bg-white pointer-events-none"></div>
                  </div>

                  <div class="mt-6 flex flex-col gap-4">
                    <div class="flex justify-center gap-3">
                      <button id="toggleCamera"
                        class="px-8 py-3 bg-gradient-to-r from-gray-600 to-gray-700 text-white rounded-lg font-bold text-sm uppercase tracking-wide hover:from-gray-700 hover:to-gray-800 shadow-md hover:shadow-lg transition-all flex items-center gap-2">
                        <i id="powerIcon" class="fas fa-power-off"></i>
                        <span id="cameraBtnText">Start Camera</span>
                      </button>
                    </div>

                    <div id="secondaryControls" class="flex justify-center gap-3" style="display: none;">
                      <button id="snapshotBtn"
                        class="px-6 py-2 bg-green-500 text-white rounded-lg text-sm font-semibold hover:bg-green-600 transition-all flex items-center gap-2">
                        <i class="fas fa-camera"></i>
                        <span>Snapshot</span>
                      </button>
                      <button id="switchCameraBtn"
                        class="px-6 py-2 bg-gray-700 text-white rounded-lg text-sm font-semibold hover:bg-gray-800 transition-all flex items-center gap-2"
                        style="display: none;">
                        <i class="fas fa-sync-alt"></i>
                        <span>Switch Camera</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Visitor Passes Page -->
        <div id="page-visitor" class="page-content hidden">
          <div class="space-y-6 bg-guard-bg">
            <!-- Header -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div
                  class="w-10 h-10 rounded-lg bg-gradient-to-br from-gray-600 to-gray-700 flex items-center justify-center text-white">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z">
                    </path>
                  </svg>
                </div>
                <div>
                  <h2 class="text-2xl font-bold text-gray-800">Visitor Passes</h2>
                  <p class="text-sm text-gray-600">View active visitor passes</p>
                </div>
              </div>
              <button id="refreshVisitorPasses"
                class="px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg text-sm font-semibold hover:from-blue-600 hover:to-blue-700 transition-all shadow-sm">
                🔄 Refresh
              </button>
            </div>

            <!-- Search Bar -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
              <input type="text" id="visitorSearchInput" placeholder="🔍 Search by visitor name, plate number..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Visitor Passes Cards Grid -->
            <div id="visitorPassesContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div class="col-span-full text-center py-12">
                <div
                  class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-gray-300 border-t-purple-500">
                </div>
                <p class="mt-2 text-gray-500">Loading visitor passes...</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <!-- Floating Camera Toggle Button -->
  <button id="floatingCameraToggle"
    class="fixed bottom-6 right-6 w-14 h-14 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-full shadow-lg hover:shadow-xl transition-all z-50 flex items-center justify-center group hover:w-auto hover:px-4">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
      </path>
    </svg>
    <span class="ml-2 hidden group-hover:inline-block text-sm font-semibold whitespace-nowrap">Camera</span>
  </button>

  <!-- Floating Camera Window -->
  <div id="floatingCameraWindow"
    class="hidden fixed glass rounded-xl shadow-2xl border border-white/20 backdrop-blur-xl"
    style="width: 640px; height: 480px; bottom: 90px; right: 20px; z-index: var(--z-floating);">
    <!-- Window Header -->
    <div id="cameraWindowHeader"
      class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-4 py-2.5 rounded-t-lg cursor-move flex items-center justify-between"
      style="user-select: none;">
      <div class="flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
          </path>
        </svg>
        <span class="font-semibold text-sm">Live Camera</span>
        <div class="flex items-center gap-1 ml-2" id="floatCameraStatus">
          <span class="w-2 h-2 rounded-full bg-gray-300"></span>
          <span class="text-xs">Offline</span>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button id="minimizeCameraBtn" class="hover:bg-white hover:bg-opacity-20 p-1 rounded transition-colors"
          title="Minimize">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
          </svg>
        </button>
        <button id="closeCameraBtn" class="hover:bg-white hover:bg-opacity-20 p-1 rounded transition-colors"
          title="Close">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
    </div>

    <!-- Camera View -->
    <div class="relative bg-black" style="height: 360px;">
      <video id="floatingCamera" autoplay playsinline muted class="w-full h-full object-cover"
        style="display: block;"></video>
      <canvas id="floatingCameraCanvas" class="hidden"></canvas>

      <div
        class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-gray-700 to-gray-900 flex justify-center items-center"
        id="floatingCameraOverlay">
        <div class="text-center text-gray-400">
          <svg class="w-16 h-16 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
            </path>
          </svg>
          <p class="text-sm font-semibold">Camera Off</p>
        </div>
      </div>

      <div id="floatingTimestamp"
        class="hidden absolute bottom-2 left-2 bg-black bg-opacity-70 text-white px-2 py-1 rounded text-xs font-mono">
        --:--:--
      </div>

      <div id="floatingSnapshotFlash" class="hidden absolute inset-0 bg-white pointer-events-none"></div>
    </div>

    <!-- Controls -->
    <div class="p-3 bg-white rounded-b-lg flex items-center justify-between gap-2">
      <button id="floatingToggleCamera"
        class="flex-1 px-3 py-2 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded text-sm font-semibold hover:from-blue-600 hover:to-blue-700 transition-all flex items-center justify-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z">
          </path>
        </svg>
        <span id="floatingCameraBtnText">Start</span>
      </button>
      <button id="floatingSnapshotBtn"
        class="hidden px-3 py-2 bg-green-500 text-white rounded text-sm font-semibold hover:bg-green-600 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z">
          </path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z">
          </path>
        </svg>
      </button>
      <button id="floatingSwitchCameraBtn"
        class="hidden px-3 py-2 bg-gray-700 text-white rounded text-sm font-semibold hover:bg-gray-800 transition-all">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
          </path>
        </svg>
      </button>
    </div>

    <!-- Resize Handle -->
    <div class="absolute bottom-0 right-0 w-4 h-4 cursor-nwse-resize" id="resizeHandle">
      <svg class="w-full h-full text-gray-400" fill="currentColor" viewBox="0 0 16 16">
        <path d="M16 16V11h-1v4h-4v1h5zM16 7V2h-5v1h4v4h1zM1 7V2h5V1H0v6h1zM1 11v5h5v-1H1v-4H0v5h1z" />
      </svg>
    </div>
  </div>

  <!-- Image Zoom Modal -->
  <div id="imageZoomModal" class="fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center p-4"
    style="display: none; z-index: var(--z-imagezoom);" onclick="closeImageZoom()">
    <button class="absolute top-4 right-4 text-white text-4xl hover:text-gray-300 transition-colors"
      onclick="(event||window.event).stopPropagation(); closeImageZoom()">&times;</button>
    <img id="zoomedImage" src="" alt="Zoomed" class="max-w-full max-h-full object-contain rounded-lg shadow-2xl">
  </div>



  <!-- Guard Application Scripts - Load in order: utils -> config -> features -> main -->
  <script src="../js/logger.js?v=<?= time() ?>"></script>
  <script src="../../assets/js/toast.js?v=<?php echo time(); ?>"></script>
  <script src="../../assets/js/keyboard-shortcuts.js?v=<?php echo time(); ?>"></script>
  <script src="../../assets/js/mobile-gestures.js?v=<?php echo time(); ?>"></script>
  <script src="../js/guard-dark-mode.js?v=<?php echo time(); ?>"></script>
  <script src="../js/guard-qr-modal.js?v=<?= time() ?>"></script>
  <script src="../js/guard_side.js?v=<?= time() ?>"></script>
  <script src="../js/camera-handler.js?v=<?= time() ?>"></script>
  <script src="../js/main-camera-handler.js?v=<?= time() ?>"></script>
  <script>
    // Image zoom/open/close handled centrally in `guard_side.js` (openImageZoom / closeImageZoom)

    // Live time update
    function updateLiveTime() {
      const liveTime = document.getElementById('liveTime');
      if (liveTime) {
        const now = new Date();
        liveTime.textContent = now.toLocaleTimeString();
      }
    }
    setInterval(updateLiveTime, 1000);
    updateLiveTime();
  </script>
</body>

</html>