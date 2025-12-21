<?php
// Security: Role-based access control
require_once __DIR__ . '/../../includes/session_admin_unified.php';
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized access']));
}

require_once __DIR__ . '/../../db.php';

$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$sql = "SELECT id, username, role, created_at FROM users WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND username LIKE ?";
    $params[] = "%$search%";
}

if ($role_filter) {
    $sql .= " AND role = ?";
    $params[] = $role_filter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalEmployees = count($employees);
$roleCount = array_count_values(array_column($employees, 'role'));
?>

<!-- Page Header -->
<div class="mb-6">
  <div class="flex items-center gap-3 mb-2">
    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 text-white">
      <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
      </svg>
    </div>
    <div>
      <h1 class="text-2xl font-bold text-gray-900">Employee Management</h1>
      <p class="text-sm text-gray-500">View and manage all system employees</p>
    </div>
  </div>
</div>

<!-- Action Bar -->
<div class="flex items-center gap-2 mb-4 flex-wrap">
  <button id="createEmployeeBtn" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 text-white rounded-lg text-sm font-medium hover:bg-gray-800 transition-colors">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
    </svg>
    Add Employee
  </button>
  <button id="refreshEmployeesBtn" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
    </svg>
    Refresh
  </button>
  <div class="flex items-center gap-2 ml-auto">
    <div class="relative flex items-center">
      <svg class="absolute left-3 h-4 w-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
      </svg>
      <input type="text" id="employeeSearchInput" class="h-10 px-4 pl-10 border border-gray-300 rounded-lg min-w-[280px] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" placeholder="Search employees...">
    </div>
    <select id="employeeRoleFilter" class="h-10 px-4 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      <option value="">All Roles</option>
      <option value="admin">Admin</option>
      <option value="guard">Guard</option>
      <option value="super_admin">Super Admin</option>
    </select>
  </div>
</div>

<!-- Employee Table -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
  <table id="employeeTable" class="w-full">
    <thead class="bg-gray-50 border-b border-gray-200">
      <tr>
        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Username</th>
        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created</th>
        <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-200">
      <?php if (empty($employees)): ?>
        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">No employees found</td></tr>
      <?php else: ?>
        <?php foreach ($employees as $employee): ?>
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4 text-sm text-gray-900"><?= htmlspecialchars($employee['username']) ?></td>
            <td class="px-6 py-4 text-sm">
              <?php
              $badges = [
                'admin' => 'bg-gray-200 text-gray-800',
                'guard' => 'bg-gray-200 text-gray-800',
                'super_admin' => 'bg-gray-300 text-gray-900',
                'owner' => 'bg-emerald-100 text-emerald-800'
              ];
              $badge = $badges[$employee['role']] ?? 'bg-gray-100 text-gray-800';
              ?>
              <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $badge ?>">
                <?= ucfirst(str_replace('_', ' ', htmlspecialchars($employee['role']))) ?>
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600"><?= date('M d, Y', strtotime($employee['created_at'])) ?></td>
            <td class="px-6 py-4">
              <div class="flex items-center justify-center gap-2">
                <button class="editEmployeeBtn inline-flex items-center px-3 py-1.5 bg-gray-700 text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors gap-1" data-id="<?= $employee['id'] ?>">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                  Edit
                </button>
                <button class="deleteEmployeeBtn inline-flex items-center px-3 py-1.5 bg-red-500 text-white text-sm font-medium rounded-md hover:bg-red-600 transition-colors gap-1" data-id="<?= $employee['id'] ?>" data-username="<?= htmlspecialchars($employee['username'], ENT_QUOTES) ?>">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                  Delete
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-600">Total Employees</p>
        <p class="text-3xl font-bold text-gray-900 mt-1"><?= $totalEmployees ?></p>
      </div>
      <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100">
        <svg class="h-6 w-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
      </div>
    </div>
  </div>
  
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-600">Admins</p>
        <p class="text-3xl font-bold text-gray-900 mt-1"><?= $roleCount['admin'] ?? 0 ?></p>
      </div>
      <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100">
        <svg class="h-6 w-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
        </svg>
      </div>
    </div>
  </div>
  
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-600">Guards</p>
        <p class="text-3xl font-bold text-gray-900 mt-1"><?= $roleCount['guard'] ?? 0 ?></p>
      </div>
      <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100">
        <svg class="h-6 w-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
        </svg>
      </div>
    </div>
  </div>
</div>
