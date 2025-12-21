<?php
// Security: Role-based access control
require_once __DIR__ . '/../../includes/session_admin_unified.php';
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
  http_response_code(403);
  header('Content-Type: application/json');
  exit(json_encode(['error' => 'Unauthorized access']));
}

// admin/fetch/fetch_logs.php
require_once __DIR__ . '/../../db.php';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

error_log("[FETCH_LOGS] Page: $page, Per page: $per_page, Offset: $offset");

try {
  // Get total count
  $total = $pdo->query("SELECT COUNT(*) FROM recent_logs")->fetchColumn();
  $total_pages = ceil($total / $per_page);

  error_log("[FETCH_LOGS] Total logs in DB: $total, Total pages: $total_pages");

  // Get paginated logs with explicit INTEGER binding for LIMIT/OFFSET
  $stmt = $pdo->prepare("SELECT r.log_id, r.log_time, r.plate_number, r.status, r.created_at, h.name, h.vehicle_type
                         FROM recent_logs r
                         LEFT JOIN homeowners h ON r.plate_number = h.plate_number
                         ORDER BY r.created_at DESC, r.log_id DESC
                         LIMIT :limit OFFSET :offset");

  $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

  error_log("[FETCH_LOGS] Query executed - fetched " . count($logs) . " logs");
  if (!empty($logs)) {
    error_log("[FETCH_LOGS] First log ID: " . $logs[0]['log_id'] . ", Plate: " . $logs[0]['plate_number']);
  } else {
    error_log("[FETCH_LOGS] WARNING: Query returned 0 rows despite $total total logs in database!");
  }
} catch (Exception $e) {
  error_log("[LOGS] Fatal error: " . $e->getMessage());
  $logs = [];
  $total = 0;
  $total_pages = 0;
}
?>
<!-- Page Header -->
<div class="mb-6">
  <div class="flex items-center gap-3 mb-2">
    <div
      class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-cyan-500 to-cyan-600 text-white">
      <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
        </path>
      </svg>
    </div>
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Access Logs</h1>
      <p class="text-sm text-gray-500">Monitor vehicle entry and exit logs</p>
    </div>
  </div>
</div>

<!-- Action Bar -->
<div class="flex items-center gap-2 mb-4 flex-wrap">
  <button id="refreshLogsBtn"
    class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
      </path>
    </svg>
    Refresh
  </button>
  <button id="exportLogsBtn"
    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700 transition-colors">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
      </path>
    </svg>
    Export CSV
  </button>

  <div class="flex items-center gap-2 ml-auto">
    <div class="relative flex items-center">
      <svg class="absolute left-3 h-4 w-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor"
        viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
      </svg>
      <input type="text" id="logsSearchInput"
        class="h-10 px-4 pl-10 border border-gray-300 rounded-lg min-w-[280px] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
        placeholder="Search logs...">
    </div>
    <span id="logsSearchCount" class="text-sm text-gray-600 font-medium whitespace-nowrap"></span>
  </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
  <table id="logsTable" class="w-full text-sm">
    <thead class="border-b border-slate-200 bg-slate-50">
      <tr>
        <th class="text-left font-semibold text-slate-900 px-4 py-3 uppercase tracking-wider text-xs">Date/Time</th>
        <th class="text-left font-semibold text-slate-900 px-4 py-3 uppercase tracking-wider text-xs">Plate</th>
        <th class="text-left font-semibold text-slate-900 px-4 py-3 uppercase tracking-wider text-xs">Status</th>
        <th class="text-left font-semibold text-slate-900 px-4 py-3 uppercase tracking-wider text-xs">Owner</th>
        <th class="text-left font-semibold text-slate-900 px-4 py-3 uppercase tracking-wider text-xs">Vehicle</th>
        <th class="text-center font-semibold text-slate-900 px-4 py-3 uppercase tracking-wider text-xs">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
      <?php if (empty($logs)): ?>
        <tr>
          <td colspan="6" class="px-4 py-8 text-center text-slate-500">
            <div class="flex flex-col items-center justify-center py-12">
              <svg class="empty-state-illustration" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
              </svg>
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">No access logs found</h3>
              <p class="text-gray-500 dark:text-gray-400 max-w-sm mt-1">Access logs will appear here when vehicles scan in
                or out of the premises.</p>
            </div>
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($logs as $log): ?>
          <tr class="hover:bg-slate-100 transition-colors even:bg-slate-50">
            <td class="px-4 py-3 text-slate-700"><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
            <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($log['plate_number']); ?></td>
            <td class="px-4 py-3">
              <span class="status-badge status-<?php echo strtolower($log['status']); ?>">
                <?php echo htmlspecialchars($log['status']); ?>
              </span>
            </td>
            <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($log['name'] ?? 'Unknown'); ?></td>
            <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($log['vehicle_type'] ?? '-'); ?></td>
            <td class="px-4 py-3 text-center">
              <button class="btn btn-sm btn-danger deleteLogBtn" data-id="<?php echo $log['log_id']; ?>"
                title="Delete log entry">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                  </path>
                </svg>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
  <div class="flex items-center justify-between mt-6">
    <div class="text-sm text-gray-600">
      Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total); ?> of
      <?php echo number_format($total); ?> logs
    </div>

    <div class="flex items-center gap-2">
      <?php if ($page > 1): ?>
        <button class="btn btn-sm btn-secondary pagination-btn" data-page="<?php echo $page - 1; ?>">« Previous</button>
      <?php endif; ?>

      <?php
      // Show page numbers
      $start = max(1, $page - 2);
      $end = min($total_pages, $page + 2);

      if ($start > 1): ?>
        <button class="btn btn-sm btn-secondary pagination-btn" data-page="1">1</button>
        <?php if ($start > 2): ?>
          <span class="px-2 text-gray-500">...</span>
        <?php endif; ?>
      <?php endif; ?>

      <?php for ($i = $start; $i <= $end; $i++): ?>
        <button class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?> pagination-btn"
          data-page="<?php echo $i; ?>"><?php echo $i; ?></button>
      <?php endfor; ?>

      <?php if ($end < $total_pages): ?>
        <?php if ($end < $total_pages - 1): ?>
          <span class="px-2 text-gray-500">...</span>
        <?php endif; ?>
        <button class="btn btn-sm btn-secondary pagination-btn"
          data-page="<?php echo $total_pages; ?>"><?php echo $total_pages; ?></button>
      <?php endif; ?>

      <?php if ($page < $total_pages): ?>
        <button class="btn btn-sm btn-secondary pagination-btn" data-page="<?php echo $page + 1; ?>">Next »</button>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>