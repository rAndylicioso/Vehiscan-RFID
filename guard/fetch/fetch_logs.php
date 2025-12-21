<?php
// Guard fetch logs with server-side pagination (matching admin panel architecture)
require_once __DIR__ . '/../../includes/session_guard.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guard') {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized access']));
}

require_once __DIR__ . '/../../db.php';

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

error_log("[GUARD_FETCH_LOGS] Page: $page, Per page: $per_page, Offset: $offset");

try {
    // Get total count
    $total = $pdo->query("SELECT COUNT(*) FROM recent_logs")->fetchColumn();
    $total_pages = ceil($total / $per_page);
    
    error_log("[GUARD_FETCH_LOGS] Total logs: $total, Total pages: $total_pages");
    
    // Get paginated logs with homeowner info AND visitor pass info
    $stmt = $pdo->prepare("
        SELECT 
            al.log_id,
            al.plate_number,
            al.status,
            al.created_at,
            DATE_FORMAT(al.created_at, '%h:%i %p') as time,
            h.name,
            h.vehicle_type,
            h.color,
            vp.id AS visitor_pass_id,
            vp.visitor_name,
            vp.purpose AS visitor_purpose,
            vp.status AS visitor_status
        FROM recent_logs al
        LEFT JOIN homeowners h ON al.plate_number = h.plate_number
        LEFT JOIN visitor_passes vp ON al.plate_number = vp.visitor_plate 
            AND vp.status = 'active'
        ORDER BY al.created_at DESC, al.log_id DESC
        LIMIT :limit OFFSET :offset
    ");
    
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("[GUARD_FETCH_LOGS] Fetched " . count($logs) . " logs");
    
} catch (Exception $e) {
    error_log("[GUARD_FETCH_LOGS] Error: " . $e->getMessage());
    $logs = [];
    $total = 0;
    $total_pages = 0;
}

// Get last seen log ID from localStorage for "new" badge detection
$lastSeenLogId = 0; // Client will handle this via JavaScript
?>

<!-- Logs Counter -->
<div class="logs-counter-container">
  <div id="logsCounter">
    <?php if ($total > 0): ?>
      Showing <?php echo number_format($offset + 1); ?> to <?php echo number_format(min($offset + $per_page, $total)); ?> of <?php echo number_format($total); ?> logs
    <?php else: ?>
      No logs to display
    <?php endif; ?>
  </div>
  <div class="text-xs text-gray-500">
    Page <?php echo $page; ?> of <?php echo $total_pages; ?>
  </div>
</div>

<!-- Logs Table -->
<div class="logs-table-container">
  <?php if (empty($logs)): ?>
    <div class="text-center py-12">
      <div class="empty-state">
        <div class="empty-state-icon text-6xl mb-4">📋</div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">No access logs found</h3>
        <p class="text-gray-500">Access logs will appear here when vehicles scan in/out.</p>
      </div>
    </div>
  <?php else: ?>
    <table class="logs-table">
      <thead>
        <tr>
          <th>Homeowner</th>
          <th>Plate Number</th>
          <th>Vehicle</th>
          <th>Color</th>
          <th>Status</th>
          <th>Time</th>
          <th>Duration</th>
        </tr>
      </thead>
      <tbody>
    <?php foreach ($logs as $log): ?>
      <?php
        $isEntry = $log['status'] === 'IN';
        $statusIcon = $isEntry ? '🟢' : '🔴';
        $statusClass = $isEntry ? 'status-in' : 'status-out';
        $statusText = $log['status'];
        
        $userName = $log['name'] ?? 'Unknown';
        $initial = strtoupper(substr($userName, 0, 1));
        
        // Calculate duration for IN status
        $durationText = '-';
        $durationClass = '';
        if ($isEntry && !empty($log['created_at'])) {
            $logTime = new DateTime($log['created_at']);
            $now = new DateTime();
            $diff = $now->getTimestamp() - $logTime->getTimestamp();
            $diffMins = floor($diff / 60);
            $diffHours = floor($diffMins / 60);
            $mins = $diffMins % 60;
            
            if ($diffHours > 0) {
                $durationText = "{$diffHours}h {$mins}m";
                if ($diffHours >= 8) {
                    $durationClass = 'duration-long';
                } elseif ($diffHours >= 4) {
                    $durationClass = 'duration-medium';
                } else {
                    $durationClass = 'duration-short';
                }
            } else {
                $durationText = "{$diffMins}m";
                $durationClass = 'duration-short';
            }
        }
      ?>
      <tr class="log-row<?php echo !empty($log['visitor_pass_id']) ? ' has-visitor-pass' : ''; ?>" 
          data-log-id="<?php echo $log['log_id']; ?>" 
          data-log-date="<?php echo $log['created_at']; ?>"
          data-plate="<?php echo htmlspecialchars($log['plate_number']); ?>"
          data-name="<?php echo htmlspecialchars($userName); ?>"
          data-status="<?php echo $log['status']; ?>"
          data-visitor="<?php echo !empty($log['visitor_pass_id']) ? '1' : '0'; ?>">
        <td>
          <div class="user-cell">
            <div class="user-avatar">
              <?php echo $initial; ?>
            </div>
            <div class="user-info">
              <div class="user-name">
                <?php echo htmlspecialchars($userName); ?>
                <?php if (!empty($log['visitor_pass_id'])): ?>
                  <span class="badge-visitor" title="Visitor Pass: <?php echo htmlspecialchars($log['visitor_name'] ?? 'N/A'); ?>">🎫</span>
                <?php endif; ?>
              </div>
              <?php if (!empty($log['visitor_name'])): ?>
                <div class="text-xs text-gray-500">Visitor: <?php echo htmlspecialchars($log['visitor_name']); ?></div>
              <?php endif; ?>
            </div>
          </div>
        </td>
        <td><span class="plate-number"><?php echo htmlspecialchars($log['plate_number'] ?? 'N/A'); ?></span></td>
        <td><?php echo htmlspecialchars($log['vehicle_type'] ?? 'N/A'); ?></td>
        <td><?php echo htmlspecialchars($log['color'] ?? 'N/A'); ?></td>
        <td>
          <span class="status-badge <?php echo $statusClass; ?>">
            <span class="status-icon"><?php echo $statusIcon; ?></span>
            <?php echo $statusText; ?>
          </span>
        </td>
        <td class="time-cell"><?php echo $log['time'] ?? '-'; ?></td>
        <td>
          <?php if ($durationText !== '-'): ?>
            <span class="duration-badge <?php echo $durationClass; ?>" title="Time inside: <?php echo $durationText; ?>">
              ⏱️ <?php echo $durationText; ?>
            </span>
          <?php else: ?>
            <span class="text-muted">-</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<!-- Pagination Controls (Shadcn Design) -->
<?php if ($total_pages > 1): ?>
<div class="pagination-wrapper">
  <nav class="pagination-nav" role="navigation" aria-label="Pagination">
    <!-- Previous Button -->
    <button 
      type="button" 
      class="pagination-btn pagination-prev <?php echo $page <= 1 ? 'disabled' : ''; ?>" 
      data-page="<?php echo $page - 1; ?>"
      <?php echo $page <= 1 ? 'disabled' : ''; ?>
      aria-label="Go to previous page">
      <svg class="pagination-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
      </svg>
      <span>Previous</span>
    </button>
    
    <!-- Page Numbers -->
    <div class="pagination-pages">
      <?php
      // Calculate visible page range
      $start = max(1, $page - 1);
      $end = min($total_pages, $page + 1);
      
      // Always show first page
      if ($start > 1): ?>
        <button type="button" class="pagination-page" data-page="1">1</button>
        <?php if ($start > 2): ?>
          <span class="pagination-ellipsis">...</span>
        <?php endif; ?>
      <?php endif; ?>
      
      <!-- Current page and surrounding pages -->
      <?php for ($i = $start; $i <= $end; $i++): ?>
        <button 
          type="button" 
          class="pagination-page <?php echo $i === $page ? 'active' : ''; ?>" 
          data-page="<?php echo $i; ?>"
          <?php echo $i === $page ? 'aria-current="page"' : ''; ?>>
          <?php echo $i; ?>
        </button>
      <?php endfor; ?>
      
      <!-- Always show last page -->
      <?php if ($end < $total_pages): ?>
        <?php if ($end < $total_pages - 1): ?>
          <span class="pagination-ellipsis">...</span>
        <?php endif; ?>
        <button type="button" class="pagination-page" data-page="<?php echo $total_pages; ?>"><?php echo $total_pages; ?></button>
      <?php endif; ?>
    </div>
    
    <!-- Next Button -->
    <button 
      type="button" 
      class="pagination-btn pagination-next <?php echo $page >= $total_pages ? 'disabled' : ''; ?>" 
      data-page="<?php echo $page + 1; ?>"
      <?php echo $page >= $total_pages ? 'disabled' : ''; ?>
      aria-label="Go to next page">
      <span>Next</span>
      <svg class="pagination-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
      </svg>
    </button>
  </nav>
</div>
<?php endif; ?>
