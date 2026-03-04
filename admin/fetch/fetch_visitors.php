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
    JOIN homeowners h ON vp.homeowner_id = h.id
    WHERE vp.status = 'pending'
    ORDER BY vp.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get all visitor passes
$passes = $pdo->query("
    SELECT vp.*, h.name as homeowner_name
    FROM visitor_passes vp
    JOIN homeowners h ON vp.homeowner_id = h.id
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

<div class="flex items-center gap-3 mb-4 flex-wrap table-tools">
  <button id="createPassBtn" class="btn btn-add">➕ Create Visitor Pass</button>
  <button id="refreshPassesBtn" class="btn btn-primary">🔄 Refresh</button>
  <button id="exportPassesBtn" class="btn btn-add">📥 Export CSV</button>
</div>

<div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">
  <table id="passesTable" class="w-full text-sm">
    <thead class="border-b border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700">
      <tr>
        <th class="text-left font-semibold text-slate-900 dark:text-slate-200 px-4 py-3 uppercase tracking-wider text-xs">Visitor Name</th>
        <th class="text-left font-semibold text-slate-900 dark:text-slate-200 px-4 py-3 uppercase tracking-wider text-xs">Plate Number</th>
        <th class="text-left font-semibold text-slate-900 dark:text-slate-200 px-4 py-3 uppercase tracking-wider text-xs">Homeowner</th>
        <th class="text-center font-semibold text-slate-900 dark:text-slate-200 px-4 py-3 uppercase tracking-wider text-xs">QR Code</th>
        <th class="text-left font-semibold text-slate-900 dark:text-slate-200 px-4 py-3 uppercase tracking-wider text-xs">Valid From</th>
        <th class="text-left font-semibold text-slate-900 dark:text-slate-200 px-4 py-3 uppercase tracking-wider text-xs">Valid Until</th>
        <th class="text-left font-semibold text-slate-900 dark:text-slate-200 px-4 py-3 uppercase tracking-wider text-xs">Status</th>
        <th class="text-center font-semibold text-slate-900 dark:text-slate-200 px-4 py-3 uppercase tracking-wider text-xs">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
      <?php if (empty($passes)): ?>
        <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No visitor passes yet</td></tr>
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
              <span class="status-badge status-<?php echo htmlspecialchars($p['status']); ?>">
                <?php echo htmlspecialchars(ucfirst($p['status'])); ?>
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <?php if ($p['status'] === 'pending'): ?>
                <!-- Dropdown for pending passes -->
                <div class="relative inline-block text-left" x-data="{ open: false }">
                  <button @click="open = !open" @click.away="open = false" type="button" class="inline-flex items-center justify-center w-full rounded-md border border-gray-300 dark:border-slate-600 shadow-sm px-4 py-2 bg-white dark:bg-slate-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-blue-500">
                    Actions
                    <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                  </button>

                  <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="origin-top-right absolute right-0 mt-2 w-40 rounded-md shadow-lg bg-white dark:bg-slate-700 ring-1 ring-black ring-opacity-5 z-10" style="display: none;">
                    <div class="py-1" role="menu">
                      <button onclick="window.approveVisitorPass(<?php echo $p['id']; ?>)" class="w-full text-left px-4 py-2 text-sm text-green-700 hover:bg-green-50 flex items-center gap-2" role="menuitem">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Approve
                      </button>
                      <button onclick="window.rejectVisitorPass(<?php echo $p['id']; ?>)" class="w-full text-left px-4 py-2 text-sm text-red-700 hover:bg-red-50 flex items-center gap-2" role="menuitem">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Reject
                      </button>
                    </div>
                  </div>
                </div>
              <?php elseif ($p['status'] === 'approved' || $p['status'] === 'active'): ?>
                <button class="btn warn cancelPassBtn" data-id="<?php echo $p['id']; ?>">Cancel</button>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
