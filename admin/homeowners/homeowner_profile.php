<?php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../db.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'], true)) {
    http_response_code(403);
    exit('Unauthorized');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo '<p class="text-sm text-red-600">Invalid homeowner ID.</p>';
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM homeowners WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$homeowner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$homeowner) {
    echo '<p class="text-sm text-red-600">Homeowner not found.</p>';
    exit;
}

$fullName = trim((string)($homeowner['name'] ?? ''));
$nameParts = preg_split('/\s+/', $fullName, 2);
$firstName = htmlspecialchars($homeowner['first_name'] ?? ($nameParts[0] ?? ''));
$lastName = htmlspecialchars($homeowner['last_name'] ?? ($nameParts[1] ?? ''));

$normalizeUploadUrl = static function (?string $rawPath): string {
  $rawPath = trim((string)$rawPath);
  if ($rawPath === '') {
    return '';
  }

  // Normalize DB path variants: uploads/foo.jpg, /uploads/foo.jpg, homeowners/foo.jpg, foo.jpg
  $clean = ltrim($rawPath, '/');
  if (stripos($clean, 'uploads/') === 0) {
    $clean = substr($clean, strlen('uploads/'));
  }

  // Modal content is injected into /admin/admin_panel.php context, so uploads are at ../uploads/
  return '../uploads/' . ltrim($clean, '/');
};

$ownerImgUrl = $normalizeUploadUrl($homeowner['owner_img'] ?? '');
$carImgUrl = $normalizeUploadUrl($homeowner['car_img'] ?? '');

$ownedVehicles = [];
try {
  $vehicleColumns = $pdo->query("SHOW COLUMNS FROM vehicles")->fetchAll(PDO::FETCH_COLUMN);

  if (!empty($vehicleColumns)) {
    $idExpr = in_array('id', $vehicleColumns, true)
      ? 'v.id'
      : (in_array('vehicle_id', $vehicleColumns, true) ? 'v.vehicle_id' : 'NULL');

    $plateExpr = in_array('plate_number', $vehicleColumns, true) ? 'v.plate_number' : "''";
    $typeExpr = in_array('vehicle_type', $vehicleColumns, true) ? 'v.vehicle_type' : "''";
    $colorExpr = in_array('color', $vehicleColumns, true) ? 'v.color' : "''";
    $primaryExpr = in_array('is_primary', $vehicleColumns, true) ? 'v.is_primary' : '0';
    $imageExpr = in_array('vehicle_img', $vehicleColumns, true) ? 'v.vehicle_img' : 'NULL';

    $activeFilter = '';
    if (in_array('is_active', $vehicleColumns, true)) {
      $activeFilter = ' AND v.is_active = 1';
    } elseif (in_array('status', $vehicleColumns, true)) {
      $activeFilter = " AND v.status = 'active'";
    }

    $orderExpr = in_array('registered_at', $vehicleColumns, true)
      ? 'v.registered_at DESC'
      : (in_array('created_at', $vehicleColumns, true) ? 'v.created_at DESC' : 'id DESC');

    $vehiclesStmt = $pdo->prepare("\n            SELECT\n                {$idExpr} AS id,\n                {$plateExpr} AS plate_number,\n                {$typeExpr} AS vehicle_type,\n                {$colorExpr} AS color,\n                {$primaryExpr} AS is_primary,\n                {$imageExpr} AS vehicle_img\n            FROM vehicles v\n            WHERE v.homeowner_id = ?{$activeFilter}\n            ORDER BY {$primaryExpr} DESC, {$orderExpr}\n        ");
    $vehiclesStmt->execute([$id]);
    $ownedVehicles = $vehiclesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }
} catch (Exception $e) {
  $ownedVehicles = [];
}

if (empty($ownedVehicles) && !empty($homeowner['plate_number'])) {
  $ownedVehicles[] = [
    'id' => null,
    'plate_number' => (string)($homeowner['plate_number'] ?? ''),
    'vehicle_type' => (string)($homeowner['vehicle_type'] ?? ''),
    'color' => (string)($homeowner['color'] ?? ''),
    'is_primary' => 1,
    'vehicle_img' => (string)($homeowner['car_img'] ?? '')
  ];
}

foreach ($ownedVehicles as &$vehicleRow) {
  $vehicleRow['vehicle_img_url'] = $normalizeUploadUrl((string)($vehicleRow['vehicle_img'] ?? ''));
}
unset($vehicleRow);

if ($carImgUrl === '') {
  foreach ($ownedVehicles as $vehicleRow) {
    if (!empty($vehicleRow['vehicle_img_url'])) {
      $carImgUrl = (string)$vehicleRow['vehicle_img_url'];
      break;
    }
  }
}

$status = htmlspecialchars($homeowner['account_status'] ?? 'pending');
$statusColor = ['approved' => 'green', 'pending' => 'yellow', 'rejected' => 'red'][$status] ?? 'gray';
?>
<style>
  .homeowner-profile-modal {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
    max-height: calc(92vh - 2rem);
  }
  .homeowner-profile-modal .profile-scroll {
    overflow-y: auto;
    padding-right: 0.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.95rem;
  }
  .homeowner-profile-modal .profile-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    padding: 1rem;
  }
  .dark .homeowner-profile-modal .profile-card {
    background: rgba(30, 41, 59, 0.6);
    border-color: #334155;
  }
  .homeowner-profile-modal .profile-heading {
    font-size: 0.72rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 0.3rem;
  }
  .dark .homeowner-profile-modal .profile-heading {
    color: #94a3b8;
  }
  .homeowner-profile-modal .profile-value {
    color: #0f172a;
    font-size: 0.98rem;
    font-weight: 600;
    line-height: 1.45;
  }
  .dark .homeowner-profile-modal .profile-value {
    color: #f1f5f9;
  }
  .homeowner-profile-modal .profile-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    border-top: 1px solid #e2e8f0;
    padding-top: 1rem;
  }
  .dark .homeowner-profile-modal .profile-actions {
    border-top-color: #334155;
  }
  .homeowner-profile-modal .profile-actions .ta-btn {
    min-width: 180px;
  }
  .homeowner-profile-modal .photo-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
  }
  .homeowner-profile-modal .photo-block {
    min-width: 0;
  }
  .homeowner-profile-modal .photo-frame {
    width: 100%;
    aspect-ratio: 1 / 1;
    max-height: 320px;
    object-fit: cover;
    border-radius: 0.7rem;
  }
  .homeowner-profile-modal .vehicle-list-block {
    grid-column: 1 / -1;
    display: flex;
    flex-direction: column;
    min-width: 0;
  }
  .homeowner-profile-modal .vehicle-list {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    max-height: 320px;
    overflow-y: auto;
    padding-right: 0.2rem;
  }
  .homeowner-profile-modal .vehicle-list-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.6rem;
    padding: 0.7rem 0.8rem;
    border: 1px solid #dbe4ef;
    border-radius: 0.65rem;
    background: #ffffff;
  }
  .dark .homeowner-profile-modal .vehicle-list-item {
    background: rgba(15, 23, 42, 0.65);
    border-color: #334155;
  }
  .homeowner-profile-modal .vehicle-title {
    font-size: 0.87rem;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.2;
  }
  .dark .homeowner-profile-modal .vehicle-title {
    color: #f1f5f9;
  }
  .homeowner-profile-modal .vehicle-subtitle {
    margin-top: 0.2rem;
    font-size: 0.74rem;
    color: #64748b;
  }
  .dark .homeowner-profile-modal .vehicle-subtitle {
    color: #94a3b8;
  }
  .homeowner-profile-modal .vehicle-pill {
    display: inline-flex;
    align-items: center;
    font-size: 0.66rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    border-radius: 999px;
    padding: 0.2rem 0.5rem;
  }
  .homeowner-profile-modal .vehicle-pill.primary {
    background: #dbeafe;
    color: #1d4ed8;
  }
  .dark .homeowner-profile-modal .vehicle-pill.primary {
    background: rgba(59, 130, 246, 0.18);
    color: #93c5fd;
  }
  .homeowner-profile-modal .vehicle-image-link {
    font-size: 0.72rem;
    font-weight: 700;
    color: #2563eb;
    text-decoration: none;
    white-space: nowrap;
  }
  .homeowner-profile-modal .vehicle-image-link:hover {
    text-decoration: underline;
  }
  .dark .homeowner-profile-modal .vehicle-image-link {
    color: #60a5fa;
  }
  @media (min-width: 1200px) {
    .homeowner-profile-modal .photo-grid {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }
    .homeowner-profile-modal .vehicle-list-block {
      grid-column: auto;
    }
  }
  @media (max-width: 768px) {
    .homeowner-profile-modal .photo-grid {
      grid-template-columns: 1fr;
    }
    .homeowner-profile-modal .profile-actions {
      justify-content: stretch;
    }
    .homeowner-profile-modal .profile-actions .ta-btn {
      width: 100%;
      min-width: 0;
    }
  }
</style>

<div class="homeowner-profile-modal">
  <div class="border-b border-gray-200 dark:border-slate-700 pb-4 sticky top-0 bg-white dark:bg-slate-800 z-10">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Homeowner Profile</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ID #<?php echo htmlspecialchars((string)$id); ?> • Registered <?php echo htmlspecialchars(date('M d, Y', strtotime($homeowner['created_at']))); ?></p>
      </div>
      <span class="px-3 py-1 rounded-full text-xs font-semibold ta-badge ta-badge-<?php echo $statusColor; ?>">
        <?php echo ucfirst($status); ?>
      </span>
    </div>
  </div>

  <div class="profile-scroll">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
      <div class="profile-card space-y-3">
        <div>
          <div class="profile-heading">First Name</div>
          <p class="profile-value"><?php echo htmlspecialchars($firstName); ?></p>
        </div>
        <div>
          <div class="profile-heading">Plate Number</div>
          <p class="font-mono font-bold text-blue-600 dark:text-blue-300 mt-1 text-lg"><?php echo htmlspecialchars((string)($homeowner['plate_number'] ?? '')); ?></p>
        </div>
        <div>
          <div class="profile-heading">Vehicle Type</div>
          <p class="profile-value"><?php echo htmlspecialchars((string)($homeowner['vehicle_type'] ?? '')); ?></p>
        </div>
      </div>

      <div class="profile-card space-y-3">
        <div>
          <div class="profile-heading">Last Name</div>
          <p class="profile-value"><?php echo htmlspecialchars($lastName); ?></p>
        </div>
        <div>
          <div class="profile-heading">Vehicle Color</div>
          <p class="profile-value"><?php echo htmlspecialchars((string)($homeowner['color'] ?? '')); ?></p>
        </div>
        <div>
          <div class="profile-heading">Contact</div>
          <p class="profile-value"><?php echo htmlspecialchars((string)($homeowner['contact_number'] ?? '')); ?></p>
        </div>
      </div>
    </div>

    <div class="profile-card">
      <div class="profile-heading">Address</div>
      <p class="profile-value"><?php echo htmlspecialchars((string)($homeowner['address'] ?? '')); ?></p>
    </div>

    <div class="profile-card">
      <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-4 uppercase tracking-wide">Photographs</h4>
      <div class="photo-grid">
        <div class="photo-block group">
          <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Owner Photo</div>
          <?php if ($ownerImgUrl !== ''): ?>
            <img src="<?php echo htmlspecialchars($ownerImgUrl); ?>"
                 alt="Owner image"
                 class="photo-frame border-2 border-gray-200 dark:border-slate-600 hover:border-blue-400 dark:hover:border-blue-500 transition-colors cursor-pointer shadow-sm"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="photo-frame hidden bg-gray-200 dark:bg-slate-600 border-2 border-gray-300 dark:border-slate-500 items-center justify-center">
              <span class="text-sm text-gray-600 dark:text-gray-300">Image unavailable</span>
            </div>
          <?php else: ?>
            <div class="photo-frame bg-gray-200 dark:bg-slate-600 border-2 border-dashed border-gray-300 dark:border-slate-500 flex items-center justify-center">
              <span class="text-sm text-gray-500 dark:text-gray-400">No photo</span>
            </div>
          <?php endif; ?>
        </div>

        <div class="photo-block group">
          <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Vehicle Photo</div>
          <?php if ($carImgUrl !== ''): ?>
            <img src="<?php echo htmlspecialchars($carImgUrl); ?>"
                 alt="Vehicle image"
                 class="photo-frame border-2 border-gray-200 dark:border-slate-600 hover:border-blue-400 dark:hover:border-blue-500 transition-colors cursor-pointer shadow-sm"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="photo-frame hidden bg-gray-200 dark:bg-slate-600 border-2 border-gray-300 dark:border-slate-500 items-center justify-center">
              <span class="text-sm text-gray-600 dark:text-gray-300">Image unavailable</span>
            </div>
          <?php else: ?>
            <div class="photo-frame bg-gray-200 dark:bg-slate-600 border-2 border-dashed border-gray-300 dark:border-slate-500 flex items-center justify-center">
              <span class="text-sm text-gray-500 dark:text-gray-400">No photo</span>
            </div>
          <?php endif; ?>
        </div>

        <div class="vehicle-list-block">
          <div class="flex items-center justify-between gap-2 mb-2">
            <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">View All Vehicles</div>
            <span class="ta-badge neutral"><?php echo count($ownedVehicles); ?> Registered</span>
          </div>

          <?php if (empty($ownedVehicles)): ?>
            <div class="photo-frame bg-gray-100 dark:bg-slate-700 border-2 border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center">
              <span class="text-sm text-gray-500 dark:text-gray-400">No registered vehicles found</span>
            </div>
          <?php else: ?>
            <div class="vehicle-list">
              <?php foreach ($ownedVehicles as $vehicle): ?>
                <div class="vehicle-list-item">
                  <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                      <div class="vehicle-title"><?php echo htmlspecialchars((string)($vehicle['plate_number'] ?? '')); ?></div>
                      <?php if (!empty($vehicle['is_primary'])): ?>
                        <span class="vehicle-pill primary">Primary</span>
                      <?php endif; ?>
                    </div>
                    <div class="vehicle-subtitle">
                      <?php echo htmlspecialchars((string)($vehicle['vehicle_type'] ?? 'Unknown Type')); ?>
                      •
                      <?php echo htmlspecialchars((string)($vehicle['color'] ?? 'Unknown Color')); ?>
                    </div>
                  </div>
                  <?php if (!empty($vehicle['vehicle_img_url'])): ?>
                    <a class="vehicle-image-link" href="<?php echo htmlspecialchars((string)$vehicle['vehicle_img_url']); ?>" target="_blank" rel="noopener noreferrer">View image</a>
                  <?php else: ?>
                    <span class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">No image</span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="profile-actions">
    <button type="button" class="ta-btn ta-btn-secondary cancel-btn">Close</button>
  </div>
</div>
