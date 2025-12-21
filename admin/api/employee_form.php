<?php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';

$id = InputSanitizer::get('id', 'int');
$employee = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
}

$isEdit = !empty($employee);
?>

<div class="p-6">
    <h3 class="text-2xl font-bold text-gray-900 mb-6"><?= $isEdit ? 'Edit Employee' : 'Create New Employee' ?></h3>

    <form id="employeeForm" class="space-y-6" action="/Vehiscan-RFID/admin/api/employee_save.php" method="POST">
        <input type="hidden" name="id" value="<?= $employee['id'] ?? '' ?>">
        
        <!-- Username -->
        <div>
            <label for="employee_username" class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
            <input type="text" 
                   id="employee_username"
                   name="username" 
                   value="<?= htmlspecialchars($employee['username'] ?? '') ?>" 
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                   placeholder="Enter username (min 3 characters)"
                   autocomplete="username"
                   required <?= $isEdit ? 'readonly' : '' ?>>
            <?php if ($isEdit): ?>
            <p class="text-sm text-gray-500 mt-1">Username cannot be changed</p>
            <?php endif; ?>
        </div>
        
        <!-- Role Selection -->
        <div>
            <label for="employee_role" class="block text-sm font-semibold text-gray-700 mb-2">Role</label>
            <select id="employee_role" name="role" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" autocomplete="off" required>
                <option value="">Select role...</option>
                <option value="admin" <?= ($employee['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="guard" <?= ($employee['role'] ?? '') === 'guard' ? 'selected' : '' ?>>Guard</option>
                <option value="owner" <?= ($employee['role'] ?? '') === 'owner' ? 'selected' : '' ?>>Homeowner</option>
            </select>
            <p class="text-sm text-gray-500 mt-2">
                <strong>Admin:</strong> Full access • <strong>Guard:</strong> Guard panel • <strong>Homeowner:</strong> Registration only
            </p>
        </div>
        
        <?php if (!$isEdit): ?>
        <!-- Password (Create Only) -->
        <div>
            <label for="employee_password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
            <input type="password" 
                   id="employee_password"
                   name="password" 
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                   placeholder="Enter password (min 8 characters)"
                   autocomplete="new-password"
                   required minlength="8">
        </div>
        
        <div>
            <label for="employee_confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">Confirm Password</label>
            <input type="password" 
                   id="employee_confirm_password"
                   name="confirm_password" 
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                   placeholder="Confirm password"
                   autocomplete="new-password"
                   required minlength="8">
        </div>
        
        <?php else: ?>
        <!-- Password Reset Section (Edit Only) -->
        <div class="p-6 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-start mb-4">
                <input type="checkbox" id="reset_password" name="reset_password" class="mt-1 mr-3 w-4 h-4 text-blue-600">
                <div>
                    <label for="reset_password" class="block text-sm font-semibold text-gray-900 cursor-pointer">Reset Password</label>
                    <p class="text-sm text-gray-600">Check this box to set a new password for this employee</p>
                </div>
            </div>
            <div id="passwordField" class="hidden space-y-4">
                <div>
                    <label for="new_password" class="block text-sm font-semibold text-gray-700 mb-2">New Password</label>
                    <input type="password" id="new_password" name="new_password" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                           placeholder="Enter new password (min 8 characters)"
                           minlength="8">
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Submit Buttons -->
        <div class="flex items-center justify-end space-x-4 pt-4">
            <button type="button" onclick="closeModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                Cancel
            </button>
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-indigo-600 text-white rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all shadow-lg font-medium">
                <?= $isEdit ? 'Update Employee' : 'Create Employee' ?>
            </button>
        </div>
    </form>
</div>

<script>
// Password reset toggle for edit mode
<?php if ($isEdit): ?>
setTimeout(() => {
    const resetCheckbox = document.getElementById('reset_password');
    const passwordField = document.getElementById('passwordField');
    const newPasswordInput = document.getElementById('new_password');
    
    if (resetCheckbox && passwordField && newPasswordInput) {
        resetCheckbox.addEventListener('change', function() {
            passwordField.classList.toggle('hidden', !this.checked);
            newPasswordInput.required = this.checked;
        });
    }
}, 100);
<?php endif; ?>
</script>
