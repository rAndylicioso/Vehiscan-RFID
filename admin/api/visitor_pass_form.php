<?php
// admin/api/visitor_pass_form.php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../includes/input_sanitizer.php';
require_once __DIR__ . '/qr_helper.php';

// Ensure CSRF token
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = InputSanitizer::generateCsrf();
$csrf = $_SESSION['csrf_token'];

// POST create/edit (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $posted = InputSanitizer::post('csrf', 'string');
    if (!InputSanitizer::validateCsrf($posted)) {
        echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit;
    }
    
    // Sanitize all inputs
    $homeowner_id = InputSanitizer::post('homeowner_id', 'int');
    $visitor_name = InputSanitizer::post('visitor_name', 'string');
    $visitor_plate = strtoupper(InputSanitizer::post('visitor_plate', 'string'));
    $purpose = InputSanitizer::post('purpose', 'string');
    $valid_from = InputSanitizer::post('valid_from', 'string');
    $valid_until = InputSanitizer::post('valid_until', 'string');
    $is_recurring = isset($_POST['is_recurring']) ? 1 : 0;
    $status = InputSanitizer::post('status', 'string', 'active');

    if (!$visitor_name || !$visitor_plate || !$purpose || !$valid_from || !$valid_until) {
        echo json_encode(['success'=>false,'message'=>'All required fields must be filled']); exit;
    }
    
    try {
        // Generate unique QR token
        $qrToken = generateSecureToken();
        
        // Insert with QR token
        $stmt = $pdo->prepare("INSERT INTO visitor_passes (homeowner_id, visitor_name, visitor_plate, purpose, valid_from, valid_until, is_recurring, status, qr_token) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$homeowner_id, $visitor_name, $visitor_plate, $purpose, $valid_from, $valid_until, $is_recurring, $status, $qrToken]);
        
        $passId = $pdo->lastInsertId();
        
        // Generate QR code
        $qrCode = generateVisitorPassQR($passId, $qrToken, $pdo);
        
        if ($qrCode) {
            // Update with QR code
            $stmt = $pdo->prepare("UPDATE visitor_passes SET qr_code = ? WHERE id = ?");
            $stmt->execute([$qrCode, $passId]);
        }
        
        echo json_encode(['success'=>true,'message'=>'Visitor pass created successfully with QR code']);
    } catch (Exception $e) {
        error_log("Visitor pass creation error: " . $e->getMessage());
        echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
    }
    exit;
}

// GET => render form fragment
$homeowners = $pdo->query("SELECT id, name FROM homeowners ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Set default times (now and +2 hours)
$defaultFrom = date('Y-m-d\TH:i');
$defaultUntil = date('Y-m-d\TH:i', strtotime('+2 hours'));
?>
<form id="visitorPassForm" class="space-y-6 modern-form compact-form" action="api/visitor_pass_form.php" method="post">
  <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
  <h3 class="text-2xl font-bold text-gray-800 mb-6 pb-3 border-b border-gray-200 flex items-center gap-2 form-title">
    <span class="text-3xl">🎫</span>
    <span>Create Visitor Pass</span>
  </h3>
  
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 compact-grid">
    <label class="block md:col-span-2">
      <span class="text-sm font-semibold text-gray-700 mb-1 block">Linked Homeowner <span class="text-gray-500 text-xs">(Optional)</span></span>
      <select name="homeowner_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        <option value="">-- Select Homeowner (Optional) --</option>
        <?php foreach ($homeowners as $h): ?>
          <option value="<?php echo $h['id']; ?>"><?php echo htmlspecialchars($h['name']); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    
    <label class="block">
      <span class="text-sm font-semibold text-gray-700 mb-1 block">Visitor Name <span class="text-red-500">*</span></span>
      <input type="text" id="visitor_name" name="visitor_name" required placeholder="John Doe" autocomplete="off" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
    </label>
    
    <label class="block">
      <span class="text-sm font-semibold text-gray-700 mb-1 block">Vehicle Plate <span class="text-red-500">*</span></span>
      <input type="text" id="visitor_plate" name="visitor_plate" required placeholder="ABC-1234" autocomplete="off" class="w-full px-4 py-2 border border-gray-300 rounded-lg uppercase focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
    </label>
    
    <label class="block md:col-span-2">
      <span class="text-sm font-semibold text-gray-700 mb-1 block">Purpose of Visit <span class="text-red-500">*</span></span>
      <input type="text" name="purpose" required placeholder="Guest, Delivery, Maintenance, etc." list="purposeSuggestions" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
      <datalist id="purposeSuggestions">
        <option value="Guest Visit">
        <option value="Delivery">
        <option value="Maintenance">
        <option value="Contractor">
        <option value="Family Visit">
        <option value="Service Provider">
      </datalist>
    </label>
    
    <label class="block">
      <span class="text-sm font-semibold text-gray-700 mb-1 block">Valid From <span class="text-red-500">*</span></span>
      <input type="datetime-local" id="valid_from" name="valid_from" required value="<?php echo $defaultFrom; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
    </label>
    
    <label class="block">
      <span class="text-sm font-semibold text-gray-700 mb-1 block">Valid Until <span class="text-red-500">*</span></span>
      <input type="datetime-local" id="valid_until" name="valid_until" required value="<?php echo $defaultUntil; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
    </label>
    
    <div class="md:col-span-2">
      <div class="text-sm font-semibold text-gray-700 mb-2">Quick Duration:</div>
      <div class="flex gap-2 flex-wrap">
        <button type="button" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors btn btn-secondary btn-sm quick-duration" data-hours="2">2 Hours</button>
        <button type="button" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors btn btn-secondary btn-sm quick-duration" data-hours="4">4 Hours</button>
        <button type="button" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors btn btn-secondary btn-sm quick-duration" data-hours="8">8 Hours</button>
        <button type="button" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors btn btn-secondary btn-sm quick-duration" data-days="1">1 Day</button>
        <button type="button" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors btn btn-secondary btn-sm quick-duration" data-days="3">3 Days</button>
        <button type="button" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors btn btn-secondary btn-sm quick-duration" data-days="7">1 Week</button>
      </div>
    </div>
    
    <label class="flex items-center gap-2 md:col-span-2 cursor-pointer">
      <input type="checkbox" name="is_recurring" value="1" class="w-4 h-4 text-blue-500 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
      <span class="text-sm text-gray-700">Recurring Pass (Auto-renew daily/weekly for regular visitors)</span>
    </label>
  </div>
  
  <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 form-actions">
    <button type="button" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-semibold transition-colors btn warn cancel-btn">Cancel</button>
    <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white rounded-lg font-semibold transition-all shadow-md hover:shadow-lg btn btn-success">✓ Create Pass</button>
  </div>
</form>

<style>
.btn-secondary {
  background: #6c757d;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}
.btn-secondary:hover {
  background: #5a6268;
}
.btn-sm {
  padding: 4px 10px;
  font-size: 12px;
}
</style>
