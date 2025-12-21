<?php
/**
 * Simple QR Redirect Handler
 * This page accepts a token and redirects to the visitor pass view
 * No security headers, no HTTPS enforcement
 */

$token = $_GET['token'] ?? $_GET['t'] ?? '';

if (!$token) {
    die('Invalid QR code');
}

// Simple redirect to view_pass.php
$redirectUrl = "view_pass.php?token=" . urlencode($token);
header("Location: $redirectUrl");
exit();
