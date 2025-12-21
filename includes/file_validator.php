<?php
/**
 * Secure File Upload Validator
 * Validates file uploads with magic byte checking and size limits
 * Prevents malicious file uploads and bypasses
 */

class FileValidator {
    // Maximum file size in bytes (5MB default)
    const MAX_FILE_SIZE = 5 * 1024 * 1024;
    
    // Allowed MIME types
    const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
        'image/heic'
    ];
    
    // Magic bytes (file signatures) for allowed file types
    const MAGIC_BYTES = [
        'image/jpeg' => [
            [0xFF, 0xD8, 0xFF, 0xE0], // JPEG (JFIF)
            [0xFF, 0xD8, 0xFF, 0xE1], // JPEG (Exif)
            [0xFF, 0xD8, 0xFF, 0xE2], // JPEG (Canon)
            [0xFF, 0xD8, 0xFF, 0xE3]  // JPEG (Samsung)
        ],
        'image/png' => [
            [0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A] // PNG
        ],
        'image/webp' => [
            [0x52, 0x49, 0x46, 0x46] // WEBP (RIFF)
        ],
        'image/heic' => [
            [0x00, 0x00, 0x00, 0x18, 0x66, 0x74, 0x79, 0x70] // HEIC
        ]
    ];
    
    /**
     * Validate uploaded file
     * @param array $file - $_FILES array element
     * @return array - ['valid' => bool, 'error' => string|null, 'safe_name' => string|null]
     */
    public static function validate($file) {
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'No file uploaded'];
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => self::getUploadError($file['error'])];
        }
        
        // Check file size
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $maxMB = round(self::MAX_FILE_SIZE / 1024 / 1024, 1);
            return ['valid' => false, 'error' => "File size exceeds {$maxMB}MB limit"];
        }
        
        // Check if file is actually uploaded (prevents local file inclusion)
        if (!is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'Invalid file upload'];
        }
        
        // Get MIME type from file content (not from user input)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        // Check if MIME type is allowed
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES)) {
            return ['valid' => false, 'error' => 'File type not allowed. Only JPG, PNG, WEBP, HEIC images are accepted'];
        }
        
        // Validate magic bytes (file signature)
        if (!self::validateMagicBytes($file['tmp_name'], $mimeType)) {
            return ['valid' => false, 'error' => 'File content does not match file type. Possible malicious file'];
        }
        
        // Generate safe filename
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $safeName = date('Ymd_His_') . bin2hex(random_bytes(8)) . '.' . $extension;
        
        return [
            'valid' => true,
            'error' => null,
            'safe_name' => $safeName,
            'mime_type' => $mimeType,
            'size' => $file['size']
        ];
    }
    
    /**
     * Validate magic bytes of file
     * @param string $filePath
     * @param string $mimeType
     * @return bool
     */
    private static function validateMagicBytes($filePath, $mimeType) {
        if (!isset(self::MAGIC_BYTES[$mimeType])) {
            return false;
        }
        
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }
        
        // Read first 16 bytes
        $fileHeader = fread($handle, 16);
        fclose($handle);
        
        if ($fileHeader === false) {
            return false;
        }
        
        $headerBytes = array_values(unpack('C*', $fileHeader));
        
        // Check against all known magic bytes for this MIME type
        foreach (self::MAGIC_BYTES[$mimeType] as $signature) {
            $match = true;
            foreach ($signature as $index => $byte) {
                if (!isset($headerBytes[$index]) || $headerBytes[$index] !== $byte) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get human-readable upload error message
     * @param int $errorCode
     * @return string
     */
    private static function getUploadError($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds MAX_FILE_SIZE directive in HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'File was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'File upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
    
    /**
     * Sanitize filename to prevent directory traversal
     * @param string $filename
     * @return string
     */
    public static function sanitizeFilename($filename) {
        // Remove any path components
        $filename = basename($filename);
        
        // Remove special characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Prevent double extensions
        $filename = preg_replace('/\.{2,}/', '.', $filename);
        
        return $filename;
    }
}
