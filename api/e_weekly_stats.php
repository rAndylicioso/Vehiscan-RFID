<?php
/**
 * Weekly Stats API Endpoint (Stub)
 * This file was missing and causing console errors.
 * Returns empty data structure to prevent JavaScript errors.
 */

header('Content-Type: application/json');

// Return empty but valid JSON response
echo json_encode([
    'success' => true,
    'data' => [],
    'message' => 'Weekly stats endpoint - currently not implemented'
]);
