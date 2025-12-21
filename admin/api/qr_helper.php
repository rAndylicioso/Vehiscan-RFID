<?php
// Generate QR code for visitor pass
require_once __DIR__ . '/../../phpqrcode/qrlib.php';

function generateVisitorPassQR($passId, $token, $pdo)
{
    try {
        // Create QR code directory if it doesn't exist
        $qrDir = __DIR__ . '/../../uploads/qr_codes';
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0755, true);
        }

        // Generate verification URL using WiFi-aware detection
        if (!function_exists('getQrCodeUrl')) {
            require_once __DIR__ . '/../../config.php';
        }

        $baseUrl = getQrCodeUrl(); // Use WiFi-aware URL
        $verifyUrl = "$baseUrl/visitor/scan.php?token=$token";

        error_log("[QR] Generated URL: $verifyUrl (from: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ")");

        // Generate QR code with logo overlay
        $tempFile = $qrDir . "/pass_$passId.png";
        $finalFile = $qrDir . "/pass_{$passId}_final.png";

        // Generate base QR code
        QRcode::png($verifyUrl, $tempFile, QR_ECLEVEL_H, 8, 2); // Higher error correction for logo

        // Add logo overlay
        $logoPath = __DIR__ . '/../../ville_de_palme.png';
        if (file_exists($logoPath)) {
            addLogoToQR($tempFile, $logoPath, $finalFile);
            // Use the final file with logo
            $imageData = file_get_contents($finalFile);
            unlink($tempFile);
            unlink($finalFile);
        } else {
            // No logo, use plain QR
            $imageData = file_get_contents($tempFile);
            unlink($tempFile);
        }

        // Convert to base64
        $base64 = base64_encode($imageData);
        $qrCodeData = 'data:image/png;base64,' . $base64;

        return $qrCodeData;

    } catch (Exception $e) {
        error_log("QR generation error: " . $e->getMessage());
        return null;
    }
}

function addLogoToQR($qrPath, $logoPath, $outputPath)
{
    // Load images
    $qr = imagecreatefrompng($qrPath);
    $logo = imagecreatefrompng($logoPath);

    if (!$qr || !$logo) {
        copy($qrPath, $outputPath);
        return;
    }

    // Get dimensions
    $qrWidth = imagesx($qr);
    $qrHeight = imagesy($qr);
    $logoWidth = imagesx($logo);
    $logoHeight = imagesy($logo);

    // Calculate logo size (15% of QR code)
    $logoQrWidth = $qrWidth / 6;
    $logoQrHeight = $logoHeight * ($logoQrWidth / $logoWidth);

    // Create white background for logo
    $logoX = ($qrWidth - $logoQrWidth) / 2;
    $logoY = ($qrHeight - $logoQrHeight) / 2;
    $whiteBg = imagecolorallocate($qr, 255, 255, 255);
    imagefilledrectangle($qr, $logoX - 5, $logoY - 5, $logoX + $logoQrWidth + 5, $logoY + $logoQrHeight + 5, $whiteBg);

    // Copy logo onto QR code
    imagecopyresampled($qr, $logo, $logoX, $logoY, 0, 0, $logoQrWidth, $logoQrHeight, $logoWidth, $logoHeight);

    // Save
    imagepng($qr, $outputPath);

    // Free memory
    imagedestroy($qr);
    imagedestroy($logo);
}

function generateSecureToken()
{
    return bin2hex(random_bytes(32));
}
