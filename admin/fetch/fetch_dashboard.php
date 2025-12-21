<?php
// Security: Role-based access control
require_once __DIR__ . '/../../includes/session_admin_unified.php';
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
  http_response_code(403);
  header('Content-Type: application/json');
  exit(json_encode(['error' => 'Unauthorized access']));
}

// admin/fetch/fetch_dashboard.php
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/query_cache.php';

// Cache key for dashboard stats
$cacheKey = 'dashboard_stats_' . ($_SESSION['user_id'] ?? 'guest');
$stats = QueryCache::get($cacheKey);

if (!$stats) {
  // Fetch fresh data
  try {
    // Dashboard Overview Stats
    $totalHomeowners = $pdo->query("SELECT COUNT(*) FROM homeowners")->fetchColumn();
  } catch (Exception $e) {
    $totalHomeowners = 'N/A';
  }

  try {
    $recentLogsCount = $pdo->query("SELECT COUNT(*) FROM recent_logs WHERE log_time >= (NOW() - INTERVAL 1 DAY)")->fetchColumn();
  } catch (Exception $e) {
    $recentLogsCount = 'N/A';
  }

  try {
    // Analytics Dashboard Stats
    $totalLogs = $pdo->query("SELECT COUNT(*) FROM recent_logs")->fetchColumn();
    $logsToday = $pdo->query("SELECT COUNT(*) FROM recent_logs WHERE DATE(log_time) = CURDATE()")->fetchColumn();
    $allowedToday = $pdo->query("SELECT COUNT(*) FROM recent_logs WHERE DATE(log_time) = CURDATE() AND status = 'IN'")->fetchColumn();
    $deniedToday = $pdo->query("SELECT COUNT(*) FROM recent_logs WHERE DATE(log_time) = CURDATE() AND status = 'OUT'")->fetchColumn();
  } catch (Exception $e) {
    $totalLogs = 'N/A';
    $logsToday = 'N/A';
    $allowedToday = 'N/A';
    $deniedToday = 'N/A';
  }

  // Cache the stats for 2 minutes
  $stats = compact('totalHomeowners', 'recentLogsCount', 'totalLogs', 'logsToday', 'allowedToday', 'deniedToday');
  QueryCache::set($cacheKey, $stats, 120);
} else {
  // Extract from cache
  extract($stats);
}
?>
<!-- Dashboard Header -->
<div class="mb-6">
  <div class="flex items-center gap-3 mb-2">
    <div
      class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 text-white">
      <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
        </path>
      </svg>
    </div>
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Dashboard Overview</h1>
      <p class="text-sm text-gray-500">Real-time system statistics and activity</p>
    </div>
  </div>
</div>

<!-- Main Dashboard Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
  <div
    class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition-all">
    <div class="flex items-start justify-between mb-4">
      <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-50 border border-gray-100">
        <svg class="h-6 w-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
          </path>
        </svg>
      </div>
    </div>
    <div>
      <p class="text-sm font-medium text-gray-500 mb-1">Total Homeowners</p>
      <div class="flex items-baseline gap-2">
        <p class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($totalHomeowners); ?></p>
        <span class="text-xs font-medium text-green-600 bg-green-50 px-2 py-0.5 rounded-full">+2.5%</span>
      </div>
      <p class="text-xs text-gray-500 mt-2">Registered residents</p>
    </div>
  </div>

  <div
    class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm hover:shadow-md transition-all">
    <div class="flex items-start justify-between mb-4">
      <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-50 border border-gray-100">
        <svg class="h-6 w-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
          </path>
        </svg>
      </div>
    </div>
    <div>
      <p class="text-sm font-medium text-gray-500 mb-1">24h Access Logs</p>
      <div class="flex items-baseline gap-2">
        <p class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($recentLogsCount); ?></p>
        <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">Coming soon</span>
      </div>
      <p class="text-xs text-gray-500 mt-2">Activity in last 24h</p>
    </div>
  </div>
</div>

<!-- Analytics Dashboard -->
<div class="mb-4">
  <div class="flex items-center gap-2 mb-1">
    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-gradient-to-br from-emerald-500 to-emerald-600">
      <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
        </path>
      </svg>
    </div>
    <h2 class="text-xl font-bold text-gray-900">Today's Analytics</h2>
  </div>
  <p class="text-sm text-gray-500">Real-time access control statistics</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
  <div
    class="relative rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md transition-all hover:-translate-y-0.5">
    <div class="flex items-center justify-between mb-3">
      <div class="flex h-10 w-10 items-center justify-center rounded-md bg-gray-100">
        <svg class="h-5 w-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
          </path>
        </svg>
      </div>
    </div>
    <p class="text-sm font-medium text-gray-600 mb-1">Total Homeowners</p>
    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($totalHomeowners); ?></p>
    <div class="absolute top-0 left-0 h-0.5 w-full bg-gradient-to-r from-blue-500 to-blue-400"></div>
  </div>

  <div
    class="relative rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md transition-all hover:-translate-y-0.5">
    <div class="flex items-center justify-between mb-3">
      <div class="flex h-10 w-10 items-center justify-center rounded-md bg-green-50">
        <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
    </div>
    <p class="text-sm font-medium text-gray-600 mb-1">Entries Today</p>
    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($allowedToday); ?></p>
    <div class="absolute top-0 left-0 h-0.5 w-full bg-gradient-to-r from-green-500 to-green-400"></div>
  </div>

  <div
    class="relative rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md transition-all hover:-translate-y-0.5">
    <div class="flex items-center justify-between mb-3">
      <div class="flex h-10 w-10 items-center justify-center rounded-md bg-red-50">
        <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
      </div>
    </div>
    <p class="text-sm font-medium text-gray-600 mb-1">Exits Today</p>
    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($deniedToday); ?></p>
    <div class="absolute top-0 left-0 h-0.5 w-full bg-gradient-to-r from-red-500 to-red-400"></div>
  </div>

  <div
    class="relative rounded-lg border border-gray-200 bg-white p-4 shadow-sm hover:shadow-md transition-all hover:-translate-y-0.5">
    <div class="flex items-center justify-between mb-3">
      <div class="flex h-10 w-10 items-center justify-center rounded-md bg-gray-100">
        <svg class="h-5 w-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
          </path>
        </svg>
      </div>
    </div>
    <p class="text-sm font-medium text-gray-600 mb-1">Total Logs Today</p>
    <p class="text-2xl font-bold text-gray-900"><?php echo number_format($logsToday); ?></p>
    <div class="absolute top-0 left-0 h-0.5 w-full bg-gradient-to-r from-purple-500 to-purple-400"></div>
  </div>
</div>

<!-- Analytics Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
  <!-- Status Distribution Pie Chart -->
  <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
    <div class="flex items-center gap-2 mb-4">
      <div class="flex h-8 w-8 items-center justify-center rounded-md bg-blue-100">
        <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
        </svg>
      </div>
      <h3 class="text-lg font-semibold text-gray-900">Today's Access Status</h3>
    </div>
    <div class="relative" style="height: 256px; width: 100%;">
      <canvas id="statusPieChart" style="max-height: 256px; display: block;"></canvas>
    </div>
  </div>

  <!-- Weekly Activity Line Chart -->
  <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
    <div class="flex items-center gap-2 mb-4">
      <div class="flex h-8 w-8 items-center justify-center rounded-md bg-emerald-100">
        <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
        </svg>
      </div>
      <h3 class="text-lg font-semibold text-gray-900">7-Day Activity Trend</h3>
    </div>
    <div class="relative" style="height: 256px; width: 100%;">
      <canvas id="weeklyLineChart" style="max-height: 256px; display: block;"></canvas>
    </div>
  </div>
</div>

<script>
  // Wait for both Chart.js to load AND DOM to be ready
  let chartInitAttempts = 0;
  const maxAttempts = 25;

  function waitForChartJS() {
    if (typeof Chart !== 'undefined') {
      console.log('[Dashboard] ✅ Chart.js loaded, initializing charts...');
      initCharts();
    } else if (chartInitAttempts < maxAttempts) {
      chartInitAttempts++;
      console.log(`[Dashboard] ⏳ Waiting for Chart.js... (attempt ${chartInitAttempts}/${maxAttempts})`);
      setTimeout(waitForChartJS, 200);
    } else {
      console.error('[Dashboard] ❌ Chart.js failed to load after', maxAttempts, 'attempts');
      // Show error message in chart containers
      ['statusPieChart', 'weeklyLineChart'].forEach(id => {
        const canvas = document.getElementById(id);
        if (canvas) {
          canvas.parentElement.innerHTML = '<div class="flex items-center justify-center h-full text-red-500"><p>⚠️ Chart library not loaded. Please refresh the page.</p></div>';
        }
      });
    }
  }

  function initCharts() {
    // Status Pie Chart
    const statusCtx = document.getElementById('statusPieChart');
    if (statusCtx) {
      console.log('[Dashboard] Creating pie chart with data:', [<?php echo $allowedToday; ?>, <?php echo $deniedToday; ?>]);

      const total = <?php echo $allowedToday + $deniedToday; ?>;
      if (total === 0) {
        statusCtx.parentElement.innerHTML = '<div class="flex flex-col items-center justify-center h-full text-gray-400"><svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg><p class="text-sm font-medium">No activity data today</p><p class="text-xs">Chart will display once logs are recorded</p></div>';
      } else {
        new Chart(statusCtx, {
          type: 'doughnut',
          data: {
            labels: ['Entries', 'Exits'],
            datasets: [{
              data: [<?php echo $allowedToday; ?>, <?php echo $deniedToday; ?>],
              backgroundColor: [
                'rgba(16, 185, 129, 0.8)',  // Green for IN
                'rgba(59, 130, 246, 0.8)'   // Blue for OUT
              ],
              borderColor: [
                'rgb(16, 185, 129)',
                'rgb(59, 130, 246)'
              ],
              borderWidth: 2,
              hoverOffset: 8
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                position: 'bottom',
                labels: {
                  padding: 20,
                  font: {
                    size: 13,
                    weight: '500'
                  },
                  usePointStyle: true,
                  pointStyle: 'circle'
                }
              },
              tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: { size: 14, weight: 'bold' },
                bodyFont: { size: 13 },
                callbacks: {
                  label: function (context) {
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                  }
                }
              }
            },
            animation: {
              animateRotate: true,
              animateScale: true
            }
          }
        });
        console.log('[Dashboard] ✅ Pie chart created successfully');
      }
    } else {
      console.warn('[Dashboard] ⚠️ statusPieChart canvas not found');
    }

    // Weekly Line Chart - Fetch data
    console.log('[Dashboard] Fetching weekly stats...');

    // Ensure Chart.js is loaded before proceeding
    if (typeof Chart === 'undefined') {
      console.error('[Dashboard] Chart.js not loaded yet');
      return;
    }

    fetch('../api/get_weekly_stats.php', {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json'
      }
    })
      .then(res => {
        console.log('[Dashboard] Weekly stats response status:', res.status);
        if (!res.ok) {
          return res.json().then(err => {
            throw new Error(err.error || 'HTTP ' + res.status);
          });
        }
        return res.json();
      })
      .then(data => {
        console.log('[Dashboard] Weekly stats data:', data);
        if (data.success) {
          const weeklyCtx = document.getElementById('weeklyLineChart');
          if (weeklyCtx) {
            const hasData = data.values.some(v => v > 0);

            if (!hasData) {
              weeklyCtx.parentElement.innerHTML = '<div class="flex flex-col items-center justify-center h-full text-gray-400"><svg class="w-16 h-16 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg><p class="text-sm font-medium">No activity in the last 7 days</p><p class="text-xs">Chart will display once logs are recorded</p></div>';
            } else {
              new Chart(weeklyCtx, {
                type: 'line',
                data: {
                  labels: data.labels,
                  datasets: [{
                    label: 'Daily Access Entries',
                    data: data.values,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: 'rgb(59, 130, 246)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointHoverBackgroundColor: 'rgb(59, 130, 246)',
                    pointHoverBorderColor: '#fff'
                  }]
                },
                options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  interaction: {
                    mode: 'index',
                    intersect: false
                  },
                  plugins: {
                    legend: {
                      display: true,
                      position: 'top',
                      align: 'end',
                      labels: {
                        boxWidth: 12,
                        boxHeight: 12,
                        padding: 15,
                        font: { size: 12, weight: '500' },
                        usePointStyle: true
                      }
                    },
                    tooltip: {
                      backgroundColor: 'rgba(0, 0, 0, 0.8)',
                      padding: 12,
                      titleFont: { size: 14, weight: 'bold' },
                      bodyFont: { size: 13 },
                      mode: 'index',
                      intersect: false
                    }
                  },
                  scales: {
                    y: {
                      beginAtZero: true,
                      ticks: {
                        precision: 0,
                        font: { size: 11 },
                        color: '#6b7280'
                      },
                      grid: {
                        color: 'rgba(0, 0, 0, 0.05)',
                        drawBorder: false
                      }
                    },
                    x: {
                      ticks: {
                        font: { size: 11 },
                        color: '#6b7280'
                      },
                      grid: {
                        display: false,
                        drawBorder: false
                      }
                    }
                  },
                  animation: {
                    duration: 750,
                    easing: 'easeInOutQuart'
                  }
                }
              });
              console.log('[Dashboard] ✅ Line chart created successfully with', data.values.length, 'data points');
            }
          } else {
            console.warn('[Dashboard] ⚠️ Weekly chart canvas not found');
          }
        } else {
          console.error('[Dashboard] ❌ API returned error:', data.error || 'Unknown error');
          const weeklyCtx = document.getElementById('weeklyLineChart');
          if (weeklyCtx) {
            weeklyCtx.parentElement.innerHTML = `<div class="flex flex-col items-center justify-center h-full text-red-500">
            <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="font-medium">⚠️ Failed to load chart data</p>
            <p class="text-xs text-gray-500 mt-1">${data.error || 'Unknown error'}</p>
          </div>`;
          }
        }
      })
      .catch(err => {
        console.error('[Dashboard] ❌ Failed to load weekly stats:', err);
        const weeklyCtx = document.getElementById('weeklyLineChart');
        if (weeklyCtx) {
          weeklyCtx.parentElement.innerHTML = `<div class="flex flex-col items-center justify-center h-full text-red-500">
          <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <p class="font-medium">⚠️ Network Error</p>
          <p class="text-xs text-gray-500 mt-1">${err.message}</p>
        </div>`;
        }
      });
  }

  // Start waiting for Chart.js
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', waitForChartJS);
  } else {
    waitForChartJS();
  }

  // Log when script is loaded
  console.log('[Dashboard] Chart initialization script loaded');
</script>
<?php
// Get last 6 months of data for stacked bar charts
$months = [];
for ($i = 5; $i >= 0; $i--) {
  $months[] = date('Y-m', strtotime("-$i months"));
}

// Homeowner registrations (approved vs pending)
$homeownerStats = [];
foreach ($months as $month) {
  $stmt = $pdo->prepare("
        SELECT 
            COALESCE(account_status, 'approved') as status,
            COUNT(*) as count
        FROM homeowners
        WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
        GROUP BY status
    ");
  $stmt->execute([$month]);
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $approved = 0;
  $pending = 0;
  foreach ($results as $row) {
    if ($row['status'] === 'approved')
      $approved = (int) $row['count'];
    elseif ($row['status'] === 'pending')
      $pending = (int) $row['count'];
  }

  $homeownerStats[] = [
    'month' => date('M', strtotime($month . '-01')),
    'approved' => $approved,
    'pending' => $pending
  ];
}

// Access logs (entries vs exits) - using recent_logs table
$accessStats = [];
foreach ($months as $month) {
  $stmt = $pdo->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM recent_logs
        WHERE DATE_FORMAT(log_time, '%Y-%m') = ?
        GROUP BY status
    ");
  $stmt->execute([$month]);
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

  $entries = 0;
  $exits = 0;
  foreach ($results as $row) {
    if ($row['status'] === 'IN')
      $entries = (int) $row['count'];
    elseif ($row['status'] === 'OUT')
      $exits = (int) $row['count'];
  }

  $accessStats[] = [
    'month' => date('M', strtotime($month . '-01')),
    'entries' => $entries,
    'exits' => $exits
  ];
}

// Vehicle registrations - check if table exists first
$vehicleStats = [];
try {
  // Check if homeowner_vehicles table exists
  $tableCheck = $pdo->query("SHOW TABLES LIKE 'homeowner_vehicles'")->fetch();

  if ($tableCheck) {
    foreach ($months as $month) {
      $stmt = $pdo->prepare("
                SELECT COUNT(*) as count
                FROM homeowner_vehicles
                WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
                AND is_active = TRUE
            ");
      $stmt->execute([$month]);
      $count = $stmt->fetchColumn();

      $vehicleStats[] = [
        'month' => date('M', strtotime($month . '-01')),
        'count' => (int) $count
      ];
    }
  } else {
    // Table doesn't exist, return zeros
    foreach ($months as $month) {
      $vehicleStats[] = [
        'month' => date('M', strtotime($month . '-01')),
        'count' => 0
      ];
    }
  }
} catch (Exception $e) {
  // Fallback: return zeros if error
  foreach ($months as $month) {
    $vehicleStats[] = [
      'month' => date('M', strtotime($month . '-01')),
      'count' => 0
    ];
  }
}
?>

<!-- Stacked Bar Charts Section -->
<div class="mt-8" style="clear: both;">
  <div class="flex items-center gap-2 mb-4">
    <div class="flex h-8 w-8 items-center justify-center rounded-md bg-gradient-to-br from-violet-500 to-violet-600">
      <svg class="h-5 w-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
        </path>
      </svg>
    </div>
    <h2 class="text-xl font-bold text-gray-900">6-Month Trends</h2>
  </div>
  <p class="text-sm text-gray-500 mb-6">Visual analytics for system activity</p>

  <!-- Homeowner Registrations Chart -->
  <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm mb-6">
    <div class="border-b border-gray-200 pb-4 mb-4">
      <h3 class="text-lg font-semibold text-gray-900">Homeowner Registrations</h3>
      <p class="text-sm text-gray-500 mt-1">Last 6 months - Approved vs Pending</p>
    </div>
    <div class="flex gap-6 mb-4 justify-center flex-wrap">
      <div class="flex items-center gap-2">
        <div style="width: 12px; height: 12px; border-radius: 2px; background: #3b82f6;"></div>
        <span class="text-sm text-gray-600">Approved</span>
      </div>
      <div class="flex items-center gap-2">
        <div style="width: 12px; height: 12px; border-radius: 2px; background: #f59e0b;"></div>
        <span class="text-sm text-gray-600">Pending</span>
      </div>
    </div>
    <div style="position: relative; height: 300px;">
      <svg id="homeownerChart" width="100%" height="100%"></svg>
      <div id="tooltip1"
        style="position: absolute; background: #1f2937; color: white; font-size: 12px; padding: 12px; border-radius: 6px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); pointer-events: none; display: none; z-index: 1000;">
      </div>
    </div>
  </div>

  <!-- Access Logs Chart -->
  <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm mb-6">
    <div class="border-b border-gray-200 pb-4 mb-4">
      <h3 class="text-lg font-semibold text-gray-900">Access Activity</h3>
      <p class="text-sm text-gray-500 mt-1">Last 6 months - Entries vs Exits</p>
    </div>
    <div class="flex gap-6 mb-4 justify-center flex-wrap">
      <div class="flex items-center gap-2">
        <div style="width: 12px; height: 12px; border-radius: 2px; background: #10b981;"></div>
        <span class="text-sm text-gray-600">Entries</span>
      </div>
      <div class="flex items-center gap-2">
        <div style="width: 12px; height: 12px; border-radius: 2px; background: #ef4444;"></div>
        <span class="text-sm text-gray-600">Exits</span>
      </div>
    </div>
    <div style="position: relative; height: 300px;">
      <svg id="accessChart" width="100%" height="100%"></svg>
      <div id="tooltip2"
        style="position: absolute; background: #1f2937; color: white; font-size: 12px; padding: 12px; border-radius: 6px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); pointer-events: none; display: none; z-index: 1000;">
      </div>
    </div>
    <div style="padding-top: 16px; font-size: 14px;">
      <div style="color: #10b981; font-weight: 500; display: flex; align-items: center; gap: 4px;">
        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6">
          </path>
        </svg>
        <span>Showing total access activity for last 6 months</span>
      </div>
    </div>
  </div>

  <!-- Vehicle Registrations Chart -->
  <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm mb-6">
    <div class="border-b border-gray-200 pb-4 mb-4">
      <h3 class="text-lg font-semibold text-gray-900">Vehicle Registrations</h3>
      <p class="text-sm text-gray-500 mt-1">Last 6 months</p>
    </div>
    <div style="position: relative; height: 300px;">
      <svg id="vehicleChart" width="100%" height="100%"></svg>
      <div id="tooltip3"
        style="position: absolute; background: #1f2937; color: white; font-size: 12px; padding: 12px; border-radius: 6px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); pointer-events: none; display: none; z-index: 1000;">
      </div>
    </div>
  </div>
</div>

<script>
  // Stacked Bar Chart Data - Use let to allow redeclaration when page reloads
  let homeownerData = <?php echo json_encode($homeownerStats); ?>;
  let accessData = <?php echo json_encode($accessStats); ?>;
  let vehicleData = <?php echo json_encode($vehicleStats); ?>;

  function drawStackedBarChart(svgId, data, tooltipId, config) {
    const svg = document.getElementById(svgId);
    const tooltip = document.getElementById(tooltipId);

    if (!svg || !tooltip) return;

    const svgRect = svg.getBoundingClientRect();
    const width = svgRect.width;
    const height = svgRect.height;
    const padding = { top: 20, right: 20, bottom: 40, left: 40 };
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;

    svg.innerHTML = '';

    // Find max value
    const maxValue = Math.max(...data.map(d => {
      return config.stacked ?
        (d[config.keys[0]] || 0) + (d[config.keys[1]] || 0) :
        d[config.keys[0]] || 0;
    }));
    const scale = maxValue > 0 ? chartHeight / maxValue : 0;

    // Bar width
    const barWidth = chartWidth / data.length * 0.6;
    const gap = chartWidth / data.length * 0.4;

    // Grid lines
    const gridLines = 5;
    for (let i = 0; i <= gridLines; i++) {
      const y = padding.top + (chartHeight / gridLines) * i;
      const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
      line.setAttribute('x1', padding.left);
      line.setAttribute('y1', y);
      line.setAttribute('x2', width - padding.right);
      line.setAttribute('y2', y);
      line.setAttribute('stroke', '#e5e7eb');
      line.setAttribute('stroke-width', '1');
      svg.appendChild(line);

      // Y-axis labels
      const value = Math.round(maxValue - (maxValue / gridLines) * i);
      const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      text.setAttribute('x', padding.left - 10);
      text.setAttribute('y', y + 4);
      text.setAttribute('text-anchor', 'end');
      text.setAttribute('fill', '#9ca3af');
      text.setAttribute('font-size', '11');
      text.textContent = value;
      svg.appendChild(text);
    }

    // Draw bars
    data.forEach((item, i) => {
      const x = padding.left + (barWidth + gap) * i + gap / 2;

      if (config.stacked) {
        const val1 = item[config.keys[0]] || 0;
        const val2 = item[config.keys[1]] || 0;
        const height1 = val1 * scale;
        const height2 = val2 * scale;
        const totalHeight = height1 + height2;

        // First bar (bottom)
        const rect1 = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        rect1.setAttribute('x', x);
        rect1.setAttribute('y', height - padding.bottom - height1);
        rect1.setAttribute('width', barWidth);
        rect1.setAttribute('height', height1);
        rect1.setAttribute('fill', config.colors[0]);
        rect1.setAttribute('rx', '4');
        rect1.style.cursor = 'pointer';
        rect1.style.transition = 'opacity 0.2s';

        // Second bar (top)
        const rect2 = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        rect2.setAttribute('x', x);
        rect2.setAttribute('y', height - padding.bottom - totalHeight);
        rect2.setAttribute('width', barWidth);
        rect2.setAttribute('height', height2);
        rect2.setAttribute('fill', config.colors[1]);
        rect2.setAttribute('rx', '4');
        rect2.style.cursor = 'pointer';
        rect2.style.transition = 'opacity 0.2s';

        [rect1, rect2].forEach(rect => {
          rect.addEventListener('mouseenter', (e) => {
            rect.style.opacity = '0.8';
            showTooltip(e, item, tooltip, config);
          });
          rect.addEventListener('mousemove', (e) => moveTooltip(e, tooltip));
          rect.addEventListener('mouseleave', () => {
            rect.style.opacity = '1';
            hideTooltip(tooltip);
          });
        });

        svg.appendChild(rect1);
        svg.appendChild(rect2);
      } else {
        const val = item[config.keys[0]] || 0;
        const barHeight = val * scale;

        const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        rect.setAttribute('x', x);
        rect.setAttribute('y', height - padding.bottom - barHeight);
        rect.setAttribute('width', barWidth);
        rect.setAttribute('height', barHeight);
        rect.setAttribute('fill', config.colors[0]);
        rect.setAttribute('rx', '4');
        rect.style.cursor = 'pointer';
        rect.style.transition = 'opacity 0.2s';

        rect.addEventListener('mouseenter', (e) => {
          rect.style.opacity = '0.8';
          showTooltip(e, item, tooltip, config);
        });
        rect.addEventListener('mousemove', (e) => moveTooltip(e, tooltip));
        rect.addEventListener('mouseleave', () => {
          rect.style.opacity = '1';
          hideTooltip(tooltip);
        });

        svg.appendChild(rect);
      }

      // X-axis labels
      const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      text.setAttribute('x', x + barWidth / 2);
      text.setAttribute('y', height - padding.bottom + 20);
      text.setAttribute('text-anchor', 'middle');
      text.setAttribute('fill', '#6b7280');
      text.setAttribute('font-size', '12');
      text.textContent = item.month;
      svg.appendChild(text);
    });
  }

  function showTooltip(e, data, tooltip, config) {
    let content = '<div style="font-weight: 600; margin-bottom: 4px;">' + data.month + '</div>';
    config.keys.forEach((key, i) => {
      const label = config.labels[i];
      const color = config.colors[i];
      const value = data[key] || 0;
      content += `
            <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                <div style="width: 8px; height: 8px; border-radius: 2px; background: ${color};"></div>
                <span>${label}: ${value}</span>
            </div>
        `;
    });
    tooltip.innerHTML = content;
    tooltip.style.display = 'block';
    moveTooltip(e, tooltip);
  }

  function moveTooltip(e, tooltip) {
    tooltip.style.left = (e.pageX + 10) + 'px';
    tooltip.style.top = (e.pageY - 10) + 'px';
  }

  function hideTooltip(tooltip) {
    tooltip.style.display = 'none';
  }

  // Initialize charts when dashboard loads
  function initStackedCharts() {
    drawStackedBarChart('homeownerChart', homeownerData, 'tooltip1', {
      keys: ['approved', 'pending'],
      labels: ['Approved', 'Pending'],
      colors: ['#3b82f6', '#f59e0b'],
      stacked: true
    });

    drawStackedBarChart('accessChart', accessData, 'tooltip2', {
      keys: ['entries', 'exits'],
      labels: ['Entries', 'Exits'],
      colors: ['#10b981', '#ef4444'],
      stacked: true
    });

    drawStackedBarChart('vehicleChart', vehicleData, 'tooltip3', {
      keys: ['count'],
      labels: ['Vehicles'],
      colors: ['#8b5cf6'],
      stacked: false
    });
  }

  // Initialize on load and redraw on resize
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStackedCharts);
  } else {
    setTimeout(initStackedCharts, 100);
  }

  window.addEventListener('resize', initStackedCharts);
</script>