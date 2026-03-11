<?php
// Don't include security_headers.php to avoid HTTPS redirect on local network
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors to user, log them instead

// Use centralized database connection
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if (!isset($pdo)) {
    error_log("[VISITOR_PASS] Database connection not available");
    $error = 'Database Connection Failed';
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
} else {
    try {
        
        // First get the visitor pass
        $stmt = $pdo->prepare("SELECT * FROM visitor_passes WHERE qr_token = ?");
        $stmt->execute([$token]);
        $pass = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$pass) {
            $error = 'Invalid or Expired Visitor Pass';
        } else {
            
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
                $statusIcon = '<svg class="w-4 h-4 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6m0-6 6 6"/></svg>';
            } elseif ($dbStatus === 'pending') {
                $statusDisplay = 'Pending Approval';
                $statusClass = 'status-pending';
                $statusIcon = '<svg class="w-4 h-4 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16.5 12"/></svg>';
            } elseif ($now > $validUntil) {
                // Pass has expired (current time is after valid_until)
                $statusDisplay = 'Expired';
                $statusClass = 'status-expired';
                $statusIcon = '<svg class="w-4 h-4 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6m0-6 6 6"/></svg>';
            } elseif ($now < $validFrom) {
                // Pass is not yet valid (current time is before valid_from)
                $statusDisplay = 'Not Yet Valid';
                $statusClass = 'status-pending';
                $statusIcon = '<svg class="w-4 h-4 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16.5 12"/></svg>';
            } elseif ($dbStatus === 'active' || $dbStatus === 'approved') {
                // Pass is active and within valid time range
                $statusDisplay = 'Active';
                $statusClass = 'status-valid';
                $statusIcon = '<svg class="w-4 h-4 inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>';
            } else {
                $statusDisplay = 'Unknown Status';
                $statusClass = 'status-pending';
                $statusIcon = '?';
            }
            
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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Visitor Pass — VehiScan</title>
    <link rel="stylesheet" href="../assets/css/tailwind.css">
    <link rel="stylesheet" href="../assets/css/tailadmin-components.css?v=<?php echo @filemtime(__DIR__ . '/../assets/css/tailadmin-components.css') ?: time(); ?>">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .pass-animate { animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .grid-bg { background-image: radial-gradient(circle, #e2e8f0 1px, transparent 1px); background-size: 24px 24px; }
        @media (prefers-color-scheme: dark) {
            body { background: #0f172a; color: #e2e8f0; }
            .grid-bg { background-image: radial-gradient(circle, #334155 1px, transparent 1px); }
            .pass-card-bg { background: #1e293b; border-color: #334155; }
            .pass-body-bg { background: #1e293b; }
            .info-label-color { color: #94a3b8; }
            .info-value-color { color: #f1f5f9; }
            .info-row-border { border-color: #334155; }
            .qr-section-bg { background: #0f172a; border-color: #334155; }
            .qr-code-bg { background: #fff; }
        }
        @media not all and (prefers-color-scheme: dark) {
            .pass-card-bg { background: white; border-color: #e5e7eb; }
            .pass-body-bg { background: white; }
            .info-label-color { color: #6b7280; }
            .info-value-color { color: #111827; }
            .info-row-border { border-color: #e5e7eb; }
            .qr-section-bg { background: #fafafa; border-color: #e5e7eb; }
            .qr-code-bg { background: white; }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gray-100">
    <div class="fixed inset-0 grid-bg opacity-40 pointer-events-none"></div>

    <div class="relative z-10 w-full max-w-md pass-animate">
        <?php if ($error): ?>
            <!-- Error State -->
            <div class="pass-card-bg rounded-2xl border shadow-lg overflow-hidden">
                <div class="text-center p-8">
                    <div class="mx-auto w-20 h-20 rounded-full bg-red-100 flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold info-value-color mb-2"><?= htmlspecialchars($error ?? '') ?></h1>
                    <p class="text-sm info-label-color">The QR code may be invalid, expired, or not yet approved by the administrator.</p>
                    <p class="text-xs mt-4 info-label-color opacity-70">If you believe this is an error, please contact the subdivision office.</p>
                </div>
            </div>
        <?php else: ?>
            <!-- Visitor Pass Display -->
            <div class="pass-card-bg rounded-2xl border shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-slate-800 px-6 py-8 text-center relative border-b-4 border-sky-500">
                    <div class="mx-auto w-20 h-20 bg-white rounded-xl flex items-center justify-center shadow-lg p-2 mb-4 relative z-10">
                        <img src="../assets/images/ville_de_palme.png" alt="Ville de Palme Logo" class="w-full h-full object-contain">
                    </div>
                    <h1 class="text-2xl font-bold text-white tracking-wide mb-1">VISITOR PASS</h1>
                    <p class="text-sm text-white/80">VehiScan Security System</p>
                    <div class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-md text-sm font-semibold uppercase tracking-wider
                        <?php echo match($statusClass) {
                            'status-valid' => 'bg-emerald-500 text-white',
                            'status-expired' => 'bg-red-500 text-white',
                            'status-cancelled' => 'bg-gray-500 text-white',
                            'status-pending' => 'bg-amber-500 text-white',
                            default => 'bg-gray-400 text-white'
                        }; ?>">
                        <span class="text-base"><?= $statusIcon ?></span>
                        <span><?= $statusDisplay ?></span>
                    </div>
                </div>

                <!-- Pass Information -->
                <div class="pass-body-bg px-5 py-4">
                    <div class="flex justify-between items-center py-3 info-row-border border-b gap-4">
                        <span class="info-label-color text-xs font-medium uppercase tracking-wider flex-shrink-0">Visitor Name</span>
                        <span class="info-value-color text-sm font-semibold text-right"><?= htmlspecialchars($pass['visitor_name'] ?? '') ?></span>
                    </div>

                    <div class="flex justify-between items-center py-3 info-row-border border-b gap-4">
                        <span class="info-label-color text-xs font-medium uppercase tracking-wider flex-shrink-0">Purpose</span>
                        <span class="info-value-color text-sm font-semibold text-right"><?= htmlspecialchars($pass['purpose'] ?? '') ?></span>
                    </div>

                    <?php if (!empty($pass['visitor_plate'])): ?>
                    <div class="flex justify-between items-center py-3 info-row-border border-b gap-4">
                        <span class="info-label-color text-xs font-medium uppercase tracking-wider flex-shrink-0">Vehicle Plate</span>
                        <span class="info-value-color text-lg font-bold font-mono text-right"><?= htmlspecialchars($pass['visitor_plate'] ?? '') ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-between items-center py-3 info-row-border border-b gap-4">
                        <span class="info-label-color text-xs font-medium uppercase tracking-wider flex-shrink-0">Host</span>
                        <span class="info-value-color text-sm font-semibold text-right"><?= htmlspecialchars($pass['homeowner_name'] ?? '') ?></span>
                    </div>

                    <div class="flex justify-between items-center py-3 info-row-border border-b gap-4">
                        <span class="info-label-color text-xs font-medium uppercase tracking-wider flex-shrink-0">Address</span>
                        <span class="info-value-color text-sm font-semibold text-right break-words"><?= htmlspecialchars($pass['homeowner_address'] ?? '') ?></span>
                    </div>

                    <div class="flex justify-between items-center py-3 info-row-border border-b gap-4">
                        <span class="info-label-color text-xs font-medium uppercase tracking-wider flex-shrink-0">Valid From</span>
                        <span class="info-value-color text-sm font-semibold text-right"><?= date('M d, Y h:i A', strtotime($pass['valid_from'])) ?></span>
                    </div>

                    <div class="flex justify-between items-center py-3 gap-4">
                        <span class="info-label-color text-xs font-medium uppercase tracking-wider flex-shrink-0">Valid Until</span>
                        <span class="info-value-color text-sm font-semibold text-right"><?= date('M d, Y h:i A', strtotime($pass['valid_until'])) ?></span>
                    </div>
                </div>

                <!-- QR Code -->
                <?php if (!empty($pass['qr_code'])): ?>
                <div class="qr-section-bg border-t text-center px-6 py-8">
                    <?php
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'];
                    $currentUrl = $protocol . '://' . $host . $_SERVER['REQUEST_URI'];
                    ?>
                    <p class="info-label-color text-xs font-semibold uppercase tracking-wider mb-4">Scan to Verify</p>
                    <a href="<?= htmlspecialchars($currentUrl ?? '') ?>" target="_blank" class="inline-block">
                        <div class="qr-code-bg inline-block p-4 rounded-lg border info-row-border shadow-sm hover:shadow-md hover:scale-105 transition-all cursor-pointer">
                            <img src="<?= htmlspecialchars($pass['qr_code'] ?? '') ?>" alt="QR Code" class="block w-44 h-44 rounded">
                        </div>
                    </a>
                    <p class="text-xs info-label-color opacity-70 mt-3">Click QR code to open in new tab</p>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
