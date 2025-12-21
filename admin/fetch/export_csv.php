<?php
// Security: Role-based access control
require_once __DIR__ . '/../../includes/session_admin_unified.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    http_response_code(403);
    header('Content-Type: text/plain');
    exit('Unauthorized access');
}

// require PDO connection (db.php)
require_once __DIR__ . '/../../db.php';

// Prevent any accidental output before headers
if (ob_get_length()) ob_end_clean();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="homeowners_export.csv"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Open output stream
$out = fopen('php://output', 'w');
if ($out === false) {
    http_response_code(500);
    exit("Unable to open output stream");
}

// Column headers
fputcsv($out, ['ID', 'Name', 'Plate', 'Vehicle', 'Contact', 'Address']);

// Fetch rows using PDO
try {
    $stmt = $pdo->query("SELECT id, name, plate_number, vehicle_type, contact, address FROM homeowners ORDER BY id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Ensure the order matches header
        $line = [
            $row['id'] ?? '',
            $row['name'] ?? '',
            $row['plate_number'] ?? '',
            $row['vehicle_type'] ?? '',
            $row['contact'] ?? '',
            $row['address'] ?? ''
        ];
        fputcsv($out, $line);
    }
} catch (Exception $e) {
    // write a tiny error row and exit
    fputcsv($out, ['error', $e->getMessage()]);
}

fclose($out);
exit;
