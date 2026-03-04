<?php
/**
 * Forgot Password Page
 * Accepts email, sends password reset link
 */
ob_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/password_reset.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$messageType = ''; // 'success' or 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $submittedToken)) {
        $message = 'Invalid form submission. Please try again.';
        $messageType = 'error';
    } else {
        $email = trim($_POST['email'] ?? '');

        // Rate limit (IP-based, on top of the token-based limit in the handler)
        require_once __DIR__ . '/../includes/rate_limit.php';
        $rateCheck = checkRateLimit('password_reset_page', 5, 15);

        if (!$rateCheck['allowed']) {
            $message = 'Too many attempts. Please try again in a few minutes.';
            $messageType = 'error';
        } else {
            logRateLimit('password_reset_page', false); // count every attempt

            $handler = new PasswordResetHandler($pdo);
            $result = $handler->requestReset($email);

            $message = $result['message'];
            $messageType = 'success'; // Always show success to prevent enumeration
        }
    }
}

ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — VehiScan</title>
    <link rel="stylesheet" href="../assets/css/login.css?v=<?php echo time(); ?>">
    <script src="../assets/js/libs/sweetalert2.all.min.js"></script>
</head>
<body>
    <!-- Background decorations -->
    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>
    <div class="bg-decoration bg-decoration-3"></div>

    <div class="login-container" role="main" aria-labelledby="pageTitle">
        <div class="logo-container">
            <img src="../assets/images/ville_de_palme.png" alt="VehiScan Logo" class="logo-image">
        </div>

        <h1 id="pageTitle">Forgot Password</h1>
        <p class="subtitle">Enter your email to receive a reset link</p>

        <?php if ($message): ?>
            <div class="message-box <?php echo $messageType === 'success' ? 'message-success' : 'message-error'; ?>"
                 style="margin-bottom: 16px; padding: 12px 16px; border-radius: 8px; font-size: 14px; line-height: 1.5;
                        <?php echo $messageType === 'success'
                            ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;'
                            : 'background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="login-form" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="form-group">
                <label for="email" class="form-label">Email Address<span class="required">*</span></label>
                <div class="input-icon-wrapper">
                    <input id="email" name="email" type="email"
                           placeholder="your@email.com" required
                           aria-label="Email address" autocomplete="email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    <span class="input-icon">✉️</span>
                </div>
            </div>

            <button type="submit" class="btn-primary" id="submitBtn">
                <span class="btn-text">Send Reset Link</span>
            </button>
        </form>

        <p class="signup-link" style="margin-top: 20px;">
            Remember your password? <a href="login.php">Sign in</a>
        </p>
    </div>

    <!-- Footer -->
    <div class="footer-text">
        &copy; <?php echo date('Y'); ?> VehiScan RFID — Secure Access Control
    </div>
</body>
</html>
