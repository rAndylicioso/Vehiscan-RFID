<?php
/**
 * Helper functions for handling file uploads in VehiScan-RFID
 */

function validateAndUploadImage($file, $uploadDir, $prefix = '', $maxSize = 5242880) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'error' => 'Upload error occurred',
            'path' => null
        ];
    }

    // Validate file size
    if ($file['size'] > $maxSize) {
        return [
            'success' => false,
            'error' => 'File size exceeds limit (5MB)',
            'path' => null
        ];
    }

    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return [
            'success' => false,
            'error' => 'Invalid file type. Only JPG, PNG, and WEBP are allowed.',
            'path' => null
        ];
    }

    // Generate safe filename
    $ext = match ($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg'
    };

    $filename = $prefix . uniqid() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $filepath = $uploadDir . '/' . $filename;

    // Move file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => false,
            'error' => 'Failed to save file',
            'path' => null
        ];
    }

    return [
        'success' => true,
        'error' => null,
        'path' => $filename
    ];
}