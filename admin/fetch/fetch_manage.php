<?php
// Security: Role-based access control
require_once __DIR__ . '/../../includes/session_admin_unified.php';
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
  http_response_code(403);
  header('Content-Type: application/json');
  exit(json_encode(['error' => 'Unauthorized access']));
}

// admin/fetch/fetch_manage.php
require_once __DIR__ . '/../../db.php';

// Auto-sync: ensure vehicles table has entries for all approved homeowners
try {
    $pdo->exec("
        INSERT IGNORE INTO vehicles (homeowner_id, plate_number, vehicle_type, color, is_primary, is_active, registered_at)
        SELECT h.id, h.plate_number, COALESCE(h.vehicle_type, 'Unknown'), COALESCE(h.color, 'Unknown'), 1, 1, NOW()
        FROM homeowners h
        LEFT JOIN vehicles v ON v.homeowner_id = h.id AND v.plate_number = h.plate_number
        WHERE h.account_status = 'approved'
          AND h.plate_number IS NOT NULL
          AND h.plate_number != ''
          AND v.id IS NULL
    ");
} catch (Exception $e) {
    error_log('[MANAGE] Vehicle sync error: ' . $e->getMessage());
}
?>
<!-- Page Header -->
<div class="mb-6">
  <div class="flex items-center gap-3 mb-2">
    <div
      class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 text-white">
      <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
        </path>
      </svg>
    </div>
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manage Records</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">View, add, edit, and manage homeowners</p>
    </div>
  </div>
</div>

<!-- Action Bar -->
<div class="flex items-center gap-2 mb-4 flex-wrap">
  <button id="refreshBtn" class="ta-btn ta-btn-secondary">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
      </path>
    </svg>
    Refresh
  </button>
  <button id="openCreateBtn" class="ta-btn ta-btn-primary">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
    </svg>
    Add New
  </button>
  <button id="exportManageBtn" class="ta-btn ta-btn-success">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
      </path>
    </svg>
    Export CSV
  </button>
  <button id="qrRegistrationBtn" class="ta-btn ta-btn-secondary">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M3 3h7v7H3V3zm0 11h7v7H3v-7zm11-11h7v7h-7V3zm2 2v3h3V5h-3zM5 5v3h3V5H5zm0 11v3h3v-3H5zm9 1h1v1h-1v-1zm2 0h1v1h-1v-1zm-2 2h1v1h-1v-1zm2 0h1v1h-1v-1zm2-2h1v1h-1v-1zm0 2h1v1h-1v-1z">
      </path>
    </svg>
    QR Registration
  </button>
  <div class="flex items-center gap-2 ml-auto">
    <div class="relative flex items-center">
      <svg class="absolute left-3 h-4 w-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor"
        viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
      </svg>
      <input type="text" id="searchInput"
        class="ta-input pl-10 min-w-[280px]"
        placeholder="Search records...">
    </div>
    <span id="searchCount" class="text-sm text-gray-600 font-medium whitespace-nowrap"></span>
  </div>
</div>

<?php
try {
  // Only show APPROVED homeowners in Manage Records
  // Pending accounts should only appear in Account Approvals
  // LEFT JOIN vehicles to get RFID binding status
  $stmt = $pdo->query("
        SELECT h.id, h.name, h.address, h.contact_number, h.plate_number, h.vehicle_type, h.account_status,
               v.id AS vehicle_id, v.rfid_uid, v.rfid_bound_at
        FROM homeowners h
        LEFT JOIN vehicles v ON v.homeowner_id = h.id AND v.plate_number = h.plate_number
        WHERE h.account_status = 'approved'
        ORDER BY h.id DESC
        LIMIT 1000
    ");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
  error_log("Error fetching homeowners: " . $e->getMessage());
  $rows = [];
}
?>

<div class="ta-table-wrapper">
  <table id="homeownersTable" class="ta-table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Plate</th>
        <th>Vehicle</th>
        <th>Contact</th>
        <th class="text-center">RFID</th>
        <th class="text-center">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr>
          <td colspan="6">
            <div class="ta-empty-state">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              <p>No approved homeowners found</p>
            </div>
          </td>
        </tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr class="hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors even:bg-slate-50 dark:even:bg-slate-800/50">
          <td class="px-4 py-3 text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($r['name'] ?? ''); ?></td>
          <td class="px-4 py-3 text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($r['plate_number'] ?? ''); ?></td>
          <td class="px-4 py-3 text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($r['vehicle_type'] ?? ''); ?></td>
          <td class="px-4 py-3 text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($r['contact_number'] ?? ''); ?></td>
          <td class="px-4 py-3 text-center">
            <?php if (!empty($r['rfid_uid'])): ?>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-700" title="UID: <?php echo htmlspecialchars($r['rfid_uid']); ?>">
                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                Bound
              </span>
            <?php elseif (!empty($r['vehicle_id'])): ?>
              <button class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors cursor-pointer btn-bind-rfid-manage"
                      data-vehicle-id="<?php echo $r['vehicle_id']; ?>"
                      data-plate="<?php echo htmlspecialchars($r['plate_number'] ?? ''); ?>"
                      data-owner="<?php echo htmlspecialchars($r['name'] ?? ''); ?>">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                </svg>
                Bind RFID
              </button>
            <?php else: ?>
              <span class="text-xs text-gray-400">—</span>
            <?php endif; ?>
          </td>
          <td class="px-4 py-3">
            <div class="flex items-center justify-center">
              <div class="ta-action-dropdown">
                <button type="button" class="ta-action-btn">
                  Actions
                  <svg class="ta-chevron" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                </button>
                <div class="ta-action-menu">
                  <button type="button" class="ta-action-menu-item btn-edit" data-id="<?php echo $r['id']; ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit
                  </button>
                  <div class="ta-action-divider"></div>
                  <button type="button" class="ta-action-menu-item red deleteBtn" data-id="<?php echo $r['id']; ?>">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Delete
                  </button>
                </div>
              </div>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>