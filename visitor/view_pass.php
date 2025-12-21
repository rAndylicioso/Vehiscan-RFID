<?php
// Don't include security_headers.php to avoid HTTPS redirect on local network
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors to user, log them instead

// Direct database connection without config dependencies
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=vehiscan_vdp;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log("[VISITOR_PASS] Database connection failed: " . $e->getMessage());
    $error = 'Database Connection Failed';
    $pdo = null;
}

$token = $_GET['token'] ?? '';
$error = $error ?? null;
$pass = null;
$statusDisplay = '';
$statusClass = '';
$statusIcon = '';

if (!$pdo) {
    $error = 'Database Connection Failed';
} elseif (!$token) {
    $error = 'Invalid Request';
    error_log("[VISITOR_PASS] No token provided");
} else {
    try {
        error_log("[VISITOR_PASS] Looking for token: $token");
        
        // First get the visitor pass
        $stmt = $pdo->prepare("SELECT * FROM visitor_passes WHERE qr_token = ?");
        $stmt->execute([$token]);
        $pass = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pass) {
            $error = 'Invalid or Expired Visitor Pass';
            error_log("[VISITOR_PASS] No pass found for token: $token");
        } else {
            error_log("[VISITOR_PASS] Pass found - ID: {$pass['id']}, Status: " . ($pass['status'] ?? 'NULL'));
            
            // Try to get homeowner info (optional)
            $homeowner_name = 'Guest';
            $homeowner_address = '';
            $contact_number = '';
            
            if (!empty($pass['homeowner_id'])) {
                $stmt2 = $pdo->prepare("SELECT name, address, contact_number FROM homeowners WHERE id = ?");
                $stmt2->execute([$pass['homeowner_id']]);
                $homeowner = $stmt2->fetch(PDO::FETCH_ASSOC);
                
                if ($homeowner) {
                    $homeowner_name = $homeowner['name'];
                    $homeowner_address = $homeowner['address'] ?? '';
                    $contact_number = $homeowner['contact_number'] ?? '';
                    error_log("[VISITOR_PASS] Homeowner found: $homeowner_name");
                } else {
                    error_log("[VISITOR_PASS] Warning: Homeowner ID {$pass['homeowner_id']} not found");
                }
            }
            
            // Add homeowner info to pass array
            $pass['homeowner_name'] = $homeowner_name;
            $pass['homeowner_address'] = $homeowner_address;
            $pass['contact'] = $contact_number;
            
            // Determine pass status based on database status and time validity
            $now = new DateTime();
            $validFrom = new DateTime($pass['valid_from']);
            $validUntil = new DateTime($pass['valid_until']);
            
            // Check actual status from database
            $dbStatus = strtolower($pass['status'] ?? 'pending');
            
            // Priority order: cancelled/rejected > pending > time-based validation
            if ($dbStatus === 'rejected' || $dbStatus === 'cancelled') {
                $statusDisplay = 'Cancelled';
                $statusClass = 'status-cancelled';
                $statusIcon = '✕';
            } elseif ($dbStatus === 'pending') {
                $statusDisplay = 'Pending Approval';
                $statusClass = 'status-pending';
                $statusIcon = '⏳';
            } elseif ($now > $validUntil) {
                // Pass has expired (current time is after valid_until)
                $statusDisplay = 'Expired';
                $statusClass = 'status-expired';
                $statusIcon = '✕';
            } elseif ($now < $validFrom) {
                // Pass is not yet valid (current time is before valid_from)
                $statusDisplay = 'Not Yet Valid';
                $statusClass = 'status-pending';
                $statusIcon = '⏱';
            } elseif ($dbStatus === 'active' || $dbStatus === 'approved') {
                // Pass is active and within valid time range
                $statusDisplay = 'Active';
                $statusClass = 'status-valid';
                $statusIcon = '✓';
            } else {
                $statusDisplay = 'Unknown Status';
                $statusClass = 'status-pending';
                $statusIcon = '?';
            }
            
            error_log("[VISITOR_PASS] DB Status: $dbStatus, Display: $statusDisplay, Now: " . $now->format('Y-m-d H:i:s') . ", Valid: " . $validFrom->format('Y-m-d H:i:s') . " to " . $validUntil->format('Y-m-d H:i:s'));
        }
    } catch (PDOException $e) {
        error_log("[VISITOR_PASS] Database error: " . $e->getMessage() . " | Token: $token");
        error_log("[VISITOR_PASS] Stack trace: " . $e->getTraceAsString());
        $error = 'Database Query Error';
    } catch (Exception $e) {
        error_log("[VISITOR_PASS] General error: " . $e->getMessage() . " | Token: $token");
        $error = 'System Error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Visitor Pass — VehiScan</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
        }
        
        .pass-container {
            position: relative;
            z-index: 10;
            max-width: 450px;
            width: 100%;
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .pass-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid #e5e7eb;
        }
        
        .pass-header {
            background: #1e293b;
            padding: 2rem 1.5rem;
            text-align: center;
            position: relative;
            border-bottom: 3px solid #0ea5e9;
        }
        .logo-circle {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 10;
            padding: 8px;
        }
        
        .logo-circle img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1.25rem;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-top: 1rem;
            position: relative;
            z-index: 10;
        }
        
        .status-valid {
            background: #10b981;
            color: white;
        }
        
        .status-expired {
            background: #ef4444;
            color: white;
        }
        
        .status-cancelled {
            background: #6b7280;
            color: white;
        }
        
        .status-pending {
            background: #f59e0b;
            color: white;
        }
        
        .pass-body {
            padding: 1.25rem 1.25rem;
            background: white;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            margin-bottom: 0.75rem;
            gap: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-row:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            color: #6b7280;
            font-size: 0.8125rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            flex-shrink: 0;
        }
        
        .info-value {
            color: #111827;
            font-size: 0.9375rem;
            font-weight: 600;
            text-align: right;
            word-wrap: break-word;
        }
        
        .qr-section {
            text-align: center;
            padding: 2rem 1.5rem;
            background: #fafafa;
            border-top: 1px solid #e5e7eb;
        }
        
        .qr-code {
            background: white;
            padding: 1.25rem;
            border-radius: 8px;
            display: inline-block;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }
        
        .qr-code:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-color: #0ea5e9;
        }
        
        .qr-code img {
            display: block;
            width: 180px;
            height: 180px;
            margin: 0 auto;
            border-radius: 4px;
        }
        
        .qr-label {
            margin-bottom: 1rem;
            color: #6b7280;
            font-size: 0.8125rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .error-container {
            text-align: center;
            padding: 2rem;
        }
        
        .error-icon {
            width: 80px;
            height: 80px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        .error-icon svg {
            width: 40px;
            height: 40px;
            color: #dc2626;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 480px) {
            body {
                padding: 0.5rem;
            }
            
            .pass-header {
                padding: 1.5rem 1rem;
            }
            
            .pass-body {
                padding: 1.25rem;
            }
            
            .info-row {
                flex-direction: column;
                gap: 0.5rem;
                padding: 0.875rem 0;
            }
            
            .info-value {
                text-align: left;
            }
            
            .qr-section {
                padding: 1.5rem 1rem;
            }
            
            .qr-code img {
                width: 160px;
                height: 160px;
            }
            
            .logo-circle {
                width: 70px;
                height: 70px;
            }
            
            .logo-circle img {
                width: 100%;
                height: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="pass-container">
        <?php if ($error): ?>
            <!-- Error State -->
            <div class="pass-card">
                <div class="error-container">
                    <div class="error-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h1 style="font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem;"><?= htmlspecialchars($error) ?></h1>
                    <p style="color: #6b7280; font-size: 0.875rem;">The QR code may be invalid, expired, or not yet approved by the administrator.</p>
                    <p style="color: #9ca3af; font-size: 0.75rem; margin-top: 1rem;">If you believe this is an error, please contact the subdivision office.</p>
                </div>
            </div>
        <?php else: ?>
            <!-- Visitor Pass Display -->
            <div class="pass-card">
                <!-- Header -->
                <div class="pass-header">
                    <div class="logo-circle">
                        <img src="../ville_de_palme.png" alt="Ville de Palme Logo">
                    </div>
                    <h1 style="color: white; font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem; letter-spacing: 0.025em;">VISITOR PASS</h1>
                    <p style="color: rgba(255,255,255,0.9); font-size: 0.875rem; font-weight: 400;">VehiScan Security System</p>
                    <div class="status-badge <?= $statusClass ?>">
                        <span style="font-size: 1rem;"><?= $statusIcon ?></span>
                        <span><?= $statusDisplay ?></span>
                    </div>
                </div>
                
                <!-- Pass Information -->
                <div class="pass-body">
                    <div class="info-row">
                        <span class="info-label">Visitor Name</span>
                        <span class="info-value"><?= htmlspecialchars($pass['visitor_name']) ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Purpose</span>
                        <span class="info-value"><?= htmlspecialchars($pass['purpose']) ?></span>
                    </div>
                    
                    <?php if (!empty($pass['visitor_plate'])): ?>
                    <div class="info-row">
                        <span class="info-label">Vehicle Plate</span>
                        <span class="info-value" style="font-family: monospace; font-size: 1.125rem;"><?= htmlspecialchars($pass['visitor_plate']) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="info-row">
                        <span class="info-label">Host</span>
                        <span class="info-value"><?= htmlspecialchars($pass['homeowner_name']) ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Address</span>
                        <span class="info-value"><?= htmlspecialchars($pass['homeowner_address']) ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Valid From</span>
                        <span class="info-value"><?= date('M d, Y h:i A', strtotime($pass['valid_from'])) ?></span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Valid Until</span>
                        <span class="info-value"><?= date('M d, Y h:i A', strtotime($pass['valid_until'])) ?></span>
                    </div>
                </div>
                
                <!-- QR Code -->
                <?php if (!empty($pass['qr_code'])): ?>
                <div class="qr-section">
                    <?php 
                    // Generate the view pass URL
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'];
                    $currentUrl = $protocol . '://' . $host . $_SERVER['REQUEST_URI'];
                    ?>
                    <a href="<?= htmlspecialchars($currentUrl) ?>" target="_blank" style="display: inline-block; text-decoration: none;">
                        <div class="qr-code" style="cursor: pointer; transition: transform 0.2s ease;">
                            <img src="<?= htmlspecialchars($pass['qr_code']) ?>" alt="QR Code">
                        </div>
                    </a>
                    <p class="qr-label">Scan to Verify</p>
                    <p style="color: #9ca3af; font-size: 0.75rem; margin-top: 0.5rem;">Click QR code to open in new tab</p>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
