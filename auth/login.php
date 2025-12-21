<?php
// VehiScan RFID - Login Page (FIXED SESSION NAMES)
// Start output buffering to prevent header issues
ob_start();

// Database connection
$host = 'localhost';
$dbname = 'vehiscan_vdp';
$db_username = 'root';
$db_password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Start session with default name first
if (session_status() === PHP_SESSION_NONE) {
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
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $authenticated = false;
    $redirectUrl = '';
    $userRole = '';
    $userId = 0;
    
    // Try super_admin
    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM super_admin WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $result = $stmt->fetch();
    
    if ($result) {
        $passwordMatch = password_verify($password, $result['password_hash']) || $password === $result['password_hash'];
        
        if ($passwordMatch) {
            $authenticated = true;
            $userRole = 'super_admin';
            $userId = $result['id'];
            $redirectUrl = '../admin/admin_panel.php';
        }
    }
    
    // Try users table if not authenticated yet
    if (!$authenticated) {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $result = $stmt->fetch();
        
        if ($result) {
            $passwordMatch = password_verify($password, $result['password']) || $password === $result['password'];
            
            if ($passwordMatch) {
                $authenticated = true;
                $userRole = $result['role'];
                $userId = $result['id'];
                
                if ($result['role'] === 'admin') {
                    $redirectUrl = '../admin/admin_panel.php';
                } elseif ($result['role'] === 'guard') {
                    $redirectUrl = '../guard/pages/guard_side.php';
                } elseif ($result['role'] === 'homeowner') {
                    $redirectUrl = '../homeowners/portal.php';
                }
            }
        }
    }
    
    // Perform redirect if authenticated
    if ($authenticated && $redirectUrl) {
        // Destroy current session
        session_destroy();
        
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
        
        // Start new session with correct name
        session_name($sessionName);
        session_start();
        
        // Set session variables
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
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
        $error = "Invalid credentials. Please check your username and password.";
    }
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
    <link rel="stylesheet" href="../assets/css/login.css?v=<?php echo time(); ?>">
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
                <label for="username" class="form-label">Username<span class="required">*</span></label>
                <div class="input-icon-wrapper">
                    <input id="username" name="username" type="text" placeholder="Enter your username" required
                        aria-label="Username" autofocus autocomplete="username">
                    <span class="input-icon">👤</span>
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password<span class="required">*</span></label>
                <div class="password-wrapper">
                    <div class="input-icon-wrapper">
                        <input id="password" name="password" type="password" placeholder="••••••••••••••" required
                            aria-label="Password" autocomplete="current-password">
                        <span class="input-icon">🔒</span>
                    </div>
                    <button type="button" id="togglePassword" aria-label="Toggle password visibility"
                        tabindex="-1">👁</button>
                </div>
            </div>

            <div class="remember-forgot">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    <span>Remember Me</span>
                </label>
                <a href="#" class="forgot-link" onclick="handleForgotPassword(event)">Forgot Password?</a>
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
        <span>💡 Press</span>
        <kbd>Enter</kbd>
        <span>to sign in</span>
    </div>

    <!-- External JavaScript -->
    <script src="../assets/js/login.js?v=<?php echo time(); ?>"></script>

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
                    text: '<?= addslashes($error) ?>',
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
                    text: '<?= addslashes($success) ?>',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#ef4444'
                });
            });
        <?php endif; ?>
    </script>
</body>
</html>