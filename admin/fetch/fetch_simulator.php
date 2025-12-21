<?php
require_once __DIR__ . '/../../includes/session_admin_unified.php';
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'admin'])) exit('Unauthorized');
require_once __DIR__ . '/../../db.php';

// Get all homeowners for dropdown
try {
    $stmt = $pdo->query("SELECT id, name, plate_number, vehicle_type FROM homeowners ORDER BY name ASC");
    $homeowners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $homeowners = [];
}

// Get recent simulations
try {
    $stmt = $pdo->query("
        SELECT rl.plate_number, rl.created_at, rl.status, h.name, h.vehicle_type
        FROM recent_logs rl
        LEFT JOIN homeowners h ON rl.plate_number = h.plate_number
        ORDER BY rl.created_at DESC, rl.log_id DESC
        LIMIT 10
    ");
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recent = [];
}
?>
<!-- Page Header -->
<div class="mb-6">
  <h1 class="text-3xl font-bold text-gray-900 mb-1 flex items-center gap-2">
    <span>🎮</span> RFID Scanner Simulator
  </h1>
  <p class="text-gray-600 text-sm">Test the RFID scanning system without physical hardware</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
  <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 simulator-panel">
    <h3 class="text-xl font-bold text-gray-800 mb-2">Scan Vehicle</h3>
    <p class="text-gray-600 mb-4 helper-text">Select a registered vehicle to simulate RFID scan</p>
    
    <select id="vehicleSelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent mb-4 sim-select">
      <option value="">-- Select Vehicle to Scan --</option>
      <?php foreach ($homeowners as $h): ?>
        <option value="<?php echo htmlspecialchars($h['plate_number']); ?>" 
                data-name="<?php echo htmlspecialchars($h['name']); ?>"
                data-type="<?php echo htmlspecialchars($h['vehicle_type']); ?>">
          <?php echo htmlspecialchars($h['plate_number']); ?> - <?php echo htmlspecialchars($h['name']); ?> (<?php echo htmlspecialchars($h['vehicle_type']); ?>)
        </option>
      <?php endforeach; ?>
    </select>
    
    <button id="scanBtn" class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white rounded-lg font-bold text-lg transition-all shadow-lg hover:shadow-xl hover:-translate-y-1 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 btn-scan" disabled>
      <span class="scan-icon text-2xl">📡</span> <span>Simulate Scan</span>
    </button>
    
    <div id="scanResult" class="mt-4 p-4 rounded-lg border-2 hidden scan-result">
      <div class="result-icon text-4xl mb-2"></div>
      <div class="result-text text-sm"></div>
    </div>
    
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4 simulator-info">
      <h4 class="font-bold text-blue-800 mb-2 flex items-center gap-2">ℹ️ How it works:</h4>
      <ul class="text-sm text-blue-700 space-y-1.5">
        <li class="flex items-start gap-2"><span class="text-blue-500">✓</span> <span>Select a vehicle from the dropdown</span></li>
        <li class="flex items-start gap-2"><span class="text-blue-500">✓</span> <span>Click "Simulate Scan" button</span></li>
        <li class="flex items-start gap-2"><span class="text-blue-500">✓</span> <span>System will create a log entry automatically</span></li>
        <li class="flex items-start gap-2"><span class="text-blue-500">✓</span> <span>Guard panel will update in real-time (2s delay)</span></li>
        <li class="flex items-start gap-2"><span class="text-blue-500">✓</span> <span>Perfect for testing without hardware!</span></li>
      </ul>
    </div>
  </div>
  
  <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200 recent-scans">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Recent Simulations</h3>
    <table>
      <thead>
        <tr>
          <th>Time</th>
          <th>Plate</th>
          <th>Owner</th>
          <th>Vehicle</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="recentScans">
        <?php if (empty($recent)): ?>
          <tr><td colspan="5" style="text-align:center;">No simulations yet</td></tr>
        <?php else: ?>
          <?php foreach ($recent as $r): ?>
            <tr>
              <td><?php echo date('H:i:s', strtotime($r['created_at'])); ?></td>
              <td><?php echo htmlspecialchars($r['plate_number']); ?></td>
              <td><?php echo htmlspecialchars($r['name'] ?? 'Unknown'); ?></td>
              <td><?php echo htmlspecialchars($r['vehicle_type'] ?? '-'); ?></td>
              <td>
                <span class="status-badge-sim status-<?php echo strtolower($r['status']); ?>">
                  <?php echo $r['status'] === 'IN' ? '🟢 IN' : '🔴 OUT'; ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<style>
.status-badge-sim {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  display: inline-block;
}

.status-badge-sim.status-in {
  background: #d5f4e6;
  color: #16a34a;
}

.status-badge-sim.status-out {
  background: #fee;
  color: #dc2626;
}

.simulator-container {
  display: grid;
  grid-template-columns: 400px 1fr;
  gap: 20px;
  margin-top: 20px;
}

.simulator-panel, .recent-scans {
  background: white;
  padding: 25px;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.simulator-panel h3 {
  margin-top: 0;
  color: #2c3e50;
}

.helper-text {
  color: #7f8c8d;
  font-size: 14px;
  margin-bottom: 20px;
}

.sim-select {
  width: 100%;
  padding: 12px;
  border: 2px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
  margin-bottom: 15px;
  transition: border-color 0.2s;
}

.sim-select:focus {
  outline: none;
  border-color: #3498db;
}

.btn-scan {
  width: 100%;
  padding: 15px;
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.btn-scan:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(52,152,219,0.3);
}

.btn-scan:disabled {
  background: #95a5a6;
  cursor: not-allowed;
  opacity: 0.6;
}

.scan-icon {
  font-size: 20px;
}

.scan-icon.scanning {
  animation: rotate 1s linear infinite;
}

@keyframes rotate {
  to { transform: rotate(360deg); }
}

.scan-result {
  margin-top: 20px;
  padding: 15px;
  border-radius: 8px;
  display: flex;
  gap: 12px;
  align-items: center;
  animation: slideIn 0.3s ease;
}

.scan-result.success {
  background: #d5f4e6;
  border-left: 4px solid #2ecc71;
}

.scan-result.error {
  background: #fadbd8;
  border-left: 4px solid #e74c3c;
}

.result-icon {
  font-size: 32px;
  flex-shrink: 0;
}

.result-text {
  flex: 1;
}

.result-text strong {
  display: block;
  margin-bottom: 5px;
  font-size: 16px;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.simulator-info {
  margin-top: 25px;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 8px;
  border-left: 4px solid #3498db;
}

.simulator-info h4 {
  margin-top: 0;
  color: #2c3e50;
}

.simulator-info ul {
  margin: 10px 0 0 0;
  padding-left: 20px;
}

.simulator-info li {
  margin: 5px 0;
  color: #555;
  font-size: 14px;
}

.recent-scans h3 {
  margin-top: 0;
  color: #2c3e50;
}

.recent-scans table {
  width: 100%;
  margin-top: 15px;
  border-collapse: collapse;
}

.recent-scans table th,
.recent-scans table td {
  padding: 10px;
  text-align: left;
  border-bottom: 1px solid #e0e0e0;
}

.recent-scans table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #2c3e50;
}

.recent-scans table tbody tr:hover {
  background: #f8f9fa;
}

@media (max-width: 900px) {
  .simulator-container {
    grid-template-columns: 1fr;
  }
}
</style>
