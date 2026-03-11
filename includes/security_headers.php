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

// Content Security Policy — mitigate XSS, data injection, click-jacking
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self'; connect-src 'self'; frame-ancestors 'self';");

// Control referrer information
header('Referrer-Policy: strict-origin-when-cross-origin');

// Permissions policy - restrict browser features
header('Permissions-Policy: camera=(self), microphone=(), geolocation=()');

// HSTS - enforce HTTPS when deployed over TLS
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
if ($isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Cache control for authenticated pages
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['username'])) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}
?>