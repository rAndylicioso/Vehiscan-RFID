<?php
// admin/fetch/export_logs_csv.php
require_once __DIR__ . '/../../includes/session_admin_unified.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    http_response_code(403);
    header('Content-Type: text/plain');
    exit('Unauthorized access');
}

require_once __DIR__ . '/../../db.php';

// Prevent any accidental output before headers
if (ob_get_length()) ob_end_clean();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="access_logs_export.csv"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Open output stream
$out = fopen('php://output', 'w');
if ($out === false) {
    http_response_code(500);
    exit("Unable to open output stream");
}

// Column headers
fputcsv($out, ['Log ID', 'Date/Time', 'Plate Number', 'Status', 'Owner Name', 'Vehicle Type']);

// Fetch rows using PDO
try {
    $stmt = $pdo->query("
        SELECT r.log_id, r.created_at, r.plate_number, r.status, h.name, h.vehicle_type
        FROM recent_logs r
        LEFT JOIN homeowners h ON r.plate_number = h.plate_number
        ORDER BY r.created_at DESC, r.log_id DESC
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $line = [
            $row['log_id'] ?? '',
            $row['created_at'] ?? '',
            $row['plate_number'] ?? '',
            $row['status'] ?? '',
            $row['name'] ?? 'Unknown',
            $row['vehicle_type'] ?? '-'
        ];
        fputcsv($out, $line);
    }
} catch (Exception $e) {
    // write error row
    fputcsv($out, ['error', $e->getMessage()]);
}

fclose($out);
exit;
