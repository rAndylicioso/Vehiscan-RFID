<?php
/**
 * Security Headers
 * Sets HTTP security headers to protect against common web vulnerabilities
 */

// Prevent clickjacking
header('X-Frame-Options: SAMEORIGIN');

// Prevent MIME type sniffing
header('X-Content-Type-Options: nosniff');

// Enable XSS protection
header('X-XSS-Protection: 1; mode=block');

// Control referrer information
header('Referrer-Policy: strict-origin-when-cross-origin');

// Permissions policy - restrict browser features
header('Permissions-Policy: camera=(self), microphone=(), geolocation=()');

// Cache control for authenticated pages
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['username'])) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}
?>