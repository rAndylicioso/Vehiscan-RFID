<?php
// admin/homeowners/homeowner_edit.php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../db.php';

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_token'];

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM homeowners WHERE id = ?");
$stmt->execute([$id]);
$homeowner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$homeowner && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<p>Record not found.</p>"; exit;
}

// POST update (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $posted = $_POST['csrf'] ?? '';
    if (!hash_equals($csrf, (string)$posted)) {
        echo json_encode(['success'=>false,'message'=>'Invalid CSRF']); exit;
    }

    $name = trim($_POST['name'] ?? $homeowner['name']);
    $contact = trim($_POST['contact'] ?? $homeowner['contact']);
    $address = trim($_POST['address'] ?? $homeowner['address']);
    $vehicle_type = trim($_POST['vehicle_type'] ?? $homeowner['vehicle_type']);
    $color = trim($_POST['color'] ?? $homeowner['color']);
    $plate_number = trim($_POST['plate_number'] ?? $homeowner['plate_number']);

    // handle simple image uploads if present (optional)
    $owner_img = $homeowner['owner_img'] ?? null;
    $car_img   = $homeowner['car_img'] ?? null;
    
    $owners_upload_dir = __DIR__ . '/../../uploads/homeowners/';
    $vehicles_upload_dir = __DIR__ . '/../../uploads/vehicles/';
    
    // Ensure upload directories exist
    foreach ([$owners_upload_dir, $vehicles_upload_dir] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    $allowed = ['jpg','jpeg','png','webp','heic'];

    foreach (['owner_img','car_img'] as $field) {
        if (!empty($_FILES[$field]['name'])) {
            $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = date('Ymd_His_') . $field . '_' . time() . '.' . $ext;
                $upload_dir = $field === 'owner_img' ? $owners_upload_dir : $vehicles_upload_dir;
                $relative_path = $field === 'owner_img' ? 'homeowners/' : 'vehicles/';
                
                if (move_uploaded_file($_FILES[$field]['tmp_name'], $upload_dir . $filename)) {
                    if ($field === 'owner_img') {
                        $owner_img = $relative_path . $filename;
                    } else {
                        $car_img = $relative_path . $filename;
                    }
                }
            }
        }
    }

    try {
        $stmt = $pdo->prepare("UPDATE homeowners SET name=?, contact=?, address=?, vehicle_type=?, color=?, plate_number=?, owner_img=?, car_img=? WHERE id=?");
        $ok = $stmt->execute([$name,$contact,$address,$vehicle_type,$color,$plate_number,$owner_img,$car_img,$id]);
        echo json_encode(['success'=>$ok,'message'=>$ok ? 'Record updated' : 'Update failed']);
    } catch (Exception $e) {
        echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
    }
    exit;
}

// GET: render the edit form (fragment)
if (!empty($_GET['ajax'])):
?>
<form id="editForm" method="post" enctype="multipart/form-data" class="modern-form compact-form" action="homeowners/homeowner_edit.php?id=<?php echo intval($id); ?>">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <input type="hidden" name="id" value="<?php echo intval($id); ?>">

  <h3 class="text-xl font-bold text-gray-900 mb-2 text-center form-title">Edit Homeowner</h3>
  <p class="text-xs text-gray-500 mb-5 text-center">Update homeowner information and attached vehicle details.</p>

  <div class="grid grid-cols-2 gap-4 compact-grid">
    <!-- Row 1 -->
    <label class="block col-span-2 sm:col-span-1">
      <span class="text-sm font-medium text-gray-700 mb-1.5 block">Name</span>
      <input
        type="text"
        name="name"
        required
        value="<?php echo htmlspecialchars($homeowner['name']); ?>"
        placeholder="Full name"
        class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      >
    </label>

    <label class="block col-span-2 sm:col-span-1">
      <span class="text-sm font-medium text-gray-700 mb-1.5 block">Contact</span>
      <input
        type="text"
        name="contact"
        required
        value="<?php echo htmlspecialchars($homeowner['contact_number'] ?? ''); ?>"
        placeholder="Phone number"
        class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      >
    </label>

    <!-- Row 2 -->
    <label class="block col-span-2">
      <span class="text-sm font-medium text-gray-700 mb-1.5 block">Address</span>
      <textarea
        name="address"
        rows="2"
        required
        placeholder="Full address"
        class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
      ><?php echo htmlspecialchars($homeowner['address']); ?></textarea>
    </label>

    <!-- Row 3 -->
    <label class="block col-span-2 sm:col-span-1">
      <span class="text-sm font-medium text-gray-700 mb-1.5 block">Vehicle Type</span>
      <input
        type="text"
        name="vehicle_type"
        value="<?php echo htmlspecialchars($homeowner['vehicle_type']); ?>"
        placeholder="e.g., Sedan, SUV"
        class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      >
    </label>

    <label class="block col-span-2 sm:col-span-1">
      <span class="text-sm font-medium text-gray-700 mb-1.5 block">Color</span>
      <input
        type="text"
        name="color"
        value="<?php echo htmlspecialchars($homeowner['color']); ?>"
        placeholder="e.g., Blue, Red"
        class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      >
    </label>

    <!-- Row 4 -->
    <label class="block col-span-2 sm:col-span-1">
      <span class="text-sm font-medium text-gray-700 mb-1.5 block">Plate Number</span>
      <input
        type="text"
        name="plate_number"
        value="<?php echo htmlspecialchars($homeowner['plate_number']); ?>"
        placeholder="e.g., ABC123"
        class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      >
    </label>

    <!-- Images -->
    <div class="block col-span-2 sm:col-span-1">
      <label class="text-sm font-medium text-gray-700 mb-1.5 block">Owner Image</label>
      <?php if (!empty($homeowner['owner_img'])): ?>
        <?php
          $ownerImgPath = $homeowner['owner_img'];
          if (!preg_match('#^uploads/#i', $ownerImgPath)) {
              $ownerImgPath = 'uploads/' . ltrim($ownerImgPath, '/');
          }
        ?>
        <img src="../../<?php echo htmlspecialchars($ownerImgPath); ?>" class="preview-img rounded-lg border border-gray-300 mb-2" style="max-width:100px;display:block;" onerror="this.style.display='none'">
      <?php endif; ?>
      <input
        type="file"
        name="owner_img"
        accept="image/*"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
      <small class="text-gray-500 text-xs mt-1 block">Leave empty to keep current image.</small>
    </div>

    <div class="block col-span-2 sm:col-span-1">
      <label class="text-sm font-medium text-gray-700 mb-1.5 block">Car Image</label>
      <?php if (!empty($homeowner['car_img'])): ?>
        <?php
          $carImgPath = $homeowner['car_img'];
          if (!preg_match('#^uploads/#i', $carImgPath)) {
              $carImgPath = 'uploads/' . ltrim($carImgPath, '/');
          }
        ?>
        <img src="../../<?php echo htmlspecialchars($carImgPath); ?>" class="preview-img rounded-lg border border-gray-300 mb-2" style="max-width:100px;display:block;" onerror="this.style.display='none'">
      <?php endif; ?>
      <input
        type="file"
        name="car_img"
        accept="image/*"
        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
      <small class="text-gray-500 text-xs mt-1 block">Leave empty to keep current image.</small>
    </div>
  </div>

  <div class="flex items-center justify-end gap-3 pt-4 mt-6 border-t border-gray-200 form-actions">
    <button type="button" class="btn btn-secondary cancel-btn">Cancel</button>
    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
  </div>
</form>
<?php endif; ?>
