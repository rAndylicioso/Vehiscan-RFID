<?php
/**
 * Migration Runner - Executes all database migrations
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

// Security: Only allow from localhost during development
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    http_response_code(403);
    exit(json_encode(['error' => 'Access denied']));
}

$migrations = [
    '001_add_email_and_account_status.sql',
    '002_restructure_name_fields.sql',
    '003_add_contact_formatting.sql',
    '004_visitor_pass_subdivision_logo.sql',
    '005_multiple_vehicles_per_homeowner.sql'
];

$results = [];

foreach ($migrations as $migration) {
    $filePath = __DIR__ . '/' . $migration;
    
    if (!file_exists($filePath)) {
        $results[] = [
            'file' => $migration,
            'status' => 'error',
            'message' => 'Migration file not found'
        ];
        continue;
    }

    try {
        $sql = file_get_contents($filePath);
        
        // Split into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $pdo->beginTransaction();
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            $pdo->exec($statement);
        }
        
        $pdo->commit();
        
        $results[] = [
            'file' => $migration,
            'status' => 'success',
            'message' => 'Migration executed successfully'
        ];
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $results[] = [
            'file' => $migration,
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

echo json_encode([
    'success' => true,
    'migrations' => $results
]);
