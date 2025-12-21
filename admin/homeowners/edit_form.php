<?php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../db.php';

$csrf = $_SESSION['csrf_token'] ?? '';
if (!$csrf) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
  $csrf = $_SESSION['csrf_token'];
}

$id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM homeowners WHERE id = ?");
$stmt->execute([$id]);
$h = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$h): ?>
  <p>Record not found.</p>
<?php else: ?>
<form id="editHomeownerForm">
  <input type="hidden" name="id" value="<?= htmlspecialchars($h['id']) ?>">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

  <label>Name
    <input type="text" name="name" value="<?= htmlspecialchars($h['name']) ?>" required>
  </label>
  <label>Plate Number
    <input type="text" name="plate_number" value="<?= htmlspecialchars($h['plate_number']) ?>" required>
  </label>
  <label>Vehicle Type
    <input type="text" name="vehicle_type" value="<?= htmlspecialchars($h['vehicle_type']) ?>">
  </label>
  <label>Contact
    <input type="text" name="contact" value="<?= htmlspecialchars($h['contact']) ?>">
  </label>
  <label>Address
    <textarea name="address"><?= htmlspecialchars($h['address']) ?></textarea>
  </label>

  <div style="display:flex;justify-content:end;gap:10px;margin-top:10px;">
    <button type="submit" class="btn">💾 Save</button>
    <button type="button" class="btn warn cancel-btn">Cancel</button>
  </div>
</form>
<?php endif; ?>
