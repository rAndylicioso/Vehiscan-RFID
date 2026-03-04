<?php
/**
 * Reset Password Page
 * Validates token from URL, allows user to set a new password
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

$token = $_GET['token'] ?? '';
$message = '';
$messageType = '';
$tokenValid = false;
$resetComplete = false;

$handler = new PasswordResetHandler($pdo);

// Validate token on page load
if ($token) {
    $validation = $handler->validateToken($token);
    $tokenValid = $validation['valid'];
    if (!$tokenValid) {
        $message = $validation['error'];
        $messageType = 'error';
    }
} else {
    $message = 'No reset token provided. Please use the link from your email.';
    $messageType = 'error';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $submittedToken)) {
        $message = 'Invalid form submission. Please try again.';
        $messageType = 'error';
    } else {
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 8) {
            $message = 'Password must be at least 8 characters long.';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'Passwords do not match.';
            $messageType = 'error';
        } else {
            $result = $handler->resetPassword($token, $newPassword);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            if ($result['success']) {
                $resetComplete = true;
                $tokenValid = false; // Hide form
            }
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
    <title>Reset Password — VehiScan</title>
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

        <h1 id="pageTitle">Reset Password</h1>
        <p class="subtitle"><?php echo $tokenValid ? 'Enter your new password below' : ($resetComplete ? 'Password updated' : 'Reset link validation'); ?></p>

        <?php if ($message): ?>
            <div class="message-box"
                 style="margin-bottom: 16px; padding: 12px 16px; border-radius: 8px; font-size: 14px; line-height: 1.5;
                        <?php echo $messageType === 'success'
                            ? 'background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;'
                            : 'background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($tokenValid): ?>
            <form method="POST" class="login-form" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="form-group">
                    <label for="password" class="form-label">New Password<span class="required">*</span></label>
                    <div class="password-wrapper">
                        <div class="input-icon-wrapper">
                            <input id="password" name="password" type="password"
                                   placeholder="••••••••••••••" required minlength="8"
                                   aria-label="New password" autocomplete="new-password">
                            <span class="input-icon">🔒</span>
                        </div>
                        <button type="button" class="toggle-pw" onclick="togglePw('password', this)" aria-label="Toggle password visibility" tabindex="-1">👁</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password<span class="required">*</span></label>
                    <div class="password-wrapper">
                        <div class="input-icon-wrapper">
                            <input id="confirm_password" name="confirm_password" type="password"
                                   placeholder="••••••••••••••" required minlength="8"
                                   aria-label="Confirm new password" autocomplete="new-password">
                            <span class="input-icon">🔒</span>
                        </div>
                        <button type="button" class="toggle-pw" onclick="togglePw('confirm_password', this)" aria-label="Toggle password visibility" tabindex="-1">👁</button>
                    </div>
                </div>

                <p style="font-size: 12px; color: #6b7280; margin: 0 0 16px;">
                    Password must be at least 8 characters long.
                </p>

                <button type="submit" class="btn-primary" id="submitBtn">
                    <span class="btn-text">Reset Password</span>
                </button>
            </form>
        <?php elseif ($resetComplete): ?>
            <div style="text-align: center; margin-top: 16px;">
                <a href="login.php" class="btn-primary"
                   style="display: inline-block; text-decoration: none; padding: 12px 32px; background: #111827; color: #fff; border-radius: 6px; font-weight: 600; font-size: 14px;">
                    Sign in Now
                </a>
            </div>
        <?php else: ?>
            <div style="text-align: center; margin-top: 16px;">
                <a href="forgot-password.php"
                   style="color: #3b82f6; text-decoration: none; font-size: 14px; font-weight: 500;">
                    Request a new reset link
                </a>
            </div>
        <?php endif; ?>

        <p class="signup-link" style="margin-top: 20px;">
            <a href="login.php">Back to Sign in</a>
        </p>
    </div>

    <!-- Footer -->
    <div class="footer-text">
        &copy; <?php echo date('Y'); ?> VehiScan RFID — Secure Access Control
    </div>

    <script>
    function togglePw(inputId, btn) {
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
            btn.textContent = '🙈';
        } else {
            input.type = 'password';
            btn.textContent = '👁';
        }
    }
    </script>
</body>
</html>
