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
      <h1 class="text-2xl font-bold text-gray-900">Manage Records</h1>
      <p class="text-sm text-gray-500">View, add, edit, and manage homeowners</p>
    </div>
  </div>
</div>

<!-- Action Bar -->
<div class="flex items-center gap-2 mb-4 flex-wrap">
  <button id="refreshBtn"
    class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
      </path>
    </svg>
    Refresh
  </button>
  <button id="openCreateBtn"
    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 text-white rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
    </svg>
    Add New
  </button>
  <button id="exportManageBtn"
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
      <input type="text" id="searchInput"
        class="h-10 px-4 pl-10 border border-gray-300 rounded-lg min-w-[280px] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
        placeholder="Search records...">
    </div>
    <span id="searchCount" class="text-sm text-gray-600 font-medium whitespace-nowrap"></span>
  </div>
</div>

<?php
try {
  // Only show APPROVED homeowners in Manage Records
  // Pending accounts should only appear in Account Approvals
  $stmt = $pdo->query("
        SELECT id, name, address, contact_number, plate_number, vehicle_type, account_status 
        FROM homeowners 
        WHERE account_status = 'approved'
        ORDER BY id DESC
    ");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Debug output
  error_log("Fetched " . count($rows) . " APPROVED homeowner records for Manage Records");
} catch (Exception $e) {
  error_log("Error fetching homeowners: " . $e->getMessage());
  $rows = [];
}
?>

<div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
  <table id="homeownersTable" class="w-full text-sm">
    <thead class="border-b border-slate-200 bg-slate-50">
      <tr>
        <th class="text-left font-semibold text-slate-900 px-4 py-3 uppercase tracking-wider text-xs">Name</th>
        <th class="text-left font-semibold text-slate-900 px-4 py-3 uppercase tracking-wider text-xs">Plate</th>
        <th class="text-left font-semibold text-slate-900 px-4 py-3 uppercase tracking-wider text-xs">Vehicle</th>
        <th class="text-left font-semibold text-slate-900 px-4 py-3 uppercase tracking-wider text-xs">Contact</th>
        <th class="text-center font-semibold text-slate-900 px-4 py-3 uppercase tracking-wider text-xs">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
      <?php foreach ($rows as $r): ?>
        <tr class="hover:bg-slate-100 transition-colors even:bg-slate-50">
          <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($r['name']); ?></td>
          <td class="px-4 py-3 text-slate-700"><?php echo htmlspecialchars($r['plate_number']); ?></td>
          <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($r['vehicle_type']); ?></td>
          <td class="px-4 py-3 text-slate-600"><?php echo htmlspecialchars($r['contact_number']); ?></td>
          <td class="px-4 py-3">
            <div class="flex items-center justify-center gap-2">
              <button
                class="inline-flex items-center px-3 py-1.5 bg-gray-700 text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors gap-1 btn-edit"
                data-id="<?php echo $r['id']; ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                  </path>
                </svg>
                Edit
              </button>
              <button
                class="inline-flex items-center px-3 py-1.5 bg-red-500 text-white text-sm font-medium rounded-md hover:bg-red-600 transition-colors gap-1 deleteBtn"
                data-id="<?php echo $r['id']; ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                  </path>
                </svg>
                Delete
              </button>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>