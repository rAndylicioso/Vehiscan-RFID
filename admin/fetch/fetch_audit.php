<?php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin']))
  exit('Unauthorized');
require_once __DIR__ . '/../../db.php';

$filter_action = $_GET['action'] ?? '';
$filter_user = $_GET['user'] ?? '';

$sql = "SELECT * FROM audit_logs WHERE 1=1";
$params = [];

if ($filter_action) {
  $sql .= " AND action = ?";
  $params[] = $filter_action;
}

if ($filter_user) {
  $sql .= " AND username LIKE ?";
  $params[] = "%$filter_user%";
}

$sql .= " ORDER BY created_at DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique actions for filter
$actions = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
?>
<!-- Page Header -->
<div class="mb-6">
  <div class="flex items-center gap-3 mb-2">
    <div
      class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 text-white">
      <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
        </path>
      </svg>
    </div>
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Audit Logs</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">Track system actions and changes</p>
    </div>
  </div>
</div>

<!-- Action Bar -->
<div class="flex items-center gap-3 mb-6 flex-wrap">
  <select id="actionFilter"
    class="ta-select filter-select">
    <option value="">All Actions</option>
    <?php foreach ($actions as $action): ?>
      <option value="<?php echo htmlspecialchars($action ?? ''); ?>" <?php echo $action === $filter_action ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars($action ?? ''); ?>
      </option>
    <?php endforeach; ?>
  </select>

  <input type="text" id="userFilter"
    class="ta-input min-w-[160px]"
    placeholder="Filter by user..." value="<?php echo htmlspecialchars($filter_user); ?>">

  <button id="applyFilters" class="ta-btn ta-btn-primary">Apply Filters</button>
  <button id="clearFilters" class="ta-btn ta-btn-secondary">Clear</button>
  <button id="exportAuditBtn" class="ta-btn ta-btn-success">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
    Export CSV
  </button>

  <div class="flex items-center gap-2 ml-auto">
    <input type="text" id="auditSearchInput"
    class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg min-w-[250px] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent search-bar dark:bg-slate-700 dark:text-gray-200"
      placeholder="Search logs...">
    <span id="auditSearchCount" class="text-gray-600 text-sm font-medium"></span>
  </div>
</div>

<div class="ta-table-wrapper">
  <table id="auditTable" class="ta-table">
    <thead>
      <tr>
        <th>Time</th>
        <th>User</th>
        <th>Action</th>
        <th>Table</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($logs)): ?>
        <tr>
          <td colspan="4">
            <div class="ta-empty-state">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
              <p>No audit logs found</p>
            </div>
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($logs as $log): ?>
          <tr class="hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors even:bg-slate-50 dark:even:bg-slate-800/50">
            <td class="px-4 py-3 text-slate-700 dark:text-slate-300"><?php echo date('M d, H:i:s', strtotime($log['created_at'])); ?></td>
            <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-200"><?php echo htmlspecialchars($log['username'] ?? ''); ?></td>
            <td class="px-4 py-3"><span class="ta-badge info"><?php echo htmlspecialchars($log['action'] ?? ''); ?></span></td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($log['table_name'] ?? '-'); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Filter handlers are managed by attachAuditControls() in admin_panel.js -->