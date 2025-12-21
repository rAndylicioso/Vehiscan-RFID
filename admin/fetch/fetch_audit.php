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
      <h1 class="text-2xl font-bold text-gray-900">Audit Logs</h1>
      <p class="text-sm text-gray-500">Track system actions and changes</p>
    </div>
  </div>
</div>

<!-- Action Bar -->
<div class="flex items-center gap-3 mb-6 flex-wrap">
  <select id="actionFilter"
    class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent filter-select">
    <option value="">All Actions</option>
    <?php foreach ($actions as $action): ?>
      <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $action === $filter_action ? 'selected' : ''; ?>>
        <?php echo htmlspecialchars($action); ?>
      </option>
    <?php endforeach; ?>
  </select>

  <button id="applyFilters" class="btn btn-primary">Apply Filters</button>
  <button id="clearFilters" class="btn btn-secondary">Clear</button>
  <button id="exportAuditBtn" class="btn btn-add">📥 Export CSV</button>

  <div class="flex items-center gap-2 ml-auto">
    <input type="text" id="auditSearchInput"
      class="px-4 py-2 border border-gray-300 rounded-lg min-w-[250px] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent search-bar"
      placeholder="🔍 Search logs...">
    <span id="auditSearchCount" class="text-gray-600 text-sm font-medium"></span>
  </div>
</div>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
  <table id="auditTable" class="w-full text-sm">
    <thead class="border-b border-slate-200 bg-slate-50">
      <tr>
        <th class="text-left font-semibold text-slate-900 px-4 py-3 uppercase tracking-wider text-xs">Time</th>
        <th class="text-left font-semibold text-slate-900 px-4 py-3 uppercase tracking-wider text-xs">User</th>
        <th class="text-left font-semibold text-slate-900 px-4 py-3 uppercase tracking-wider text-xs">Action</th>
        <th class="text-left font-semibold text-slate-900 px-4 py-3 uppercase tracking-wider text-xs">Table</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
      <?php if (empty($logs)): ?>
        <tr>
          <td colspan="4" class="px-4 py-8 text-center text-slate-500">No audit logs found</td>
        </tr>
      <?php else: ?>
        <?php foreach ($logs as $log): ?>
          <tr class="hover:bg-slate-100 transition-colors even:bg-slate-50">
            <td class="px-4 py-3 text-slate-700"><?php echo date('M d, H:i:s', strtotime($log['created_at'])); ?></td>
            <td class="px-4 py-3 font-medium text-slate-900"><?php echo htmlspecialchars($log['username']); ?></td>
            <td class="px-4 py-3"><span class="action-badge"><?php echo htmlspecialchars($log['action']); ?></span></td>
            <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($log['table_name'] ?? '-'); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
  (function () {
    console.log('[Audit] Inline script loaded');

    // Attach handlers immediately since the fragment is already in DOM
    const applyBtn = document.getElementById('applyFilters');
    const clearBtn = document.getElementById('clearFilters');
    const actionFilter = document.getElementById('actionFilter');
    const userFilter = document.getElementById('userFilter');

    console.log('[Audit] Elements:', {
      applyBtn: !!applyBtn,
      clearBtn: !!clearBtn,
      actionFilter: !!actionFilter,
      userFilter: !!userFilter
    });

    if (applyBtn && actionFilter && userFilter) {
      applyBtn.addEventListener('click', async function (e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('[Audit] Apply filters clicked');

        const action = actionFilter.value || '';
        const user = userFilter.value || '';

        console.log('[Audit] Filter values:', { action, user });

        // Use window.loadPage if available (same pattern as manage page refresh)
        if (window.loadPage) {
          let queryString = '';
          if (action) queryString += `&action=${encodeURIComponent(action)}`;
          if (user) queryString += `&user=${encodeURIComponent(user)}`;

          const contentArea = document.getElementById('content-area');
          if (contentArea) {
            contentArea.innerHTML = "<div class='loading'>Loading...</div>";
          }

          try {
            const url = `fetch/fetch_audit.php?_=${Date.now()}${queryString}`;
            console.log('[Audit] Fetching:', url);

            const res = await fetch(url, {
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}`);

            const html = await res.text();
            if (contentArea) {
              contentArea.innerHTML = html;
            }

            if (window.showGrowl) window.showGrowl('Filters applied', 'success');
          } catch (err) {
            console.error('[Audit] Error:', err);
            if (contentArea) {
              contentArea.innerHTML = "<p style='color:red'>Failed to apply filters</p>";
            }
            if (window.showGrowl) window.showGrowl('Failed to apply filters', 'error');
          }
        }
      });
      console.log('[Audit] Apply handler attached');
    } else {
      console.error('[Audit] Missing elements for apply button');
    }

  })();
</script>

<style>
  .action-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.625rem;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 0.375rem;
    line-height: 1;
    background: #dbeafe;
    color: #1e40af;
  }
</style>