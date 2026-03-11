<?php
/**
 * Simple QR Redirect Handler
 * This page accepts a token and redirects to the visitor pass view
 * No security headers, no HTTPS enforcement
 */

$token = $_GET['token'] ?? $_GET['t'] ?? '';

if (!$token) {
    http_response_code(400);
    echo '<!DOCTYPE html><html><head><title>Invalid QR Code</title></head><body><h1>Invalid QR Code</h1><p>The QR code is invalid or missing. Please try scanning again.</p></body></html>';
    exit;
}

// Simple redirect to view_pass.php
$redirectUrl = "view_pass.php?token=" . urlencode($token);
header("Location: $redirectUrl");
exit();
