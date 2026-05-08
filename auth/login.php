<?php
// VehiScan RFID - Login Page (FIXED SESSION NAMES)
// Start output buffering to prevent header issues
ob_start();

// Database connection - use centralized config
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/security_headers.php';
require_once __DIR__ . '/../includes/input_sanitizer.php';

// Start session with default name first
if (session_status() === PHP_SESSION_NONE) {
    // Isolate from other XAMPP apps to prevent cross-app GC
    $appSavePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vehiscan_sessions';
    if (!is_dir($appSavePath)) { mkdir($appSavePath, 0700, true); }
    ini_set('session.save_path', $appSavePath);
    ini_set('session.gc_maxlifetime', 3600);
    session_start();
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

// Handle login
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate CSRF token
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!InputSanitizer::validateCsrf((string)$submittedToken)) {
        $error = "Invalid form submission. Please try again.";
    } else {
    $identifier = trim((string)($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    
    $authenticated = false;
    $redirectUrl = '';
    $userRole = '';
    $userId = 0;
    $sessionUsername = '';
    
    // Validate identifier
    if ($identifier === '') {
        $error = "Email or username is required.";
    } else {
    
    // Try super_admin
    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM super_admin WHERE email = ? OR username = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier]);
    $result = $stmt->fetch();
    
    if ($result) {
        $passwordMatch = password_verify($password, $result['password_hash']);
        
        if ($passwordMatch) {
            $authenticated = true;
            $userRole = 'super_admin';
            $userId = $result['id'];
            $sessionUsername = (string)($result['username'] ?? $identifier);
            $redirectUrl = '../admin/admin_panel.php';
        }
    }
    
    // Try users table if not authenticated yet
    if (!$authenticated) {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE email = ? OR username = ? LIMIT 1");
        $stmt->execute([$identifier, $identifier]);
        $result = $stmt->fetch();
        
        if ($result) {
            $passwordMatch = password_verify($password, $result['password']);
            
            if ($passwordMatch) {
                $authenticated = true;
                $rawRole = (string)($result['role'] ?? '');
                $userRole = $rawRole === 'owner' ? 'homeowner' : $rawRole;
                $userId = $result['id'];
                $sessionUsername = (string)($result['username'] ?? $identifier);
                
                if ($userRole === 'admin') {
                    $redirectUrl = '../admin/admin_panel.php';
                } elseif ($userRole === 'guard') {
                    $redirectUrl = '../guard/pages/guard_side.php';
                } elseif ($userRole === 'homeowner') {
                    $redirectUrl = '../homeowners/portal.php';
                }
            }
        }
    }
    
    // Try homeowner_auth table if not authenticated yet
    if (!$authenticated) {
        $stmt = $pdo->prepare("
            SELECT ha.id, ha.homeowner_id, ha.username, ha.password_hash, ha.is_active,
                   h.account_status
            FROM homeowner_auth ha
            JOIN homeowners h ON ha.homeowner_id = h.id
            WHERE ha.email = ? OR ha.username = ?
            LIMIT 1
        ");
        $stmt->execute([$identifier, $identifier]);
        $result = $stmt->fetch();
        
        if ($result && $result['is_active'] && $result['account_status'] === 'approved') {
            $passwordMatch = password_verify($password, $result['password_hash']);
            
            if ($passwordMatch) {
                $authenticated = true;
                $userRole = 'homeowner';
                $userId = $result['homeowner_id']; // Use homeowner_id, not auth ID
                $sessionUsername = (string)($result['username'] ?? $identifier);
                $redirectUrl = '../homeowners/portal.php';
                
                // Update last_login
                $pdo->prepare("UPDATE homeowner_auth SET last_login = NOW(), failed_login_attempts = 0 WHERE id = ?")->execute([$result['id']]);
            } else {
                // Track failed login
                $pdo->prepare("UPDATE homeowner_auth SET failed_login_attempts = failed_login_attempts + 1, last_failed_login = NOW() WHERE id = ?")->execute([$result['id']]);
            }
        }
    }
    
    // Perform redirect if authenticated
    if ($authenticated && $redirectUrl) {
        // Destroy current session
        session_destroy();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // Clear ALL role-specific session cookies to prevent session collision
        // This is critical: stale cookies from a previous role login will cause
        // session_admin_unified.php to pick up the wrong session
        $allSessionNames = ['vehiscan_superadmin', 'vehiscan_admin', 'vehiscan_guard', 'vehiscan_homeowner', 'vehiscan_session'];
        foreach ($allSessionNames as $sName) {
            if (isset($_COOKIE[$sName])) {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }
                // Destroy the session data on disk
                session_name($sName);
                session_id($_COOKIE[$sName]);
                session_start();
                $_SESSION = [];
                session_destroy();
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }
                // Expire the cookie
                setcookie($sName, '', time() - 3600, '/');
                unset($_COOKIE[$sName]);
            }
        }
        
        // Start new session with role-specific name
        $sessionName = 'vehiscan_session'; // Default
        if ($userRole === 'super_admin') {
            $sessionName = 'vehiscan_superadmin';
        } elseif ($userRole === 'admin') {
            $sessionName = 'vehiscan_admin';
        } elseif ($userRole === 'guard') {
            $sessionName = 'vehiscan_guard';
        } elseif ($userRole === 'homeowner') {
            $sessionName = 'vehiscan_homeowner';
        }
        
        // Start new session with correct name and secure cookie params
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        session_name($sessionName);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.use_strict_mode', 1);
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                   (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        ini_set('session.cookie_secure', $isHttps ? 1 : 0);
        session_start();
        session_regenerate_id(true); // Prevent session fixation
        
        // Set session variables
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $sessionUsername !== '' ? $sessionUsername : $identifier;
        $_SESSION['role'] = $userRole;
        $_SESSION['last_activity'] = time();
        $_SESSION['created'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        // Set role-specific session variables
        if ($userRole === 'guard') {
            $_SESSION['guard_id'] = $userId;
        } elseif ($userRole === 'homeowner') {
            $_SESSION['homeowner_id'] = $userId;
        }
        
        // Write session data
        session_write_close();
        
        // Clear output buffer
        ob_end_clean();
        
        // Redirect
        header("Location: $redirectUrl");
        exit();
    } else {
        $error = "Invalid credentials. Please check your email/username and password.";
    }
    }
    } // end CSRF else and identifier validation else
}

// Check for URL parameters
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'timeout') {
        $error = "Session expired. Please login again.";
    }
}

if (isset($_GET['setup']) && $_GET['setup'] === 'complete') {
    $success = "Super Admin account created successfully! Please login.";
}

// Flush output buffer for HTML
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in to VehiScan</title>
    <link rel="stylesheet" href="../assets/css/login.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/login.css'); ?>">
    <script src="../assets/js/libs/sweetalert2.all.min.js"></script>
</head>
<body>
    <!-- Background decorations -->
    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>
    <div class="bg-decoration bg-decoration-3"></div>

    <div class="login-container" role="main" aria-labelledby="loginTitle">
        <div class="logo-container">
            <img src="../assets/images/ville_de_palme.png" alt="VehiScan Logo" class="logo-image">
        </div>

        <h1 id="loginTitle">Sign in to VehiScan</h1>
        <p class="subtitle">Secure Access Control System</p>

        <form method="POST" class="login-form" autocomplete="on">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="form-group">
                <label for="email" class="form-label">Email or Username<span class="required">*</span></label>
                <div class="input-icon-wrapper">
                    <input id="email" name="email" type="text" placeholder="Enter your email or username" required
                        aria-label="Email or Username" autofocus autocomplete="username">
                    <span class="input-icon"><svg style="width:1em;height:1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg></span>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password<span class="required">*</span></label>
                <div class="password-wrapper">
                    <div class="input-icon-wrapper">
                        <input id="password" name="password" type="password" placeholder="••••••••••••••" required
                            aria-label="Password" autocomplete="current-password">
                        <span class="input-icon"><svg style="width:1em;height:1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
                    </div>
                    <button type="button" id="togglePassword" aria-label="Toggle password visibility"
                        tabindex="-1"><svg style="width:1em;height:1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
                </div>
            </div>

            <div class="remember-forgot">
                <div></div>
                <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-primary" id="submitBtn">
                <span class="btn-text">Sign in to VehiScan</span>
            </button>
        </form>

        <div class="divider"><span>or</span></div>

        <p class="signup-link">New on our platform? <a href="../homeowners/homeowner_registration.php"
                id="createAccountLink">Create an account</a></p>
    </div>

    <!-- Keyboard Hint -->
    <div class="keyboard-hint">
        <span><svg style="width:1em;height:1em;vertical-align:-0.15em;display:inline" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21h6M12 3a6 6 0 0 0-4 10.5V17h8v-3.5A6 6 0 0 0 12 3z"/></svg> Press</span>
        <kbd>Enter</kbd>
        <span>to sign in</span>
    </div>

    <!-- External JavaScript -->
    <script src="../assets/js/login.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/login.js'); ?>"></script>

    <!-- PHP-generated alerts -->
    <script>
        // Configure SweetAlert2 defaults
        if (typeof Swal !== 'undefined') {
            Swal.mixin({
                scrollbarPadding: false,
                heightAuto: false,
                backdrop: true,
                allowOutsideClick: true,
                allowEscapeKey: true
            });
        }

        <?php if (!empty($error)): ?>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: <?= json_encode($error) ?>,
                    confirmButtonText: 'Try Again',
                    confirmButtonColor: '#ef4444'
                });
            });
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: <?= json_encode($success) ?>,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3b82f6'
                });
            });
        <?php endif; ?>
    </script>
</body>
</html>