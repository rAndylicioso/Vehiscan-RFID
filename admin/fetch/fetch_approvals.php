<?php
// Fetch approvals page component
require_once __DIR__ . '/../../includes/session_admin_unified.php';

// Check authorization - allow both admin and super_admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    http_response_code(403);
    echo '<div class="p-6 text-center text-red-600">Unauthorized - Admin access required</div>';
    exit();
}

require_once __DIR__ . '/../components/approvals_page.php';
