<?php
/**
 * Input Validation and Sanitization Helpers
 * Location: includes/input_validation.php
 * 
 * Provides comprehensive input validation and sanitization functions
 * to protect against XSS, SQL injection, and other security vulnerabilities
 */

/**
 * Sanitize string input - removes HTML and scripts
 * @param string $input - The input to sanitize
 * @param bool $allowBasicHtml - Whether to allow basic HTML tags (b, i, u, em, strong)
 * @return string - Sanitized string
 */
function sanitizeString($input, $allowBasicHtml = false) {
    if ($allowBasicHtml) {
        // Allow only safe HTML tags
        $allowed = '<b><i><u><em><strong><p><br>';
        return strip_tags(trim($input), $allowed);
    }
    
    // Strip all HTML tags and trim whitespace
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Validate and sanitize email address
 * @param string $email - Email address to validate
 * @return array - ['valid' => bool, 'email' => string, 'error' => string]
 */
function validateEmail($email) {
    $email = trim($email);
    
    if (empty($email)) {
        return ['valid' => false, 'email' => '', 'error' => 'Email is required'];
    }
    
    // Basic format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'email' => $email, 'error' => 'Invalid email format'];
    }
    
    // Additional checks
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return ['valid' => false, 'email' => $email, 'error' => 'Invalid email format'];
    }
    
    // Check domain has valid TLD
    if (!preg_match('/\.[a-z]{2,}$/i', $parts[1])) {
        return ['valid' => false, 'email' => $email, 'error' => 'Invalid email domain'];
    }
    
    return ['valid' => true, 'email' => strtolower($email), 'error' => ''];
}

/**
 * Validate and sanitize username
 * @param string $username - Username to validate
 * @param int $minLength - Minimum length (default: 3)
 * @param int $maxLength - Maximum length (default: 50)
 * @return array - ['valid' => bool, 'username' => string, 'error' => string]
 */
function validateUsername($username, $minLength = 3, $maxLength = 50) {
    $username = trim($username);
    
    if (empty($username)) {
        return ['valid' => false, 'username' => '', 'error' => 'Username is required'];
    }
    
    if (strlen($username) < $minLength) {
        return ['valid' => false, 'username' => $username, 'error' => "Username must be at least $minLength characters"];
    }
    
    if (strlen($username) > $maxLength) {
        return ['valid' => false, 'username' => $username, 'error' => "Username cannot exceed $maxLength characters"];
    }
    
    // Only allow alphanumeric, underscore, hyphen, and period
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
        return ['valid' => false, 'username' => $username, 'error' => 'Username can only contain letters, numbers, dots, hyphens, and underscores'];
    }
    
    return ['valid' => true, 'username' => $username, 'error' => ''];
}

/**
 * Validate password strength
 * @param string $password - Password to validate
 * @param int $minLength - Minimum length (default: 8)
 * @param bool $requireComplex - Require uppercase, lowercase, number, and special char
 * @return array - ['valid' => bool, 'strength' => string, 'error' => string]
 */
function validatePassword($password, $minLength = 8, $requireComplex = false) {
    if (empty($password)) {
        return ['valid' => false, 'strength' => 'none', 'error' => 'Password is required'];
    }
    
    if (strlen($password) < $minLength) {
        return ['valid' => false, 'strength' => 'weak', 'error' => "Password must be at least $minLength characters"];
    }
    
    if ($requireComplex) {
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasLower = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);
        
        if (!$hasUpper) {
            return ['valid' => false, 'strength' => 'weak', 'error' => 'Password must contain at least one uppercase letter'];
        }
        if (!$hasLower) {
            return ['valid' => false, 'strength' => 'weak', 'error' => 'Password must contain at least one lowercase letter'];
        }
        if (!$hasNumber) {
            return ['valid' => false, 'strength' => 'weak', 'error' => 'Password must contain at least one number'];
        }
        if (!$hasSpecial) {
            return ['valid' => false, 'strength' => 'weak', 'error' => 'Password must contain at least one special character'];
        }
    }
    
    // Determine password strength
    $strength = 'medium';
    if (strlen($password) >= 12 && preg_match('/[A-Z]/', $password) && 
        preg_match('/[a-z]/', $password) && preg_match('/[0-9]/', $password) && 
        preg_match('/[^A-Za-z0-9]/', $password)) {
        $strength = 'strong';
    }
    
    return ['valid' => true, 'strength' => $strength, 'error' => ''];
}

/**
 * Validate and sanitize phone number
 * @param string $phone - Phone number to validate
 * @return array - ['valid' => bool, 'phone' => string, 'error' => string]
 */
function validatePhone($phone) {
    // Remove all non-numeric characters except + and spaces
    $cleaned = preg_replace('/[^0-9+\s-]/', '', $phone);
    $cleaned = trim($cleaned);
    
    if (empty($cleaned)) {
        return ['valid' => false, 'phone' => '', 'error' => 'Phone number is required'];
    }
    
    // Check if it has at least 10 digits
    $digits = preg_replace('/[^0-9]/', '', $cleaned);
    if (strlen($digits) < 10) {
        return ['valid' => false, 'phone' => $cleaned, 'error' => 'Phone number must have at least 10 digits'];
    }
    
    return ['valid' => true, 'phone' => $cleaned, 'error' => ''];
}

/**
 * Validate and sanitize plate number
 * @param string $plate - Plate number to validate
 * @return array - ['valid' => bool, 'plate' => string, 'error' => string]
 */
function validatePlateNumber($plate) {
    $plate = strtoupper(trim($plate));
    
    if (empty($plate)) {
        return ['valid' => false, 'plate' => '', 'error' => 'Plate number is required'];
    }
    
    // Remove all spaces for consistency
    $plate = preg_replace('/\s+/', '', $plate);
    
    // Allow only alphanumeric characters and hyphens
    if (!preg_match('/^[A-Z0-9-]+$/', $plate)) {
        return ['valid' => false, 'plate' => $plate, 'error' => 'Plate number can only contain letters, numbers, and hyphens'];
    }
    
    if (strlen($plate) < 3) {
        return ['valid' => false, 'plate' => $plate, 'error' => 'Plate number must be at least 3 characters'];
    }
    
    if (strlen($plate) > 15) {
        return ['valid' => false, 'plate' => $plate, 'error' => 'Plate number cannot exceed 15 characters'];
    }
    
    return ['valid' => true, 'plate' => $plate, 'error' => ''];
}

/**
 * Validate integer input
 * @param mixed $input - Input to validate
 * @param int $min - Minimum value (optional)
 * @param int $max - Maximum value (optional)
 * @return array - ['valid' => bool, 'value' => int, 'error' => string]
 */
function validateInteger($input, $min = null, $max = null) {
    if ($input === '' || $input === null) {
        return ['valid' => false, 'value' => 0, 'error' => 'Value is required'];
    }
    
    if (!is_numeric($input)) {
        return ['valid' => false, 'value' => 0, 'error' => 'Value must be a number'];
    }
    
    $value = (int)$input;
    
    if ($min !== null && $value < $min) {
        return ['valid' => false, 'value' => $value, 'error' => "Value must be at least $min"];
    }
    
    if ($max !== null && $value > $max) {
        return ['valid' => false, 'value' => $value, 'error' => "Value cannot exceed $max"];
    }
    
    return ['valid' => true, 'value' => $value, 'error' => ''];
}

/**
 * Validate date input
 * @param string $date - Date string to validate
 * @param string $format - Expected date format (default: Y-m-d)
 * @return array - ['valid' => bool, 'date' => string, 'error' => string]
 */
function validateDate($date, $format = 'Y-m-d') {
    if (empty($date)) {
        return ['valid' => false, 'date' => '', 'error' => 'Date is required'];
    }
    
    $d = DateTime::createFromFormat($format, $date);
    
    if (!$d || $d->format($format) !== $date) {
        return ['valid' => false, 'date' => $date, 'error' => 'Invalid date format'];
    }
    
    return ['valid' => true, 'date' => $date, 'error' => ''];
}

/**
 * Validate file upload
 * @param array $file - $_FILES array element
 * @param array $allowedTypes - Allowed MIME types
 * @param int $maxSize - Max file size in bytes (default: 4MB)
 * @return array - ['valid' => bool, 'file' => array, 'error' => string]
 */
function validateFileUpload($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'], $maxSize = 4194304) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['valid' => false, 'file' => null, 'error' => 'Invalid file upload'];
    }
    
    // Check for upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['valid' => false, 'file' => null, 'error' => 'No file uploaded'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['valid' => false, 'file' => null, 'error' => 'File exceeds size limit'];
        default:
            return ['valid' => false, 'file' => null, 'error' => 'Unknown upload error'];
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        $maxMB = round($maxSize / 1048576, 2);
        return ['valid' => false, 'file' => null, 'error' => "File size exceeds {$maxMB}MB limit"];
    }
    
    // Validate MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['valid' => false, 'file' => null, 'error' => 'Invalid file type'];
    }
    
    return ['valid' => true, 'file' => $file, 'error' => ''];
}

/**
 * Sanitize array input - recursively sanitize all values
 * @param array $array - Array to sanitize
 * @return array - Sanitized array
 */
function sanitizeArray($array) {
    $sanitized = [];
    
    foreach ($array as $key => $value) {
        $sanitizedKey = sanitizeString($key);
        
        if (is_array($value)) {
            $sanitized[$sanitizedKey] = sanitizeArray($value);
        } else {
            $sanitized[$sanitizedKey] = sanitizeString($value);
        }
    }
    
    return $sanitized;
}

/**
 * Validate URL
 * @param string $url - URL to validate
 * @return array - ['valid' => bool, 'url' => string, 'error' => string]
 */
function validateUrl($url) {
    $url = trim($url);
    
    if (empty($url)) {
        return ['valid' => false, 'url' => '', 'error' => 'URL is required'];
    }
    
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['valid' => false, 'url' => $url, 'error' => 'Invalid URL format'];
    }
    
    // Check for allowed protocols
    $parsed = parse_url($url);
    if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
        return ['valid' => false, 'url' => $url, 'error' => 'Only HTTP and HTTPS URLs are allowed'];
    }
    
    return ['valid' => true, 'url' => $url, 'error' => ''];
}
?>
