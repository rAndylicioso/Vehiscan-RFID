<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/security_headers.php';
require_once __DIR__ . '/../includes/rate_limiter.php';
require_once __DIR__ . '/../includes/input_sanitizer.php';
require_once __DIR__ . '/../includes/common_utilities.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generate CSRF token using InputSanitizer
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = InputSanitizer::generateCsrf();
}
$csrf = $_SESSION['csrf_token'];

// formatContactNumber() now comes from common_utilities.php

if (isset($_GET['classic'])) {
  if (!empty($_FILES['test_image']['tmp_name']) && is_uploaded_file($_FILES['test_image']['tmp_name'])) {
    $testDir = __DIR__ . '/../uploads/homeowners/';
    if (!is_dir($testDir)) {
      mkdir($testDir, 0755, true);
    }
    $dest = $testDir . 'test_' . basename($_FILES['test_image']['name']);
    if (move_uploaded_file($_FILES['test_image']['tmp_name'], $dest)) {
      echo '<p>Classic upload succeeded and saved as ' . htmlspecialchars($dest) . '</p>';
    } else {
      echo '<p>Classic upload received the file but failed to save.</p>';
    }
  } else {
    echo '<p>No file received by classic upload handler.</p>';
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');

  // Rate limiting check (3 registration attempts per hour per IP)
  $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  $rateLimiter = new RateLimiter($pdo);
  $rateCheck = $rateLimiter->check($ipAddress, 'registration', 3, 60);
  
  if (!$rateCheck['allowed']) {
    $minutesLeft = ceil($rateCheck['reset_time'] / 60);
    echo json_encode([
      'success' => false, 
      'message' => "Too many registration attempts. Please try again in {$minutesLeft} minutes."
    ]);
    exit;
  }

  // Validate CSRF token using InputSanitizer
  $posted_csrf = InputSanitizer::post('csrf_token', 'string');
  if (!InputSanitizer::validateCsrf($posted_csrf)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh and try again.']);
    exit;
  }

  // Get structured name fields using InputSanitizer
  $firstName = InputSanitizer::post('first_name', 'string');
  $middleName = InputSanitizer::post('middle_name', 'string');
  $lastName = InputSanitizer::post('last_name', 'string');
  $suffix = InputSanitizer::post('suffix', 'string');
  
  // Combine names for backward compatibility
  $fullName = trim("$firstName $middleName $lastName $suffix");
  
  $email = InputSanitizer::post('email', 'email');
  $contact = InputSanitizer::post('contact', 'string');
  $address = InputSanitizer::post('address', 'string');
  $vehicle_type = InputSanitizer::post('vehicle_type', 'string');
  $color = InputSanitizer::post('color', 'string');
  $plate_number = strtoupper(InputSanitizer::post('plate_number', 'string'));
  $username = InputSanitizer::post('username', 'string');
  $password = InputSanitizer::post('password', 'string');
  $confirm_password = InputSanitizer::post('confirm_password', 'string');
  
  // Validate required fields
  if (empty($firstName) || empty($lastName) || empty($email) || empty($contact) || empty($address) || empty($vehicle_type) || empty($color) || empty($plate_number) || empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled out.']);
    exit;
  }
  
  // Validate email format
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address format.']);
    exit;
  }
  
  // Format contact number (0912-345-6789)
  $contact = formatContactNumber($contact);
  if ($contact && !preg_match('/^\d{4}-\d{3}-\d{4}$/', $contact)) {
    echo json_encode(['success' => false, 'message' => 'Contact number must be in format: 0912-345-6789']);
    exit;
  }

  $ownersDir = __DIR__ . '/../uploads/homeowners/';
  $vehiclesDir = __DIR__ . '/../uploads/vehicles/';

  $ownerImagePath = null;
  $vehicleImagePath = null;
  $uploadErrors = [];

  // Load validation library
  require_once __DIR__ . '/../includes/input_validator.php';

  // Ensure directories exist
  foreach ([$ownersDir, $vehiclesDir] as $dir) {
    if (!is_dir($dir)) {
      if (!mkdir($dir, 0755, true)) {
        error_log("Failed to create directory: " . $dir);
        $uploadErrors[] = "Failed to create upload directory. Please contact administrator.";
      }
    }
    
    if (!is_writable($dir)) {
      error_log("Directory not writable: " . $dir);
      $uploadErrors[] = "Upload directory not writable. Please contact administrator.";
    }
  }

  if ($uploadErrors) {
    echo json_encode(['success' => false, 'message' => implode(' ', $uploadErrors)]);
    exit;
  }

  $saveFile = function (string $fieldName, bool $required = false) use ($ownersDir, $vehiclesDir, &$uploadErrors) {
    if (!isset($_FILES[$fieldName]) || (int)($_FILES[$fieldName]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
      if ($required) {
        $uploadErrors[] = ucfirst(str_replace('_', ' ', $fieldName)) . ' is required.';
      }
      return null;
    }

    $file = $_FILES[$fieldName];
    $fieldLabel = ucfirst(str_replace('_', ' ', $fieldName));

    if ($file['error'] !== UPLOAD_ERR_OK) {
      $uploadErrors[] = sprintf('%s upload failed (error code %d).', $fieldLabel, $file['error']);
      return null;
    }

    if (!is_uploaded_file($file['tmp_name'])) {
      $uploadErrors[] = sprintf('%s upload failed (temp file missing).', $fieldLabel);
      return null;
    }

    $maxSize = 4 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
      $uploadErrors[] = sprintf('%s exceeds the 4 MB limit.', $fieldLabel);
      return null;
    }

    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
    $detectedType = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
    if ($finfo) finfo_close($finfo);

    $typeToCheck = $detectedType ?: ($file['type'] ?? '');

    if ($typeToCheck && stripos($typeToCheck, 'image/') !== 0) {
      $uploadErrors[] = sprintf('%s must be an image file.', $fieldLabel);
      return null;
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!$ext && $typeToCheck) {
      $ext = str_replace('image/', '', $typeToCheck);
    }
    if (!$ext || strlen($ext) > 5) {
      $ext = 'png';
    }

    // Generate a unique filename
    $filename = date('Ymd_His_') . uniqid() . '.' . preg_replace('/[^a-z0-9]/i', '', $ext);
    
    // Determine the correct directory and path
    $uploadDir = $fieldName === 'owner_img' ? $ownersDir : $vehiclesDir;
    $destination = $uploadDir . $filename;
    $relativePath = $fieldName === 'owner_img' ? 'homeowners/' : 'vehicles/';

    error_log(sprintf("Saving %s to: %s", $fieldLabel, $destination));

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
      $uploadErrors[] = sprintf('%s could not be saved. Error: %s', $fieldLabel, error_get_last()['message'] ?? 'Unknown error');
      error_log(sprintf("Upload failed for %s: %s", $fieldLabel, error_get_last()['message'] ?? 'Unknown error'));
      return null;
    }

    return $relativePath . $filename;
  };

  $ownerImagePath = $saveFile('owner_img', true);
  $vehicleImagePath = $saveFile('car_img');

  if ($uploadErrors) {
    echo json_encode(['success' => false, 'message' => implode(' ', $uploadErrors)]);
    exit;
  }

  // Validate username
  $usernameValidation = InputValidator::validateUsername($username);
  if (!$usernameValidation['valid']) {
    echo json_encode(['success' => false, 'message' => $usernameValidation['message']]);
    exit;
  }

  // Validate password
  if ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
  }

  $passwordValidation = InputValidator::validatePassword($password, 12);
  if (!$passwordValidation['valid']) {
    echo json_encode(['success' => false, 'message' => $passwordValidation['message']]);
    exit;
  }

  // Validate plate number
  $plateValidation = InputValidator::validatePlateNumber($plate_number);
  if (!$plateValidation['valid']) {
    echo json_encode(['success' => false, 'message' => $plateValidation['message']]);
    exit;
  }
  $plate_number = $plateValidation['formatted']; // Use sanitized version

  // Validate phone number
  $phoneValidation = InputValidator::validatePhoneNumber($contact);
  if (!$phoneValidation['valid']) {
    echo json_encode(['success' => false, 'message' => $phoneValidation['message']]);
    exit;
  }

  if ($fullName && $contact && $address && $vehicle_type && $color && $plate_number && $username && $password) {
    try {
      // Hash the password (do this BEFORE transaction for better performance)
      $password_hash = password_hash($password, PASSWORD_DEFAULT);
      
      // Check if homeowner_auth table exists
      $tableExists = false;
      try {
        $pdo->query("SELECT 1 FROM homeowner_auth LIMIT 1");
        $tableExists = true;
      } catch (PDOException $e) {
        error_log("homeowner_auth table does not exist - will create it");
      }
      
      // If table doesn't exist, create it now
      if (!$tableExists) {
        $pdo->exec("
          CREATE TABLE IF NOT EXISTS homeowner_auth (
            id INT AUTO_INCREMENT PRIMARY KEY,
            homeowner_id INT NOT NULL,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            is_active TINYINT(1) DEFAULT 0,
            account_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            last_login DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (homeowner_id) REFERENCES homeowners(id) ON DELETE CASCADE,
            INDEX idx_username (username),
            INDEX idx_email (email)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        error_log("Created homeowner_auth table automatically");
      }
      
      // Start transaction for data consistency
      $pdo->beginTransaction();

      // Insert into homeowners table (vehicle and owner info)
      // Note: using 'contact_number' not 'contact', 'email' already exists in table
      $stmt = $pdo->prepare("INSERT INTO homeowners (name, first_name, middle_name, last_name, suffix, email, contact_number, address, vehicle_type, color, plate_number, owner_img, car_img, account_status, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'pending',NOW())");
      $stmt->execute([
        $fullName,
        $firstName,
        $middleName,
        $lastName,
        $suffix,
        $email,
        $contact,
        $address,
        $vehicle_type,
        $color,
        $plate_number,
        $ownerImagePath,
        $vehicleImagePath
      ]);
      
      $homeowner_id = $pdo->lastInsertId();

      // Insert into homeowner_auth table (login credentials - inactive until approved)
      // Note: account_status column doesn't exist in homeowner_auth
      $authStmt = $pdo->prepare("INSERT INTO homeowner_auth (homeowner_id, username, password_hash, email, is_active, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
      $authStmt->execute([
        $homeowner_id,
        $username,
        $password_hash,
        $email
      ]);

      // Commit transaction
      $pdo->commit();

      // Reset rate limit on successful registration
      $rateLimiter->reset($ipAddress, 'registration');

      echo json_encode([
        'success' => true, 
        'message' => 'Registration successful! Your account is pending approval by the administrator. You will be notified via email once approved.',
        'homeowner_id' => $homeowner_id,
        'plate_number' => $plate_number,
        'username' => $username,
        'email' => $email,
        'status' => 'pending'
      ]);
      
      // Log registration for admin review
      error_log("New homeowner registration: $username ($email) - Status: Pending Approval");
    } catch (PDOException $e) {
      // Rollback transaction on error
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      
      // Check if it's a duplicate username/email
      if ($e->getCode() == 23000) {
        echo json_encode([
          'success' => false,
          'message' => 'Username or email already exists. Please use a different one.'
        ]);
      }
      // Check if it's a column not found error
      else if (strpos($e->getMessage(), 'Unknown column') !== false) {
        echo json_encode([
          'success' => false, 
          'message' => 'Database configuration error: Please contact the administrator to update the system.',
          'technical_details' => 'Run the migration script at: _testing/apply_homeowner_auth_migration.php'
        ]);
      } else {
        echo json_encode(['success' => false, 'message' => 'Database error occurred. Please try again later.']);
      }
      
      error_log('REGISTRATION ERROR: ' . $e->getMessage());
      
      // Record failed attempt
      $rateLimiter->recordAttempt($ipAddress, 'registration', [
        'username' => $username ?? 'unknown',
        'error' => 'database_error'
      ]);
    } catch (Exception $e) {
      // Rollback transaction on error
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      
      echo json_encode(['success' => false, 'message' => 'Unexpected error occurred. Please try again later.']);
      error_log('REGISTRATION ERROR: ' . $e->getMessage());
      
      // Record failed attempt
      $rateLimiter->recordAttempt($ipAddress, 'registration', [
        'username' => $username ?? 'unknown',
        'error' => 'exception'
      ]);
    }
  } else {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    
    // Record failed attempt
    $rateLimiter->recordAttempt($ipAddress, 'registration', [
      'error' => 'validation_failed'
    ]);
  }
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Homeowner Registration — VehiScan</title>
<link rel="stylesheet" href="../assets/css/registration.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  <!-- Background decorations -->
  <div class="bg-decoration bg-decoration-1"></div>
  <div class="bg-decoration bg-decoration-2"></div>

  <!-- Loading Overlay -->
  <div id="loadingOverlay" class="loading-overlay hidden">
    <div class="loading-card">
      <div class="spinner"></div>
      <h3 class="loading-title">Processing Registration</h3>
      <p class="loading-message" id="loadingMessage">Uploading images and saving data...</p>
      <div class="progress-bar-container">
        <div id="progressBar" class="progress-bar" style="width: 0%"></div>
      </div>
    </div>
  </div>

  <div class="registration-container">
    <div class="logo-container">
      <img src="../assets/images/vehiscan-logo.png" alt="VehiScan Logo" class="logo-image">
    </div>

    <h1>Create Your Account</h1>
    <p class="subtitle">Set up your homeowner account for seamless gate access.</p>

    <form id="registrationForm" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

      <!-- Homeowner Details -->
      <fieldset>
        <legend>Account Information</legend>
        <p class="fieldset-description">Your personal details and login credentials.</p>

        <div class="form-row">
          <div class="form-group" style="flex: 2;">
            <label class="form-label">
              First Name <span class="required">*</span>
            </label>
            <input
              type="text"
              name="first_name"
              id="firstName"
              placeholder="e.g., Juan"
              required
              minlength="2"
              maxlength="50"
            >
          </div>
          
          <div class="form-group" style="flex: 1;">
            <label class="form-label">
              Middle Name
            </label>
            <input
              type="text"
              name="middle_name"
              id="middleName"
              placeholder="e.g., Santos"
              maxlength="50"
            >
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group" style="flex: 2;">
            <label class="form-label">
              Last Name <span class="required">*</span>
            </label>
            <input
              type="text"
              name="last_name"
              id="lastName"
              placeholder="e.g., Dela Cruz"
              required
              minlength="2"
              maxlength="50"
            >
          </div>
          
          <div class="form-group" style="flex: 1;">
            <label class="form-label">
              Suffix
            </label>
            <input
              type="text"
              name="suffix"
              id="suffix"
              placeholder="Jr., Sr., III"
              maxlength="10"
            >
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">
            Email Address <span class="required">*</span>
          </label>
          <input
            type="email"
            name="email"
            id="email"
            placeholder="juan.delacruz@email.com"
            required
            maxlength="100"
          >
          <p class="form-hint">For account notifications and password recovery</p>
        </div>

        <div class="form-group">
          <label class="form-label">
            Username <span class="required">*</span>
          </label>
          <input
            type="text"
            name="username"
            placeholder="e.g., jdelacruz"
            required
            minlength="3"
            maxlength="50"
            pattern="[a-zA-Z0-9_]+"
            title="Username can only contain letters, numbers, and underscores"
          >
          <p class="form-hint">Use only letters, numbers, and underscores</p>
        </div>

        <div class="form-group">
          <label class="form-label">
            Password <span class="required">*</span>
          </label>
          <div class="password-wrapper">
            <input
              type="password"
              name="password"
              id="passwordInput"
              placeholder="Create a secure password"
              required
              minlength="12"
              maxlength="100"
              pattern="(?=.*[a-zA-Z])(?=.*[0-9]).{12,}"
              title="Must be at least 12 characters with letters and numbers"
            >
            <button type="button" class="toggle-password" data-target="passwordInput" aria-label="Toggle password visibility" tabindex="-1">
              <span class="eye-icon">👁️</span>
            </button>
          </div>
          <p class="form-hint">At least 12 characters with letters and numbers</p>
        </div>

        <div class="form-group">
          <label class="form-label">
            Confirm Password <span class="required">*</span>
          </label>
          <div class="password-wrapper">
            <input
              type="password"
              name="confirm_password"
              id="confirmPasswordInput"
              placeholder="Re-enter password"
              required
              minlength="12"
              maxlength="100"
            >
            <button type="button" class="toggle-password" data-target="confirmPasswordInput" aria-label="Toggle confirm password visibility" tabindex="-1">
              <span class="eye-icon">👁️</span>
            </button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">
            Contact Number <span class="required">*</span>
          </label>
          <input
            type="tel"
            name="contact"
            id="contact"
            placeholder="e.g., 0912-345-6789"
            required
            pattern="^\d{4}-\d{3}-\d{4}$"
            title="Format: 0912-345-6789"
          >
          <p class="form-hint">Will auto-format to: 0912-345-6789</p>
        </div>

        <div class="form-group">
          <label class="form-label">
            Home Address <span class="required">*</span>
          </label>
          <textarea
            name="address"
            placeholder="e.g., Block 10 Lot 5, Maple Street, Greenwood Subdivision"
            required
          ></textarea>
        </div>
      </fieldset>

      <!-- Vehicle Details -->
      <fieldset>
        <legend>Vehicle Information</legend>
        <p class="fieldset-description">Register your primary vehicle for automated gate entry.</p>

        <div class="grid-cols-2">
          <div class="form-group">
            <label class="form-label">
              Vehicle Type <span class="required">*</span>
            </label>
            <select
              name="vehicle_type"
              id="vehicleTypeInput"
              required
            >
              <option value="">Choose vehicle type</option>
              <option value="Car">Car</option>
              <option value="Motorcycle">Motorcycle</option>
              <option value="Truck">Truck</option>
              <option value="SUV">SUV</option>
              <option value="Van">Van</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">
              Vehicle Color <span class="required">*</span>
            </label>
            <input
              type="text"
              name="color"
              id="colorInput"
              placeholder="e.g., White, Silver, Black"
              required
              maxlength="30"
            >
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">
            License Plate Number <span class="required">*</span>
          </label>
          <input
            type="text"
            name="plate_number"
            id="plateInput"
            placeholder="e.g., ABC-1234"
            required
            pattern="[A-Z0-9\-]{3,15}"
            title="Plate number should be 3-15 characters (letters, numbers, hyphens)"
            style="text-transform: uppercase;"
            autocomplete="off"
          >
          <p class="form-hint" id="plateHint">Required for automated gate recognition</p>
        </div>
      </fieldset>

      <!-- Photos -->
      <fieldset>
        <legend>Upload Photos</legend>
        <p class="fieldset-description">Clear photos enable quick verification at the gate.</p>

        <div class="form-group">
          <label class="form-label">
            Your Photo <span class="required">*</span>
          </label>
          <div class="upload-box" id="ownerUploadBox" data-for="owner_img">
            <div class="upload-icon">📸</div>
            <div class="upload-text">
              <p class="upload-title">Click to upload or drag and drop</p>
              <p class="upload-subtitle">JPG, PNG, or WEBP (max 4MB)</p>
            </div>
            <input
              type="file"
              name="owner_img"
              id="ownerImgInput"
              accept="image/*"
              required
              class="upload-input"
            >
            <div class="upload-preview" id="ownerPreview" style="display: none;">
              <img id="ownerPreviewImg" alt="Owner preview">
              <button type="button" class="preview-remove" data-for="owner_img">✕</button>
            </div>
            <div class="upload-actions">
              <button type="button" class="camera-btn" data-for="owner_img">📸 Use Camera</button>
              <button type="button" class="gallery-btn" data-for="owner_img">🖼️ Choose from Gallery</button>
            </div>
            <div class="note" id="ownerImgLabel">Owner photo is required for verification</div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">
            Vehicle Photo <span class="text-gray-400">(Optional)</span>
          </label>
          <div class="upload-box" id="carUploadBox" data-for="car_img">
            <div class="upload-icon">🚗</div>
            <div class="upload-text">
              <p class="upload-title">Click to upload or drag and drop</p>
              <p class="upload-subtitle">JPG, PNG, or WEBP (max 4MB)</p>
            </div>
            <input
              type="file"
              name="car_img"
              id="carImgInput"
              accept="image/*"
              class="upload-input"
            >
            <div class="upload-preview" id="carPreview" style="display: none;">
              <img id="carPreviewImg" alt="Vehicle preview">
              <button type="button" class="preview-remove" data-for="car_img">✕</button>
            </div>
            <div class="upload-actions">
              <button type="button" class="camera-btn" data-for="car_img">📸 Use Camera</button>
              <button type="button" class="gallery-btn" data-for="car_img">🖼️ Choose from Gallery</button>
            </div>
            <div class="note" id="carImgLabel">Helps guards identify your vehicle</div>
          </div>
        </div>
      </fieldset>

      <button type="submit" class="btn-primary" id="submitBtn">
        <span class="btn-text">Create Account</span>
        <span class="btn-loading" style="display: none;">
          <span class="btn-spinner"></span>
          <span>Processing...</span>
        </span>
      </button>

      <div class="keyboard-hint">
        Press <span class="hint-key">Ctrl</span> + <span class="hint-key">Enter</span> to submit
      </div>

      <div class="info-box">
        <p class="info-box-text">
          <strong>Note:</strong> All fields marked with <span class="required">*</span> are required. Your license plate number will be automatically converted to uppercase for consistency.
        </p>
      </div>
    </form>

    <div class="divider"><span>or</span></div>

    <p class="signup-link">Already have an account? <a href="../auth/login.php">Sign in here</a></p>
  </div>

  <!-- Local Scripts -->
  <!-- SweetAlert2 fallback: Create window.Swal object if not loaded from CDN -->
  <script>
    if (typeof Swal === 'undefined') {
      window.Swal = {
        fire: function(options) {
          const isConfirm = options.showCancelButton || options.showConfirmButton !== false;
          const message = options.html || options.text || options.title || '';
          if (isConfirm) {
            return Promise.resolve({ isConfirmed: confirm(message), value: true });
          } else {
            alert(options.icon ? options.icon.toUpperCase() + ': ' + message : message);
            return Promise.resolve({ isConfirmed: true });
          }
        }
      };
      console.warn('SweetAlert2 not loaded, using fallback alert/confirm');
    }

    // Password toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
      const toggleButtons = document.querySelectorAll('.toggle-password');
      
      toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
          const targetId = this.getAttribute('data-target');
          const input = document.getElementById(targetId);
          const eyeIcon = this.querySelector('.eye-icon');
          
          if (input) {
            if (input.type === 'password') {
              input.type = 'text';
              eyeIcon.textContent = '🙈';
              this.setAttribute('aria-label', 'Hide password');
            } else {
              input.type = 'password';
              eyeIcon.textContent = '👁️';
              this.setAttribute('aria-label', 'Show password');
            }
          }
        });
      });
      
      // Auto-format contact number to 0912-345-6789
      const contactInput = document.getElementById('contact');
      if (contactInput) {
        contactInput.addEventListener('input', function(e) {
          let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
          
          // Limit to 11 digits (Philippine mobile number)
          if (value.length > 11) {
            value = value.slice(0, 11);
          }
          
          // Format as 0912-345-6789
          if (value.length >= 4) {
            value = value.slice(0, 4) + '-' + value.slice(4);
          }
          if (value.length >= 8) {
            value = value.slice(0, 8) + '-' + value.slice(8);
          }
          
          e.target.value = value;
        });
        
        // Validate on blur
        contactInput.addEventListener('blur', function(e) {
          const pattern = /^\d{4}-\d{3}-\d{4}$/;
          if (e.target.value && !pattern.test(e.target.value)) {
            e.target.setCustomValidity('Contact number must be in format: 0912-345-6789');
            e.target.reportValidity();
          } else {
            e.target.setCustomValidity('');
          }
        });
      }
    });
  </script>
  <script src="../assets/js/registration.js"></script>
</body>
</html>