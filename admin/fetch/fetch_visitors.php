<?php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) exit('Unauthorized');
require_once __DIR__ . '/../../db.php';

// Get all homeowners for dropdown
$homeowners = $pdo->query("SELECT id, name FROM homeowners ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get pending visitor passes
$pendingPasses = $pdo->query("
    SELECT vp.*, h.name as homeowner_name
    FROM visitor_passes vp
    LEFT JOIN homeowners h ON vp.homeowner_id = h.id
    WHERE vp.status = 'pending'
    ORDER BY vp.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get all visitor passes (with computed display_status for expired/upcoming)
$passes = $pdo->query("
    SELECT vp.*, h.name as homeowner_name,
        CASE
            WHEN vp.status IN ('active','approved') AND NOW() > vp.valid_until THEN 'expired'
            WHEN vp.status IN ('active','approved') AND NOW() < vp.valid_from THEN 'upcoming'
            ELSE vp.status
        END AS display_status
    FROM visitor_passes vp
    LEFT JOIN homeowners h ON vp.homeowner_id = h.id
    ORDER BY vp.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.visitor-card {
    background: linear-gradient(to bottom, #ffffff, #fafafa);
}
.dark .visitor-card {
    background: linear-gradient(to bottom, #1e293b, #1e293b);
}
.visitor-card:hover {
    background: linear-gradient(to bottom, #ffffff, #f0f9ff);
}
.dark .visitor-card:hover {
    background: linear-gradient(to bottom, #1e293b, #1e3a5f);
}
.info-grid {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}
.dark .info-grid {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
}
</style>

<h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6 flex items-center gap-3">
    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
    </svg>
    Visitor Pass Management
</h2>



<!-- All Visitor Passes Table -->

<div class="flex items-center gap-3 mb-4 flex-wrap">
  <button id="createPassBtn" class="ta-btn ta-btn-primary">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
    Create Visitor Pass
  </button>
  <button id="refreshPassesBtn" class="ta-btn ta-btn-secondary">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
    Refresh
  </button>
  <button id="exportPassesBtn" class="ta-btn ta-btn-success">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
    Export CSV
  </button>
  <div class="flex items-center gap-2 ml-auto">
    <div class="relative flex items-center">
      <svg class="absolute left-3 h-4 w-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
      </svg>
      <input type="text" id="visitorsSearchInput"
        class="h-10 px-4 pl-10 border border-gray-300 dark:border-slate-600 rounded-lg min-w-[280px] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all dark:bg-slate-700 dark:text-gray-200"
        placeholder="Search visitor passes...">
    </div>
    <span id="visitorsSearchCount" class="text-sm text-gray-600 font-medium whitespace-nowrap"></span>
  </div>
</div>

<div class="ta-table-wrapper">
  <table id="passesTable" class="ta-table">
    <thead>
      <tr>
        <th>Visitor Name</th>
        <th>Plate Number</th>
        <th>Homeowner</th>
        <th class="text-center">QR Code</th>
        <th>Valid From</th>
        <th>Valid Until</th>
        <th>Status</th>
        <th class="text-center">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
      <?php if (empty($passes)): ?>
        <tr>
          <td colspan="8">
            <div class="ta-empty-state">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
              </svg>
              <p>No visitor passes yet</p>
            </div>
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($passes as $p): ?>
          <tr class="hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
            <td class="px-4 py-3 text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($p['visitor_name'] ?? ''); ?></td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($p['visitor_plate'] ?? ''); ?></td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($p['homeowner_name'] ?? ''); ?></td>
            <td class="px-4 py-3 text-center">
              <?php if (!empty($p['qr_code'])): ?>
                <img src="<?php echo htmlspecialchars($p['qr_code'] ?? ''); ?>" alt="QR Code" class="w-16 h-16 mx-auto qr-clickable" style="image-rendering: pixelated;">
              <?php else: ?>
                <span class="text-slate-400 text-xs">No QR</span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-400"><?php echo date('M d, Y H:i', strtotime($p['valid_from'])); ?></td>
            <td class="px-4 py-3 text-slate-600 dark:text-slate-400"><?php echo date('M d, Y H:i', strtotime($p['valid_until'])); ?></td>
            <td class="px-4 py-3">
              <?php
                $displayStatus = $p['display_status'] ?? $p['status'];
                $badgeMap = ['active' => 'success', 'approved' => 'success', 'used' => 'info', 'pending' => 'warning', 'expired' => 'neutral', 'upcoming' => 'info', 'cancelled' => 'danger', 'rejected' => 'danger'];
                $badgeColor = $badgeMap[$displayStatus] ?? 'neutral';
              ?>
              <span class="ta-badge <?php echo $badgeColor; ?>">
                <?php echo htmlspecialchars(ucfirst($displayStatus)); ?>
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <?php if ($p['status'] === 'pending'): ?>
                <div class="ta-action-dropdown">
                  <button type="button" class="ta-action-btn">
                    Actions
                    <svg class="ta-chevron" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                  </button>
                  <div class="ta-action-menu">
                    <button type="button" class="ta-action-menu-item green" onclick="window.approveVisitorPass(<?php echo $p['id']; ?>)">
                      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                      Approve
                    </button>
                    <div class="ta-action-divider"></div>
                    <button type="button" class="ta-action-menu-item red" onclick="window.rejectVisitorPass(<?php echo $p['id']; ?>)">
                      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                      Reject
                    </button>
                  </div>
                </div>
              <?php elseif (($p['status'] === 'approved' || $p['status'] === 'active') && $displayStatus !== 'expired'): ?>
                <div class="ta-action-dropdown">
                  <button type="button" class="ta-action-btn">
                    Actions
                    <svg class="ta-chevron" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                  </button>
                  <div class="ta-action-menu">
                    <button type="button" class="ta-action-menu-item red cancelPassBtn" data-id="<?php echo $p['id']; ?>">
                      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                      Cancel Pass
                    </button>
                  </div>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
