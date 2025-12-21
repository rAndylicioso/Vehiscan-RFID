/**
 * Real-Time Updates Module
 * Polls for new data and updates UI without full page refresh
 */

(function () {
  'use strict';

  const POLL_INTERVAL = 10000; // 10 seconds
  let lastLogId = 0;
  let lastApprovalCount = 0;
  let pollTimer = null;
  let isPolling = false;

  /**
   * Initialize real-time updates
   */
  function initRealTimeUpdates() {
    console.log('[RealTime] Initializing real-time updates...');

    // Start polling when on dashboard or logs page
    startPolling();

    // Stop polling when page is hidden (tab inactive)
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) {
        stopPolling();
      } else {
        startPolling();
      }
    });

    // Clean up on page unload
    window.addEventListener('beforeunload', stopPolling);
  }

  /**
   * Start polling for updates
   */
  function startPolling() {
    if (isPolling) return;

    isPolling = true;
    console.log('[RealTime] Started polling');

    // Initial check
    checkForUpdates();

    // Set up interval
    pollTimer = setInterval(checkForUpdates, POLL_INTERVAL);
  }

  /**
   * Stop polling
   */
  function stopPolling() {
    if (!isPolling) return;

    isPolling = false;
    if (pollTimer) {
      clearInterval(pollTimer);
      pollTimer = null;
    }
    console.log('[RealTime] Stopped polling');
  }

  /**
   * Check for new logs and pending approvals
   */
  async function checkForUpdates() {
    try {
      // Check for new access logs
      const logsResponse = await fetch('api/check_new_logs.php', {
        method: 'GET',
        credentials: 'same-origin'
      });

      if (logsResponse.ok) {
        const logsData = await logsResponse.json();
        if (logsData.success && logsData.latest_log_id > lastLogId) {
          const newCount = logsData.new_count || 1;

          // Only show notification if this is NOT the initial load
          // On first load, lastLogId is 0, so we just set it without notifying
          if (lastLogId !== 0) {
            showNewLogsNotification(newCount);
          }

          lastLogId = logsData.latest_log_id;

          // Auto-refresh if on logs page
          const currentPage = document.querySelector('.page-content.active');
          if (currentPage && currentPage.id === 'page-access-logs') {
            refreshAccessLogs();
          }
        }
      } else if (logsResponse.status === 403) {
        console.warn('[RealTime] Session expired, stopping polling');
        stopPolling();
        return;
      }

      // Check for new pending approvals
      const approvalsResponse = await fetch('api/check_pending_approvals.php', {
        method: 'GET',
        credentials: 'same-origin'
      });

      if (approvalsResponse.ok) {
        const approvalsData = await approvalsResponse.json();
        if (approvalsData.success && approvalsData.pending_count !== lastApprovalCount) {
          lastApprovalCount = approvalsData.pending_count;
          updateApprovalsBadge(approvalsData.pending_count);
        }
      } else if (approvalsResponse.status === 403) {
        console.warn('[RealTime] Session expired, stopping polling');
        stopPolling();
        return;
      }

    } catch (error) {
      console.error('[RealTime] Poll error:', error);
      // Don't spam console - stop polling on repeated errors
      if (error.name === 'TypeError') {
        stopPolling();
      }
    }
  }

  /**
   * Show notification for new logs
   */
  function showNewLogsNotification(count) {
    if (window.toast) {
      window.toast.info(`🆕 ${count} new access log${count > 1 ? 's' : ''}`);
    }

    // Play sound notification (optional)
    playNotificationSound();
  }

  /**
   * Update approvals badge in sidebar
   */
  function updateApprovalsBadge(count) {
    const badge = document.querySelector('[data-page="approvals"] .badge');
    if (badge) {
      if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'inline-flex';
      } else {
        badge.style.display = 'none';
      }
    }
  }

  /**
   * Refresh access logs table
   */
  function refreshAccessLogs() {
    const refreshBtn = document.querySelector('#refreshAccessLogs');
    if (refreshBtn) {
      refreshBtn.click();
    }
  }

  /**
   * Play notification sound - DISABLED due to CSP policy
   */
  function playNotificationSound() {
    // Disabled to prevent CSP violations
    // Audio notifications removed as they violate Content Security Policy
  }

  // Auto-initialize on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRealTimeUpdates);
  } else {
    initRealTimeUpdates();
  }

  // Export to global scope
  window.RealTimeUpdates = {
    start: startPolling,
    stop: stopPolling,
    checkNow: checkForUpdates
  };

})();
