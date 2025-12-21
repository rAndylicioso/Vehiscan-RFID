<?php
/**
 * Configuration Loader
 * Loads environment variables from .env file if it exists
 * Falls back to default values for development
 */

// Load .env file if it exists
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Set as environment variable
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Initialize error handler
require_once __DIR__ . '/includes/error_handler.php';

// Helper function to get config values with defaults
function config($key, $default = null)
{
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

// Helper function to get application URL
function getAppUrl()
{
    // Check if already set in environment
    $envUrl = config('APP_URL', null);
    if ($envUrl) {
        return rtrim($envUrl, '/');
    }

    // Auto-detect from server variables
    if (php_sapi_name() === 'cli') {
        // CLI mode - return localhost default
        return 'http://localhost/Vehiscan-RFID';
    }

    // Web mode - detect from request
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';

    // Extract base path by removing file and subdirectories
    $basePath = dirname($script);
    $basePath = preg_replace('#/(admin|guard|visitor|homeowners|auth|api|pages|utilities|includes).*$#', '', $basePath);
    $basePath = rtrim($basePath, '/');

    return "$protocol://$host$basePath";
}

// Database configuration
define('DB_HOST', config('DB_HOST', 'localhost'));
define('DB_NAME', config('DB_NAME', 'vehiscan_vdp'));
define('DB_USER', config('DB_USER', 'root'));
define('DB_PASS', config('DB_PASS', ''));
define('DB_CHARSET', config('DB_CHARSET', 'utf8mb4'));

// Application settings
define('APP_ENV', config('APP_ENV', 'development'));
define('APP_DEBUG', config('APP_DEBUG', 'false') === 'true');

// Session settings
define('SESSION_LIFETIME', (int) config('SESSION_LIFETIME', 3600));
define('SESSION_SECURE', config('SESSION_SECURE', 'false') === 'true');
define('SESSION_HTTPONLY', config('SESSION_HTTPONLY', 'true') === 'true');

// Security settings
define('CSRF_TOKEN_LENGTH', (int) config('CSRF_TOKEN_LENGTH', 32));
define('PASSWORD_MIN_LENGTH', (int) config('PASSWORD_MIN_LENGTH', 12));
define('MAX_LOGIN_ATTEMPTS', (int) config('MAX_LOGIN_ATTEMPTS', 5));
define('LOGIN_LOCKOUT_MINUTES', (int) config('LOGIN_LOCKOUT_MINUTES', 15));

// Upload settings
define('MAX_FILE_SIZE', (int) config('MAX_FILE_SIZE', 5242880)); // 5MB
define('ALLOWED_IMAGE_TYPES', config('ALLOWED_IMAGE_TYPES', 'image/jpeg,image/png,image/jpg'));

// QR Code settings
define('QR_CODE_SIZE', (int) config('QR_CODE_SIZE', 10));
define('QR_CODE_ERROR_CORRECTION', config('QR_CODE_ERROR_CORRECTION', 'L'));

/**
 * Get WiFi-aware URL for QR codes
 * Returns local WiFi IP if accessed from same network, otherwise returns hosting domain
 */
function getQrCodeUrl()
{
    // Check for forced QR URL in environment
    $envQrUrl = config('QR_BASE_URL', null);
    if ($envQrUrl) {
        return rtrim($envQrUrl, '/');
    }

    // Detect if request is from local network
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
    $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
    $httpHost = $_SERVER['HTTP_HOST'] ?? '';

    // Check if accessed via local IP or local network
    $isLocalNetwork = (
        preg_match('/^192\.168\./', $remoteAddr) ||
        preg_match('/^10\./', $remoteAddr) ||
        preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $remoteAddr) ||
        $remoteAddr === '127.0.0.1' ||
        $remoteAddr === '::1'
    );

    $isLocalHost = (
        preg_match('/^192\.168\./', $httpHost) ||
        preg_match('/^10\./', $httpHost) ||
        strpos($httpHost, 'localhost') === 0 ||
        strpos($httpHost, '127.0.0.1') === 0
    );

    // If accessed from local network, use WiFi IP
    if ($isLocalNetwork || $isLocalHost) {
        $protocol = 'http'; // Always use HTTP for local network

        // Try to get WiFi IP from config
        $wifiIp = config('WIFI_IP', null);

        if (!$wifiIp) {
            // Auto-detect: prioritize actual IP over localhost
            if (preg_match('/^\d+\.\d+\.\d+\.\d+/', $httpHost) && !preg_match('/^127\./', $httpHost)) {
                // HTTP_HOST is a valid IP (not 127.x.x.x)
                $wifiIp = explode(':', $httpHost)[0]; // Remove port if present
            } elseif ($serverAddr && !preg_match('/^127\./', $serverAddr)) {
                // SERVER_ADDR is available and not localhost
                $wifiIp = $serverAddr;
            } else {
                // Fallback: default to common WiFi IP
                $wifiIp = $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? 'localhost';
            }
        }

        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname($script);
        $basePath = preg_replace('#/(admin|guard|visitor|homeowners|auth|api|pages|utilities|includes).*$#', '', $basePath);
        $basePath = rtrim($basePath, '/');

        return "$protocol://$wifiIp$basePath";
    }

    // Otherwise, use the regular APP_URL (for hosting/internet access)
    return getAppUrl();
}
