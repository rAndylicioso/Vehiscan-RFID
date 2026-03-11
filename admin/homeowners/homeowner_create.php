<?php
// admin/homeowners/homeowner_create.php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/input_validator.php';

// Authorization check
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) {
    http_response_code(403);
    exit('Unauthorized');
}

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

// POST create (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $posted = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, (string)$posted)) {
        echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit;
    }
    $name = trim($_POST['name'] ?? '');
    $plate = trim($_POST['plate_number'] ?? '');
    $vehicle = trim($_POST['vehicle_type'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $color = trim($_POST['color'] ?? '');

    if (!$name || !$plate) {
        echo json_encode(['success'=>false,'message'=>'Name and plate number required']); exit;
    }

    // Validate name length
    if (strlen($name) < 2 || strlen($name) > 100) {
        echo json_encode(['success'=>false,'message'=>'Name must be 2-100 characters']); exit;
    }

    // Validate plate number format
    $plateCheck = InputValidator::validatePlateNumber($plate);
    if (!$plateCheck['valid']) {
        echo json_encode(['success'=>false,'message'=>$plateCheck['message']]); exit;
    }
    $plate = $plateCheck['formatted'];

    // Validate phone number if provided
    if ($contact !== '') {
        $phoneCheck = InputValidator::validatePhoneNumber($contact);
        if (!$phoneCheck['valid']) {
            echo json_encode(['success'=>false,'message'=>$phoneCheck['message']]); exit;
        }
        $contact = $phoneCheck['formatted'];
    }

    // Validate address length
    if ($address !== '' && strlen($address) > 255) {
        echo json_encode(['success'=>false,'message'=>'Address must not exceed 255 characters']); exit;
    }
    
    // Handle image uploads
    $owner_img = null;
    $car_img = null;
    
    $owners_upload_dir = __DIR__ . '/../../uploads/homeowners/';
    $vehicles_upload_dir = __DIR__ . '/../../uploads/vehicles/';
    
    // Ensure upload directories exist
    foreach ([$owners_upload_dir, $vehicles_upload_dir] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    $allowed = ['jpg','jpeg','png','webp','heic'];
    $allowed_mimes = ['image/jpeg','image/png','image/webp','image/heic','image/heif'];
    
    // Handle owner image upload
    if (!empty($_FILES['owner_img']['name']) && $_FILES['owner_img']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['owner_img']['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success'=>false,'message'=>'Owner image too large. Maximum 5MB.']); exit;
        }
        $ext = strtolower(pathinfo($_FILES['owner_img']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['owner_img']['tmp_name']);
            if (in_array($mime, $allowed_mimes)) {
                $filename = date('Ymd_His_') . 'owner_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['owner_img']['tmp_name'], $owners_upload_dir . $filename)) {
                    $owner_img = 'homeowners/' . $filename;
                }
            } else {
                error_log("[HOMEOWNER_CREATE] Rejected upload: MIME $mime for owner_img");
            }
        }
    }
    
    // Handle car image upload
    if (!empty($_FILES['car_img']['name']) && $_FILES['car_img']['error'] === UPLOAD_ERR_OK) {
        if ($_FILES['car_img']['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success'=>false,'message'=>'Vehicle image too large. Maximum 5MB.']); exit;
        }
        $ext = strtolower(pathinfo($_FILES['car_img']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['car_img']['tmp_name']);
            if (in_array($mime, $allowed_mimes)) {
                $filename = date('Ymd_His_') . 'car_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['car_img']['tmp_name'], $vehicles_upload_dir . $filename)) {
                    $car_img = 'vehicles/' . $filename;
                }
            } else {
                error_log("[HOMEOWNER_CREATE] Rejected upload: MIME $mime for car_img");
            }
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO homeowners (name, plate_number, vehicle_type, contact_number, address, color, owner_img, car_img) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$name, $plate, $vehicle, $contact, $address, $color, $owner_img, $car_img]);
        echo json_encode(['success'=>true,'message'=>'Homeowner added successfully']);
    } catch (Exception $e) {
        error_log('[HOMEOWNER_CREATE] DB error: ' . $e->getMessage());
        echo json_encode(['success'=>false,'message'=>'A database error occurred. Please try again.']);
    }
    exit;
}

// GET => render form fragment (when called with ajax=1)
?>
<form id="createHomeownerForm" class="modern-form compact-form" action="homeowners/homeowner_create.php" method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

  <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 text-center form-title">Add New Homeowner</h3>
  <p class="text-xs text-gray-500 dark:text-gray-400 mb-5 text-center">Create a new homeowner record and link it to a vehicle.</p>

  <div class="grid grid-cols-2 gap-4 compact-grid">
    <!-- Row 1 -->
    <label class="block col-span-2 sm:col-span-1">
      <span class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 block">Name <span class="text-red-500">*</span></span>
      <input
        type="text"
        name="name"
        required
        placeholder="Full name"
        class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      >
    </label>

    <label class="block col-span-2 sm:col-span-1">
      <span class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 block">Plate Number <span class="text-red-500">*</span></span>
      <input
        type="text"
        name="plate_number"
        required
        placeholder="e.g., ABC123"
        class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      >
    </label>

    <!-- Row 2 -->
    <label class="block col-span-2 sm:col-span-1">
      <span class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 block">Vehicle Type</span>
      <input
        type="text"
        name="vehicle_type"
        placeholder="e.g., Sedan, SUV"
        class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      >
    </label>

    <label class="block col-span-2 sm:col-span-1">
      <span class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 block">Color</span>
      <input
        type="text"
        name="color"
        placeholder="e.g., Blue, Red"
        class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      >
    </label>

    <!-- Row 3 -->
    <label class="block col-span-2 sm:col-span-1">
      <span class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 block">Contact</span>
      <input
        type="text"
        name="contact"
        placeholder="Phone number"
        class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      >
    </label>

    <label class="block col-span-2 sm:col-span-1">
      <span class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 block">Address</span>
      <textarea
        name="address"
        rows="2"
        placeholder="Full address"
        class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
      ></textarea>
    </label>

    <!-- Row 4: Images -->
    <div class="block col-span-2 sm:col-span-1">
      <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 block">Owner Image</label>
      <input
        type="file"
        name="owner_img"
        accept="image/*"
        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
      <small class="text-gray-500 text-xs mt-1 block">Optional. JPG, PNG, WEBP.</small>
    </div>

    <div class="block col-span-2 sm:col-span-1">
      <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5 block">Car Image</label>
      <input
        type="file"
        name="car_img"
        accept="image/*"
        class="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg text-sm bg-white dark:bg-slate-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
      <small class="text-gray-500 text-xs mt-1 block">Optional. JPG, PNG, WEBP.</small>
    </div>
  </div>

  <div class="flex items-center justify-end gap-3 pt-4 mt-6 border-t border-gray-200 form-actions">
    <button type="button" class="btn btn-secondary cancel-btn">Cancel</button>
    <button type="submit" class="btn btn-add">➕ Add</button>
  </div>
</form>
