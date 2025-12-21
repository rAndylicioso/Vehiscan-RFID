/* admin/admin_panel.js - COMPLETE VERSION WITH SHADCN SIDEBAR */

// SweetAlert2 Fallback - Must be defined before DOMContentLoaded
if (typeof Swal === 'undefined') {
  console.warn('[ADMIN] SweetAlert2 not loaded, using fallback alert/confirm');
  window.Swal = {
    fire: function (options) {
      const isConfirm = options.showCancelButton || options.showConfirmButton !== false;
      const message = options.html || options.text || options.title || '';
      if (isConfirm) {
        return Promise.resolve({ isConfirmed: confirm(message), value: true });
      } else {
        alert(options.icon ? options.icon.toUpperCase() + ': ' + message : message);
        return Promise.resolve({ isConfirmed: true });
      }
    },
    mixin: function () { return this; },
    stopTimer: function () { },
    resumeTimer: function () { },
    showValidationError: function (msg) { alert('Validation Error: ' + msg); },
    showValidationMessage: function (msg) { alert('Validation: ' + msg); }
  };
} else {
  console.log('[ADMIN] SweetAlert2 loaded successfully');
}

document.addEventListener("DOMContentLoaded", () => {
  console.log('[ADMIN] DOMContentLoaded fired');

  const contentArea = document.getElementById("content-area");
  const menuLinks = document.querySelectorAll(".menu-item[data-page]");
  const liveTime = document.getElementById("liveTime");
  const signOutBtn = document.getElementById("signOutBtn");
  const backupBtn = document.getElementById("backupBtn");
  const modalEl = document.getElementById("editModal");
  const modalBody = document.getElementById("modal-body");
  const pageTitle = document.getElementById("page-title");
  const csrf = window.__ADMIN_CSRF__;
  let currentPage = "dashboard"; // Track current page for reload after form submit

  console.log('[ADMIN] Elements found:', {
    contentArea: !!contentArea,
    menuLinks: menuLinks.length,
    pageTitle: !!pageTitle,
    signOutBtn: !!signOutBtn,
    backupBtn: !!backupBtn
  });

  /* ---------- Dark Mode  // Dark mode is handled by admin-dark-mode.js
  // Removed duplicate handler to prevent conflicts

  // === SIDEBAR TOGGLE ===Sidebar Toggle ---------- */
  const sidebar = document.getElementById('sidebar');
  const sidebarToggle = document.getElementById('sidebar-toggle');
  const sidebarTexts = document.querySelectorAll('.sidebar-text');
  let sidebarOpen = localStorage.getItem('sidebarOpen') !== 'false'; // Default true

  console.log('[SIDEBAR] Sidebar elements:', {
    sidebar: !!sidebar,
    sidebarToggle: !!sidebarToggle,
    sidebarTexts: sidebarTexts.length,
    sidebarOpen: sidebarOpen
  });

  // Initialize sidebar state
  if (sidebar) {
    if (!sidebarOpen) {
      sidebar.classList.remove('sidebar-open');
      sidebar.classList.add('sidebar-closed');
    }
    console.log('[SIDEBAR] Initial classes:', sidebar.className);
  }

  const hamburgerIcon = document.getElementById('hamburger-icon');

  if (sidebarToggle) {
    sidebarToggle.addEventListener('click', (e) => {
      console.log('[SIDEBAR] Toggle clicked!');
      e.preventDefault();
      e.stopPropagation();

      sidebarOpen = !sidebarOpen;
      localStorage.setItem('sidebarOpen', sidebarOpen);

      if (sidebarOpen) {
        sidebar.classList.remove('sidebar-closed');
        sidebar.classList.add('sidebar-open');
        if (hamburgerIcon) hamburgerIcon.style.transform = 'rotate(0deg)';
        console.log('[SIDEBAR] Opened - classes:', sidebar.className);
      } else {
        sidebar.classList.remove('sidebar-open');
        sidebar.classList.add('sidebar-closed');
        if (hamburgerIcon) hamburgerIcon.style.transform = 'rotate(90deg)';
        console.log('[SIDEBAR] Closed - classes:', sidebar.className);
      }
    });
    console.log('[SIDEBAR] Toggle listener attached');
  } else {
    console.error('[SIDEBAR] Toggle button not found!');
  }

  /* ---------- Mobile Menu Toggle ---------- */
  const mobileMenuBtn = document.getElementById('mobile-menu-btn');
  const mobileOverlay = document.getElementById('mobile-overlay');

  if (mobileMenuBtn && mobileOverlay && sidebar) {
    mobileMenuBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      sidebar.classList.toggle('mobile-open');
      mobileOverlay.classList.toggle('active');
      console.log('[MOBILE] Menu toggled');
    });

    mobileOverlay.addEventListener('click', () => {
      sidebar.classList.remove('mobile-open');
      mobileOverlay.classList.remove('active');
      console.log('[MOBILE] Overlay clicked - menu closed');
    });

    // Close mobile menu when a menu item is clicked
    menuLinks.forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
          sidebar.classList.remove('mobile-open');
          mobileOverlay.classList.remove('active');
        }
      });
    });
  }

  /* ---------- User Dropdown ---------- */
  const userTrigger = document.getElementById('user-trigger');
  const userDropdown = document.getElementById('user-dropdown');
  const userChevron = document.getElementById('user-chevron');

  console.log('[USER-DROPDOWN] Elements found:', {
    trigger: !!userTrigger,
    dropdown: !!userDropdown,
    chevron: !!userChevron
  });

  // Position dropdown dynamically
  function positionDropdown() {
    if (!userTrigger || !userDropdown) {
      console.log('[USER-DROPDOWN] Missing elements, cannot position');
      return;
    }

    const triggerRect = userTrigger.getBoundingClientRect();
    const sidebar = document.getElementById('sidebar');
    const sidebarRect = sidebar ? sidebar.getBoundingClientRect() : null;
    const gap = 8; // 0.5rem gap

    // Position relative to sidebar width when it's visible
    const left = sidebarRect ? sidebarRect.left : triggerRect.left;
    const bottom = window.innerHeight - triggerRect.top + gap;
    const width = sidebarRect ? sidebarRect.width : triggerRect.width;

    console.log('[USER-DROPDOWN] Positioning:', {
      left,
      bottom,
      width,
      sidebarWidth: sidebarRect?.width,
      triggerLeft: triggerRect.left
    });

    // Use bottom positioning and align with sidebar
    userDropdown.style.left = `${left}px`;
    userDropdown.style.bottom = `${bottom}px`;
    userDropdown.style.top = 'auto';
    userDropdown.style.width = `${width}px`;
    userDropdown.style.display = 'block';
  }

  userTrigger?.addEventListener('click', (e) => {
    console.log('[USER-DROPDOWN] Trigger clicked!');
    e.stopPropagation();
    const isHidden = userDropdown.style.display === 'none' || userDropdown.style.display === '';

    console.log('[USER-DROPDOWN] Current state:', { isHidden, display: userDropdown.style.display });

    if (isHidden) {
      positionDropdown();
      userDropdown.style.display = 'block';
      console.log('[USER-DROPDOWN] Dropdown opened');
    } else {
      userDropdown.style.display = 'none';
      console.log('[USER-DROPDOWN] Dropdown closed');
    }

    if (userChevron) {
      // When closed: point up (180deg), When open: point down (0deg)
      userChevron.style.transform = isHidden ? 'rotate(0deg)' : 'rotate(180deg)';
    }
  });

  // Close dropdown when clicking outside
  document.addEventListener('click', () => {
    if (userDropdown.style.display === 'block') {
      userDropdown.style.display = 'none';
      if (userChevron) userChevron.style.transform = 'rotate(180deg)';
    }
  });

  userDropdown?.addEventListener('click', (e) => {
    e.stopPropagation();
  });

  // Reposition on window resize
  window.addEventListener('resize', () => {
    if (userDropdown.style.display === 'block') {
      positionDropdown();
    }
  });

  /* ---------- Global Session Expiration Handler ---------- */
  const originalFetch = window.fetch;
  window.fetch = async function (...args) {
    const response = await originalFetch.apply(this, args);

    // Check for session expiration on any AJAX request
    if (response.status === 403) {
      try {
        const clone = response.clone();
        const json = await clone.json();
        if (json.error === 'Session expired' && json.redirect) {
          await Swal.fire({
            title: 'Session Expired',
            text: 'Your session has expired. Please login again.',
            icon: 'warning',
            confirmButtonText: 'Login',
            allowOutsideClick: false,
            heightAuto: false
          });
          window.location.href = json.redirect;
          return new Response(JSON.stringify(json), { status: 403 });
        }
      } catch (e) {
        // Not JSON or other error, continue with original response
      }
    }

    return response;
  };

  /* ---------- Toast Notifications (replacing Growl) ---------- */
  function showGrowl(msg, type = "success") {
    // Use SweetAlert2 toast instead of window.toast
    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
      }
    });

    let icon = 'success';
    if (type === 'error') {
      icon = 'error';
    } else if (type === 'warning') {
      icon = 'warning';
    } else if (type === 'info') {
      icon = 'info';
    }

    Toast.fire({
      icon: icon,
      title: msg
    });
  }

  /* ---------- Session Activity Tracker ---------- */
  let sessionWarningShown = false;
  let sessionTimeout = 1800000; // 30 minutes in milliseconds
  let warningTime = 1500000; // Show warning at 25 minutes (5 min before expiry)
  let lastActivity = Date.now();

  // Reset activity timer on user interaction
  function resetActivityTimer() {
    lastActivity = Date.now();
    sessionWarningShown = false;
  }

  // Track user activity
  document.addEventListener('click', resetActivityTimer);
  document.addEventListener('keypress', resetActivityTimer);
  document.addEventListener('mousemove', resetActivityTimer);

  // Check session status every minute
  setInterval(() => {
    const timeSinceActivity = Date.now() - lastActivity;

    // Show warning 5 minutes before expiry
    if (!sessionWarningShown && timeSinceActivity > warningTime) {
      sessionWarningShown = true;
      Swal.fire({
        title: 'Session Expiring Soon',
        text: 'Your session will expire in 5 minutes due to inactivity. Click OK to stay logged in.',
        icon: 'warning',
        confirmButtonText: 'Stay Logged In',
        heightAuto: false
      }).then((result) => {
        if (result.isConfirmed) {
          resetActivityTimer();
          // Ping server to refresh session
          fetch('fetch/keep_alive.php', {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Content-Type': 'application/json'
            }
          }).then(response => response.json())
            .then(data => {
              if (data && data.success) {
                showGrowl('Session refreshed successfully', 'success');
              }
            })
            .catch(() => {
              showGrowl('Failed to refresh session', 'error');
            });
        }
      });
    }
  }, 60000); // Check every minute

  /* ---------- Live Clock ---------- */
  function updateTime() {
    const now = new Date();
    if (liveTime)
      liveTime.textContent = now.toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
        second: "2-digit",
      });
  }
  updateTime();
  setInterval(updateTime, 1000);

  /* ---------- Navigation ---------- */
  console.log('[NAV] Setting up navigation for', menuLinks.length, 'menu items');

  menuLinks.forEach((link, index) => {
    console.log(`[NAV] Menu item ${index}:`, {
      text: link.textContent.trim(),
      page: link.dataset.page,
      href: link.href
    });

    link.addEventListener("click", (e) => {
      console.log('[NAV] Menu clicked:', link.dataset.page);
      e.preventDefault();
      e.stopPropagation();

      const page = link.dataset.page;

      if (!page) {
        console.error('[NAV] No data-page attribute found!');
        return;
      }

      // Update active state
      menuLinks.forEach((l) => l.classList.remove("active"));
      link.classList.add("active");
      console.log('[NAV] Active class set on:', page);

      // Update page title
      const pageNames = {
        'dashboard': 'Dashboard',
        'manage': 'Manage Records',
        'logs': 'Access Logs',
        'audit': 'Audit Logs',
        'simulator': 'RFID Simulator',
        'visitors': 'Visitor Passes',
        'employees': 'Employee Management'
      };
      if (pageTitle && pageNames[page]) {
        pageTitle.textContent = pageNames[page];
        console.log('[NAV] Page title updated to:', pageNames[page]);
      }

      loadPage(page);
    });
  });

  console.log('[NAV] All navigation listeners attached');

  /* ---------- Initial Page ---------- */
  loadPage("dashboard");

  /* ---------- Logout ---------- */
  signOutBtn?.addEventListener("click", async () => {
    const result = await Swal.fire({
      title: "Sign out?",
      text: "You will be logged out of the admin panel.",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Yes, Logout",
      cancelButtonText: "Cancel",
      confirmButtonColor: "#3498db",
      heightAuto: false,
    });

    if (!result.isConfirmed) return;

    showGrowl("Logging out...", "success");

    try {
      const res = await fetch("../auth/logout.php", {
        method: "POST",
        headers: { "X-Requested-With": "XMLHttpRequest" },
        credentials: "same-origin",
      });

      if (res.ok) {
        const data = await res.json().catch(() => null);
        if (data && data.success) {
          await Swal.fire({
            title: "Logged out",
            text: "Redirecting to login...",
            icon: "success",
            timer: 900,
            showConfirmButton: false,
            heightAuto: false
          });
          setTimeout(() => {
            window.location.href = "../auth/login.php";
          }, 900);
          return;
        }
      }

      window.location.href = "../auth/logout.php";
    } catch (err) {
      console.error(err);
      window.location.href = "../auth/logout.php";
    }
  });

  /* ---------- Load Page Fragment ---------- */
  async function loadPage(page) {
    currentPage = page; // Track which page we're on

    // Fade out animation
    contentArea.style.opacity = '0';
    contentArea.style.transform = 'translateY(10px)';

    await new Promise(resolve => setTimeout(resolve, 200));

    contentArea.innerHTML = `
      <div class="p-6 space-y-6 animate-pulse">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="h-32 bg-gray-200 dark:bg-slate-700 rounded-xl"></div>
          <div class="h-32 bg-gray-200 dark:bg-slate-700 rounded-xl"></div>
          <div class="h-32 bg-gray-200 dark:bg-slate-700 rounded-xl"></div>
        </div>
        <div class="h-64 bg-gray-200 dark:bg-slate-700 rounded-xl"></div>
        <div class="space-y-4">
          <div class="h-12 bg-gray-200 dark:bg-slate-700 rounded-lg"></div>
          <div class="h-12 bg-gray-200 dark:bg-slate-700 rounded-lg"></div>
          <div class="h-12 bg-gray-200 dark:bg-slate-700 rounded-lg"></div>
        </div>
      </div>
    `;

    // Fade in loading state
    contentArea.style.opacity = '1';
    contentArea.style.transform = 'translateY(0)';

    try {
      const res = await fetch(`fetch/fetch_${page}.php`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });

      // Session expiration is now handled by global fetch interceptor
      // Just check if response is ok
      if (!res.ok) {
        // If it's 403, the global interceptor already handled it
        if (res.status === 403) return;
        throw new Error("fetch failed");
      }

      const html = await res.text();

      // Fade out before changing content
      contentArea.style.opacity = '0';
      await new Promise(resolve => setTimeout(resolve, 150));

      contentArea.innerHTML = html;

      // Execute any inline scripts in the loaded content
      const scripts = contentArea.querySelectorAll('script');
      scripts.forEach(oldScript => {
        try {
          const newScript = document.createElement('script');

          // Copy attributes
          Array.from(oldScript.attributes).forEach(attr => {
            newScript.setAttribute(attr.name, attr.value);
          });

          // Copy content
          newScript.textContent = oldScript.textContent;

          // Replace the script if it has a parent (avoid orphaned scripts)
          if (oldScript.parentNode) {
            oldScript.parentNode.replaceChild(newScript, oldScript);
          }
        } catch (error) {
          console.error('Error executing script:', error);
          // If replaceChild fails, try direct execution
          try {
            eval(oldScript.textContent);
          } catch (evalError) {
            console.error('Error evaluating script:', evalError);
          }
        }
      });

      // Fade in new content
      contentArea.style.opacity = '1';

      console.log(`[Page Load] ${page} loaded successfully`);

      // Attach page-specific controls
      if (page === "manage") attachManageControls();
      if (page === "logs") attachLogsControls();
      if (page === "dashboard") attachDashboardControls();
      if (page === "simulator") attachRFIDSimulatorControls();
      if (page === "visitors") attachVisitorsControls();
      if (page === "audit") attachAuditControls();
      if (page === "reports") attachReportsControls();
      if (page === "employees") attachEmployeesControls();
      if (page === "approvals") attachApprovalsControls();

    } catch (err) {
      console.error(err);
      contentArea.innerHTML = "<p style='color:red'>Failed to load page</p>";
      showGrowl("Failed to load page", "error");
    }
  }

  /* ---------- RFID Simulator Controls ---------- */
  function attachRFIDSimulatorControls() {
    console.log('[RFID] Attaching controls...');

    // Poll for required DOM elements because fragment is loaded asynchronously
    const pollInterval = 120; // ms
    const maxAttempts = 60;
    let attempts = 0;

    const poll = setInterval(() => {
      attempts++;
      const vehicleSelect = document.getElementById('vehicleSelect');
      const scanBtn = document.getElementById('scanBtn');
      const scanResult = document.getElementById('scanResult');
      const recentScans = document.getElementById('recentScans');

      if (!vehicleSelect || !scanBtn) {
        if (attempts >= maxAttempts) {
          clearInterval(poll);
          console.warn('[RFID] Simulator elements not found after polling');
        }
        return; // keep waiting
      }

      clearInterval(poll);
      console.log('[RFID] Elements found - initializing simulator controls');

      // Helper to update scan button visuals
      const updateScanButtonStyle = () => {
        if (!scanBtn.disabled) {
          scanBtn.style.background = 'linear-gradient(135deg, #3498db, #2980b9)';
          scanBtn.style.cursor = 'pointer';
          scanBtn.style.opacity = '1';
        } else {
          scanBtn.style.background = '#95a5a6';
          scanBtn.style.cursor = 'not-allowed';
          scanBtn.style.opacity = '0.6';
        }
      };

      scanBtn.disabled = !vehicleSelect.value;
      updateScanButtonStyle();

      vehicleSelect.addEventListener('change', function () {
        scanBtn.disabled = !this.value;
        updateScanButtonStyle();
      });

      scanBtn.addEventListener('click', async function () {
        const plate = vehicleSelect.value;
        if (!plate) {
          showGrowl('Please select a vehicle first', 'error');
          return;
        }

        scanBtn.disabled = true;
        scanBtn.innerHTML = '<span class="scan-icon scanning">🔄</span> Scanning...';

        await new Promise((r) => setTimeout(r, 700 + Math.random() * 900));

        try {
          const res = await fetch('simulation/simulate_rfid_scan.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'plate_number=' + encodeURIComponent(plate) + '&csrf=' + encodeURIComponent(csrf),
            credentials: 'same-origin'
          });

          // Check if response is actually JSON
          const contentType = res.headers.get('content-type');
          if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server returned non-JSON response. Session may have expired.');
          }

          let json;
          try {
            json = await res.json();
          } catch (err) {
            console.error('JSON parse error:', err);
            json = { success: false, message: 'Invalid JSON response from server' };
          }

          // Ensure json is always an object
          if (!json || typeof json !== 'object') {
            json = { success: false, message: 'Invalid response format' };
          }

          // Check for session expiration
          if (json.error && json.redirect) {
            window.location.href = json.redirect;
            return;
          }

          if (scanResult) scanResult.style.display = 'block';

          if (json.success === true) {
            if (scanResult) {
              const direction = json.direction || 'IN';
              const isEntry = direction === 'IN';
              const icon = isEntry ? '🟢' : '🔴';
              const statusText = isEntry ? 'ENTRY LOGGED' : 'EXIT LOGGED';
              const statusColor = isEntry ? '#16a34a' : '#dc2626';

              scanResult.className = 'scan-result success';
              scanResult.querySelector('.result-icon').textContent = icon;
              scanResult.querySelector('.result-text').innerHTML = `
                <strong style="color: ${statusColor}">${statusText}</strong><br>
                Plate: ${json.plate || 'N/A'}<br>
                Owner: ${json.name || 'N/A'}<br>
                Status: ${json.status || 'N/A'}
              `;
            }
            showGrowl('Scan successful!', 'success');
            setTimeout(() => refreshRecentScans(), 500);
          } else {
            if (scanResult) {
              scanResult.className = 'scan-result error';
              scanResult.querySelector('.result-icon').textContent = '❌';
              scanResult.querySelector('.result-text').innerHTML = `<strong>Scan Failed</strong><br>${json.message || json.error || 'Unknown error'}`;
            }
            showGrowl('Scan failed: ' + (json.message || json.error || 'Unknown'), 'error');
          }
        } catch (err) {
          console.error('[RFID] Error during scan:', err);
          if (scanResult) {
            scanResult.style.display = 'block';
            scanResult.className = 'scan-result error';
            scanResult.querySelector('.result-icon').textContent = '❌';
            scanResult.querySelector('.result-text').textContent = err.message || 'Connection error';
          }
          showGrowl('Connection error', 'error');
        } finally {
          scanBtn.disabled = false;
          scanBtn.innerHTML = '<span class="scan-icon">📡</span> Simulate Scan';
        }
      });

      async function refreshRecentScans() {
        try {
          const res = await fetch('simulation/get_recent_simulations.php', { credentials: 'same-origin' });
          let json;
          try {
            json = await res.json();
          } catch (err) {
            json = { success: false };
          }
          if (json && json.success && Array.isArray(json.scans) && recentScans) {
            if (json.scans.length > 0) {
              recentScans.innerHTML = json.scans.map(s => {
                const statusClass = s.status === 'IN' ? 'status-in' : 'status-out';
                const statusIcon = s.status === 'IN' ? '🟢 IN' : '🔴 OUT';
                return `<tr>
                  <td>${s.time || '-'}</td>
                  <td>${s.plate_number || '-'}</td>
                  <td>${s.name || 'Unknown'}</td>
                  <td>${s.vehicle_type || '-'}</td>
                  <td><span class="status-badge-sim ${statusClass}">${statusIcon}</span></td>
                </tr>`;
              }).join('');
            } else {
              recentScans.innerHTML = '<tr><td colspan="5" style="text-align:center;">No simulations yet</td></tr>';
            }
          }
        } catch (err) {
          console.error('[RFID] refreshRecentScans error:', err);
        }
      }

      console.log('[RFID] Controls attached successfully');
    }, pollInterval);
  }

  /* ---------- Visitors Page Controls ---------- */
  function attachVisitorsControls() {
    console.log('[Visitors] Attaching controls');

    // Define approve and reject functions
    window.approveVisitorPass = async function (passId) {
      const result = await Swal.fire({
        title: 'Approve Visitor Pass?',
        html: `
          <div class="text-left">
            <p class="mb-3">This will:</p>
            <ul class="list-disc list-inside text-sm text-gray-600 space-y-1">
              <li>Approve the visitor pass request</li>
              <li>Generate a QR code for entry</li>
              <li>Make it available to the homeowner</li>
              <li>Allow the visitor to enter during the specified period</li>
            </ul>
          </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '✓ Approve',
        confirmButtonColor: '#10b981',
        cancelButtonText: 'Cancel',
        cancelButtonColor: '#6b7280'
      });

      if (result.isConfirmed) {
        try {
          const response = await fetch('api/approve_visitor_pass.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pass_id: passId })
          });

          const data = await response.json();

          if (data.success) {
            await Swal.fire({
              icon: 'success',
              title: 'Approved!',
              html: `
                <div class="text-center">
                  <p class="mb-2">Visitor pass has been approved successfully.</p>
                  <p class="text-sm text-gray-600">The homeowner can now view and share the QR code.</p>
                </div>
              `,
              confirmButtonColor: '#3b82f6'
            });
            loadPage("visitors");
          } else {
            throw new Error(data.message || 'Failed to approve pass');
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            confirmButtonColor: '#ef4444'
          });
        }
      }
    };

    window.rejectVisitorPass = async function (passId) {
      const { value: reason } = await Swal.fire({
        title: 'Reject Visitor Pass',
        html: `
          <div class="text-left mb-3">
            <p class="text-sm text-gray-600 mb-2">Please provide a reason for rejecting this pass request:</p>
          </div>
        `,
        input: 'textarea',
        inputPlaceholder: 'e.g., Invalid documentation, security concerns, etc.',
        showCancelButton: true,
        confirmButtonText: '✗ Reject',
        confirmButtonColor: '#ef4444',
        cancelButtonText: 'Cancel',
        cancelButtonColor: '#6b7280',
        inputValidator: (value) => {
          if (!value || value.trim().length < 5) {
            return 'Please provide a detailed reason (at least 5 characters)';
          }
        },
        inputAttributes: {
          'aria-label': 'Rejection reason',
          'style': 'min-height: 80px;'
        }
      });

      if (reason) {
        try {
          const response = await fetch('api/reject_visitor_pass.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              pass_id: passId,
              reason: reason.trim()
            })
          });

          const data = await response.json();

          if (data.success) {
            await Swal.fire({
              icon: 'success',
              title: 'Rejected',
              html: `
                <div class="text-center">
                  <p class="mb-2">Visitor pass has been rejected.</p>
                  <p class="text-sm text-gray-600">The homeowner will be notified with your reason.</p>
                </div>
              `,
              confirmButtonColor: '#3b82f6'
            });
            loadPage("visitors");
          } else {
            throw new Error(data.message || 'Failed to reject pass');
          }
        } catch (error) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            confirmButtonColor: '#ef4444'
          });
        }
      }
    };

    // Visitor Pass Create Button
    document
      .getElementById("createPassBtn")
      ?.addEventListener("click", () => {
        console.log('[Visitors] Create Pass button clicked');
        openModal("api/visitor_pass_form.php");
      });

    // Refresh Button
    document.getElementById("refreshPassesBtn")?.addEventListener("click", () => {
      loadPage("visitors");
    });

    // Export CSV Button
    document.getElementById("exportPassesBtn")?.addEventListener("click", () => {
      exportTableToCSV('passesTable', 'visitor_passes_export.csv');
    });

    // Cancel Pass Buttons with Type-out Confirmation
    document.querySelectorAll(".cancelPassBtn").forEach((btn) => {
      btn.addEventListener("click", async () => {
        const id = btn.dataset.id;
        const { value: typed } = await Swal.fire({
          title: "Confirm Cancellation",
          html: `Type <strong>CANCEL</strong> to cancel visitor pass #${id}`,
          input: "text",
          inputPlaceholder: "Type CANCEL to confirm",
          showCancelButton: true,
          confirmButtonText: "Cancel Pass",
          confirmButtonColor: "#e74c3c",
          preConfirm: (v) => {
            if (v !== "CANCEL")
              Swal.showValidationError("You must type CANCEL to confirm");
            return v;
          },
          allowOutsideClick: false,
          width: "340px",
          heightAuto: false,
        });

        if (typed === "CANCEL") {
          try {
            const form = new FormData();
            form.append("csrf", csrf);
            form.append("id", id);
            const res = await fetch("api/cancel_visitor_pass.php", {
              method: "POST",
              body: form,
            });
            let json;
            try {
              json = await res.json();
            } catch (err) {
              json = { success: false, message: 'Invalid response' };
            }
            if (json && json.success) {
              showGrowl(json.message || "Pass cancelled");
              loadPage("visitors");
            } else {
              showGrowl(json.message || "Cancellation failed", "error");
            }
          } catch (err) {
            console.error("cancel error:", err);
            showGrowl("Cancellation failed", "error");
          }
        }
      });
    });

    // QR Code Click Handlers - Attach after content is loaded
    console.log('[Visitors] Attaching QR click handlers');
    const qrImages = document.querySelectorAll('.qr-clickable');
    console.log(`[Visitors] Found ${qrImages.length} QR images`);

    qrImages.forEach((img) => {
      img.addEventListener('click', () => {
        console.log('[Visitors] QR image clicked, src:', img.src);
        if (typeof window.openQRZoom === 'function') {
          window.openQRZoom(img.src);
        } else {
          console.error('[Visitors] openQRZoom function not found!');
        }
      });
    });
  }

  /* ---------- Employee Management Controls ---------- */
  function attachEmployeesControls() {
    console.log('[Employees] Attaching controls');

    // Wait for DOM elements with polling
    const pollInterval = setInterval(() => {
      const createBtn = document.getElementById("createEmployeeBtn");
      const refreshBtn = document.getElementById("refreshEmployeesBtn");
      const searchInput = document.getElementById('employeeSearchInput');
      const roleFilter = document.getElementById('employeeRoleFilter');

      if (!createBtn && !refreshBtn && !searchInput && !roleFilter) {
        return; // Keep polling
      }

      clearInterval(pollInterval);
      console.log('[Employees] Elements found, attaching listeners');

      // Create Employee Button
      const createBtn2 = document.getElementById("createEmployeeBtn");
      if (createBtn2) {
        createBtn2.addEventListener("click", () => {
          console.log('[Employees] Create Employee button clicked');
          openModal('api/employee_form.php');
        });
      }

      // Refresh Button
      const refreshBtn2 = document.getElementById("refreshEmployeesBtn");
      if (refreshBtn2) {
        refreshBtn2.addEventListener("click", () => {
          console.log('[Employees] Refresh button clicked');
          loadPage("employees");
        });
      }

      // Search functionality
      const searchInput2 = document.getElementById('employeeSearchInput');
      const roleFilter2 = document.getElementById('employeeRoleFilter');

      if (searchInput2) {
        searchInput2.addEventListener('input', function () {
          filterEmployeesTable();
        });
      }

      if (roleFilter2) {
        roleFilter2.addEventListener('change', function () {
          filterEmployeesTable();
        });
      }

      // Edit Employee Buttons - Same pattern as Manage Records
      document.querySelectorAll(".editEmployeeBtn").forEach((btn) => {
        btn.addEventListener("click", () => {
          const id = btn.dataset.id;
          console.log('[Employees] Edit employee:', id);
          openModal(`api/employee_form.php?id=${id}`);
        });
      });

      // Delete Employee Buttons - Same pattern as Manage Records
      document.querySelectorAll(".deleteEmployeeBtn").forEach((btn) => {
        btn.addEventListener("click", async () => {
          const id = btn.dataset.id;
          const username = btn.dataset.username;

          const { value: typed } = await Swal.fire({
            title: "Delete Employee",
            html: `Type <strong>DELETE</strong> to confirm deletion of <strong>${username}</strong>`,
            input: "text",
            inputPlaceholder: "Type DELETE to confirm",
            showCancelButton: true,
            confirmButtonText: "Delete",
            confirmButtonColor: "#e74c3c",
            cancelButtonText: "Cancel",
            preConfirm: (v) => {
              if (v !== "DELETE")
                Swal.showValidationError("You must type DELETE to confirm");
              return v;
            },
            allowOutsideClick: false,
            width: "340px",
            heightAuto: false
          });

          if (typed === "DELETE") {
            try {
              const form = new FormData();
              form.append("csrf", csrf);
              form.append("id", id);
              const res = await fetch("api/employee_delete.php", {
                method: "POST",
                body: form,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
              });
              let json;
              try {
                json = await res.json();
              } catch (err) {
                json = { success: false, message: 'Invalid response' };
              }
              if (json && json.success) {
                showGrowl(json.message || "Employee deleted", "success");
                loadPage("employees");
              } else {
                showGrowl(json.message || "Deletion failed", "error");
              }
            } catch (err) {
              console.error("delete error:", err);
              showGrowl("Deletion failed", "error");
            }
          }
        });
      });

    }, 100); // Poll every 100ms
  }

  function filterEmployeesTable() {
    const searchTerm = document.getElementById('employeeSearchInput')?.value.toLowerCase() || '';
    const roleFilter = document.getElementById('employeeRoleFilter')?.value || '';
    const table = document.getElementById('employeeTable');

    if (!table) return;

    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
      const cells = Array.from(row.querySelectorAll('td'));
      const username = cells[1]?.textContent.toLowerCase() || '';
      const role = cells[2]?.textContent.toLowerCase() || '';

      const matchesSearch = searchTerm === '' || username.includes(searchTerm);
      const matchesRole = roleFilter === '' || role.includes(roleFilter.toLowerCase());

      row.style.display = (matchesSearch && matchesRole) ? '' : 'none';
    });
  }

  /* ---------- Approvals Controls ---------- */
  function attachApprovalsControls() {
    console.log('[Approvals] Page loaded - controls are self-contained in approvals_page.php');
  }

  /* ---------- Audit Search Functionality ---------- */
  function initializeAuditSearch() {
    const searchInput = document.getElementById('auditSearchInput');
    const searchCount = document.getElementById('auditSearchCount');
    const table = document.getElementById('auditTable');

    console.log('[AUDIT SEARCH] Initializing search...', { searchInput, searchCount, table });

    if (!searchInput || !table) {
      console.error('[AUDIT SEARCH] Search elements not found');
      return;
    }

    const totalRows = table.querySelectorAll('tbody tr').length;
    console.log('[AUDIT SEARCH] Total rows:', totalRows);

    searchInput.addEventListener('input', function (e) {
      console.log('[AUDIT SEARCH] Search triggered:', e.target.value);
      const searchTerm = this.value.toLowerCase().trim();
      const rows = table.querySelectorAll('tbody tr');
      let visibleCount = 0;

      rows.forEach(row => {
        const cells = Array.from(row.querySelectorAll('td:not(:last-child)'));
        const text = cells.map(cell => cell.textContent).join(' ').toLowerCase();
        const isVisible = searchTerm === '' || text.includes(searchTerm);
        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
      });

      if (searchTerm) {
        searchCount.textContent = `${visibleCount} of ${totalRows} records`;
        searchCount.style.color = visibleCount > 0 ? '#16a34a' : '#dc2626';
      } else {
        searchCount.textContent = '';
      }
      console.log('[AUDIT SEARCH] Visible count:', visibleCount);
    });

    // ESC key clears search
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        this.value = '';
        this.dispatchEvent(new Event('input'));
      }
    });

    console.log('[AUDIT SEARCH] Search initialized successfully');
  }

  /* ---------- Audit Logs Controls ---------- */
  function attachAuditControls() {
    console.log('[Audit] Attaching controls...');

    // Initialize search functionality (same pattern as manage)
    initializeAuditSearch();

    // Apply Filters button
    const applyBtn = document.getElementById('applyFilters');
    if (applyBtn) {
      applyBtn.addEventListener('click', async function (e) {
        e.preventDefault();
        console.log('[Audit] Apply filters clicked');

        const actionFilter = document.getElementById('actionFilter');
        const action = actionFilter?.value || '';

        console.log('[Audit] Filter values:', { action });

        let queryString = '';
        if (action) queryString += `&action=${encodeURIComponent(action)}`;

        contentArea.innerHTML = `
          <div class="flex items-center justify-center min-h-[400px]">
            <div class="text-center">
              <div class="spinner spinner-lg mx-auto mb-4"></div>
              <p class="text-gray-500 text-sm">Applying filters...</p>
            </div>
          </div>
        `;

        try {
          const url = `fetch/fetch_audit.php?_=${Date.now()}${queryString}`;
          console.log('[Audit] Fetching:', url);

          const res = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });

          if (!res.ok) throw new Error(`HTTP ${res.status}`);

          const html = await res.text();
          contentArea.innerHTML = html;

          // Re-attach controls after loading filtered content
          attachAuditControls();

          showGrowl('Filters applied', 'success');
        } catch (err) {
          console.error('[Audit] Error:', err);
          contentArea.innerHTML = "<p style='color:red'>Failed to apply filters</p>";
          showGrowl('Failed to apply filters', 'error');
        }
      });
    }

    // Clear button
    document.getElementById('clearFilters')?.addEventListener('click', async () => {
      console.log('[Audit] Clear filters clicked');

      contentArea.innerHTML = `
        <div class="flex items-center justify-center min-h-[400px]">
          <div class="text-center">
            <div class="spinner spinner-lg mx-auto mb-4"></div>
            <p class="text-gray-500 text-sm">Loading...</p>
          </div>
        </div>
      `;

      try {
        const res = await fetch(`fetch/fetch_audit.php?_=${Date.now()}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const html = await res.text();
        contentArea.innerHTML = html;

        // Re-attach controls after clearing
        attachAuditControls();

        showGrowl('Filters cleared', 'success');
      } catch (err) {
        console.error('[Audit] Error:', err);
        showGrowl('Failed to clear filters', 'error');
      }
    });

    // Export CSV button
    document.getElementById('exportAuditBtn')?.addEventListener('click', () => {
      console.log('[EXPORT] Exporting table: auditTable');
      exportTableToCSV('auditTable', 'audit_logs_export.csv');
    });

    // Pagination controls for Audit Logs
    document.querySelectorAll(".pagination-btn").forEach((btn) => {
      btn.addEventListener("click", async function () {
        const page = this.dataset.page;
        console.log("[AUDIT] Loading page:", page);

        contentArea.innerHTML = `
          <div class="flex items-center justify-center min-h-[400px]">
            <div class="text-center">
              <div class="spinner spinner-lg mx-auto mb-4"></div>
              <p class="text-gray-500 text-sm">Loading...</p>
            </div>
          </div>
        `;

        try {
          const res = await fetch(`fetch/fetch_audit.php?page=${page}&_=${Date.now()}`, {
            headers: { "X-Requested-With": "XMLHttpRequest" },
          });

          if (!res.ok) throw new Error(`HTTP ${res.status}`);

          const html = await res.text();
          contentArea.innerHTML = html;

          contentArea.scrollTo({ top: 0, behavior: "smooth" });

          // Re-attach controls for new content
          attachAuditControls();
        } catch (err) {
          console.error("[AUDIT] Pagination error:", err);
          showGrowl("Failed to load page", "error");
        }
      });
    });
  }

  /* ---------- Reports Controls ---------- */
  function attachReportsControls() {
    console.log('[Reports] Attaching controls');
  }

  /* ---------- Modal Handling ---------- */
  async function openModal(url) {
    if (!modalEl || !modalBody) {
      console.error('[MODAL] Modal elements not found!');
      return;
    }

    modalEl.classList.remove('hidden');
    modalEl.setAttribute('aria-hidden', 'false');
    modalBody.innerHTML = "<div class='loading'>Loading...</div>";
    document.documentElement.style.overflow = "hidden";

    try {
      const sep = url.includes("?") ? "&" : "?";
      const fullUrl = `${url}${sep}ajax=1`;

      const res = await fetch(fullUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });

      // Session expiration is now handled by global fetch interceptor
      if (!res.ok) {
        throw new Error("load failed");
      }

      const html = await res.text();
      modalBody.innerHTML = html;
      bindModalForm();

      // Focus management: focus first input
      setTimeout(() => {
        const firstInput = modalBody.querySelector('input:not([type="hidden"]), textarea, select');
        if (firstInput) firstInput.focus();
      }, 100);

      // Keyboard trap: keep focus inside modal
      setupModalKeyboardTrap();
    } catch (err) {
      console.error("openModal error:", err);
      modalBody.innerHTML = '<p style="color:red;">Failed to load form</p>';
    }
  }
  window.openModal = openModal;

  window.closeModal = function () {
    if (!modalEl) return;
    modalEl.classList.add('hidden');
    modalEl.setAttribute('aria-hidden', 'true');
    modalBody.innerHTML = "";
    document.documentElement.style.overflow = "auto";

    // Return focus to trigger element if available
    if (document.activeElement) {
      document.activeElement.blur();
    }
  };

  /* ---------- Modal Keyboard Trap ---------- */
  function setupModalKeyboardTrap() {
    const focusableElements = modalEl.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];

    modalEl.addEventListener('keydown', function trapFocus(e) {
      if (e.key !== 'Tab') {
        // ESC key closes modal
        if (e.key === 'Escape') {
          closeModal();
        }
        return;
      }

      if (e.shiftKey) {
        // Shift + Tab
        if (document.activeElement === firstElement) {
          lastElement.focus();
          e.preventDefault();
        }
      } else {
        // Tab
        if (document.activeElement === lastElement) {
          firstElement.focus();
          e.preventDefault();
        }
      }
    });
  }

  function bindModalCancel() {
    modalBody.querySelectorAll(".cancel-btn").forEach((btn) => {
      btn.removeEventListener("click", closeModal);
      btn.addEventListener("click", closeModal);
    });
  }

  function bindModalForm() {
    bindModalCancel();
    const form = modalBody.querySelector("form");
    if (!form) return;

    console.log('[bindModalForm] Binding form:', form.id, 'Action:', form.getAttribute('action'));

    if (!form.getAttribute('action')) {
      if (form.dataset.url) form.setAttribute('action', form.dataset.url);
    }

    // Bind quick duration buttons for visitor pass form
    const quickDurationBtns = modalBody.querySelectorAll('.quick-duration');
    if (quickDurationBtns.length > 0) {
      const fromInput = modalBody.querySelector('#valid_from');
      const untilInput = modalBody.querySelector('#valid_until');

      if (fromInput && untilInput) {
        quickDurationBtns.forEach(btn => {
          btn.addEventListener('click', (e) => {
            e.preventDefault();
            const now = new Date();
            fromInput.value = now.toISOString().slice(0, 16);

            if (btn.dataset.hours) {
              now.setHours(now.getHours() + parseInt(btn.dataset.hours));
            } else if (btn.dataset.days) {
              now.setDate(now.getDate() + parseInt(btn.dataset.days));
            }

            untilInput.value = now.toISOString().slice(0, 16);
          });
        });
      }
    }

    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      console.log('[bindModalForm] Form submit intercepted');

      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn) submitBtn.disabled = true;
      const data = new FormData(form);
      if (!data.get("csrf")) data.append("csrf", csrf);

      // Password validation for employee forms
      const password = data.get('password');
      const confirmPassword = data.get('confirm_password');
      if (password && confirmPassword && password !== confirmPassword) {
        showGrowl('Passwords do not match', 'error');
        if (submitBtn) submitBtn.disabled = false;
        return;
      }

      const action = form.getAttribute("action") || form.dataset.url;
      console.log('[bindModalForm] Submitting to:', action);
      console.log('[bindModalForm] FormData:', Array.from(data.entries()));

      try {
        const res = await fetch(action, {
          method: "POST",
          body: data,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        console.log('[bindModalForm] Response status:', res.status);

        let json;
        try {
          const text = await res.text();
          console.log('[bindModalForm] Response text:', text);
          json = JSON.parse(text);
        } catch (err) {
          console.error('[bindModalForm] JSON parse error:', err);
          json = { success: false, message: 'Invalid response from server' };
        }

        if (json && json.success) {
          showGrowl(json.message || "Saved");
          closeModal();
          loadPage(currentPage);
        } else {
          showGrowl(json.message || "Save failed", "error");
        }
      } catch (err) {
        console.error("bindModalForm submit error:", err);
        showGrowl("Request failed", "error");
      } finally {
        if (submitBtn) submitBtn.disabled = false;
      }
    });
  }

  /* ---------- Search Functionality ---------- */
  function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchCount = document.getElementById('searchCount');
    const table = document.getElementById('homeownersTable');

    console.log('[SEARCH] Initializing search...', { searchInput, searchCount, table });

    if (!searchInput || !table) {
      console.error('[SEARCH] Search elements not found');
      return;
    }

    const totalRows = table.querySelectorAll('tbody tr').length;
    console.log('[SEARCH] Total rows:', totalRows);

    searchInput.addEventListener('input', function (e) {
      console.log('[SEARCH] Search triggered:', e.target.value);
      const searchTerm = this.value.toLowerCase().trim();
      const rows = table.querySelectorAll('tbody tr');
      let visibleCount = 0;

      rows.forEach(row => {
        const cells = Array.from(row.querySelectorAll('td:not(:last-child)'));
        const text = cells.map(cell => cell.textContent).join(' ').toLowerCase();
        const isVisible = searchTerm === '' || text.includes(searchTerm);
        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
      });

      if (searchTerm) {
        searchCount.textContent = `${visibleCount} of ${totalRows} records`;
        searchCount.style.color = visibleCount > 0 ? '#16a34a' : '#dc2626';
      } else {
        searchCount.textContent = '';
      }
      console.log('[SEARCH] Visible count:', visibleCount);
    });

    // ESC key clears search
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        this.value = '';
        this.dispatchEvent(new Event('input'));
      }
    });

    console.log('[SEARCH] Search initialized successfully');
  }

  /* ---------- CSV Export Functionality ---------- */
  function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) {
      console.error('[EXPORT] Table not found:', tableId);
      showGrowl('Table not found', 'error');
      return;
    }

    console.log('[EXPORT] Exporting table:', tableId);

    // Get headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
      const text = th.textContent.trim();
      // Skip empty headers or "Actions" column
      if (text && text.toLowerCase() !== 'actions') {
        headers.push(text);
      }
    });

    // Get visible rows only (respects search filter)
    const rows = [];
    table.querySelectorAll('tbody tr').forEach(tr => {
      // Skip hidden rows (filtered out by search)
      if (tr.style.display === 'none') return;

      const rowData = [];
      tr.querySelectorAll('td').forEach((td, index) => {
        // Skip the last column (Actions)
        if (index < tr.querySelectorAll('td').length - 1) {
          // Escape quotes and wrap in quotes if contains comma
          let value = td.textContent.trim();
          value = value.replace(/"/g, '""'); // Escape quotes
          if (value.includes(',') || value.includes('\n') || value.includes('"')) {
            value = `"${value}"`;
          }
          rowData.push(value);
        }
      });
      if (rowData.length > 0) {
        rows.push(rowData);
      }
    });

    if (rows.length === 0) {
      showGrowl('No data to export', 'warning');
      return;
    }

    // Build CSV content
    let csv = headers.join(',') + '\n';
    rows.forEach(row => {
      csv += row.join(',') + '\n';
    });

    // Create blob and download
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', filename);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);

    showGrowl(`Exported ${rows.length} records to CSV`, 'success');
    console.log('[EXPORT] Export completed:', rows.length, 'rows');
  }

  /* ---------- Manage Page Controls ---------- */
  function attachManageControls() {
    console.log('[MANAGE] Attaching controls...');

    // Initialize search functionality
    initializeSearch();

    // Refresh button
    document.getElementById("refreshBtn")?.addEventListener("click", () => {
      loadPage("manage");
    });

    // Add New button
    document
      .getElementById("openCreateBtn")
      ?.addEventListener("click", () =>
        openModal("homeowners/homeowner_create.php")
      );

    // Export CSV button
    document.getElementById("exportManageBtn")?.addEventListener("click", () => {
      exportTableToCSV('homeownersTable', 'homeowners_export.csv');
    });

    document.querySelectorAll(".btn-edit").forEach((btn) => {
      btn.addEventListener("click", () =>
        openModal(`homeowners/homeowner_edit.php?id=${btn.dataset.id}`)
      );
    });

    document.querySelectorAll(".deleteBtn").forEach((btn) => {
      btn.addEventListener("click", async () => {
        const id = btn.dataset.id;
        const { value: typed } = await Swal.fire({
          title: "Confirm deletion",
          html: `Type <strong>DELETE</strong> to delete homeowner #${id}`,
          input: "text",
          inputPlaceholder: "Type DELETE to confirm",
          showCancelButton: true,
          confirmButtonText: "Delete",
          confirmButtonColor: "#e74c3c",
          preConfirm: (v) => {
            if (v !== "DELETE")
              Swal.showValidationMessage("You must type DELETE to confirm");
            return v;
          },
          allowOutsideClick: false,
          width: "340px",
          heightAuto: false,
        });

        if (typed === "DELETE") {
          try {
            const form = new FormData();
            form.append("csrf", csrf);
            form.append("id", id);
            const res = await fetch("homeowners/homeowner_delete.php", {
              method: "POST",
              body: form,
            });
            let json;
            try {
              json = await res.json();
            } catch (err) {
              json = { success: false, message: 'Invalid response' };
            }
            if (json && json.success) {
              showGrowl(json.message || "Deleted");
              loadPage("manage");
            } else {
              showGrowl(json.message || "Delete failed", "error");
            }
          } catch (err) {
            console.error("delete error:", err);
            showGrowl("Delete failed", "error");
          }
        } else {
          showGrowl("Delete cancelled", "error");
        }
      });
    });

    document.querySelectorAll(".cancel-btn").forEach((btn) => {
      btn.addEventListener("click", closeModal);
    });

    // Pagination controls for Manage Records
    document.querySelectorAll(".pagination-btn").forEach((btn) => {
      btn.addEventListener("click", async function () {
        const page = this.dataset.page;
        console.log("[MANAGE] Loading page:", page);

        contentArea.innerHTML = `
          <div class="flex items-center justify-center min-h-[400px]">
            <div class="text-center">
              <div class="spinner spinner-lg mx-auto mb-4"></div>
              <p class="text-gray-500 text-sm">Loading...</p>
            </div>
          </div>
        `;

        try {
          const res = await fetch(`fetch/fetch_manage.php?page=${page}&_=${Date.now()}`, {
            headers: { "X-Requested-With": "XMLHttpRequest" },
          });

          if (!res.ok) throw new Error(`HTTP ${res.status}`);

          const html = await res.text();
          contentArea.innerHTML = html;

          contentArea.scrollTo({ top: 0, behavior: "smooth" });

          // Re-attach controls for new content
          attachManageControls();
        } catch (err) {
          console.error("[MANAGE] Pagination error:", err);
          showGrowl("Failed to load page", "error");
        }
      });
    });
  }

  /* ---------- Access Logs Search Functionality ---------- */
  function initializeLogsSearch() {
    const searchInput = document.getElementById('logsSearchInput');
    const searchCount = document.getElementById('logsSearchCount');
    const table = document.getElementById('logsTable');

    console.log('[LOGS SEARCH] Initializing search...', { searchInput, searchCount, table });

    if (!searchInput || !table) {
      console.error('[LOGS SEARCH] Search elements not found');
      return;
    }

    const totalRows = table.querySelectorAll('tbody tr').length;
    console.log('[LOGS SEARCH] Total rows:', totalRows);

    searchInput.addEventListener('input', function (e) {
      console.log('[LOGS SEARCH] Search triggered:', e.target.value);
      const searchTerm = this.value.toLowerCase().trim();
      const rows = table.querySelectorAll('tbody tr');
      let visibleCount = 0;

      rows.forEach(row => {
        const cells = Array.from(row.querySelectorAll('td:not(:last-child)'));
        const text = cells.map(cell => cell.textContent).join(' ').toLowerCase();
        const isVisible = searchTerm === '' || text.includes(searchTerm);
        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
      });

      if (searchTerm) {
        searchCount.textContent = `${visibleCount} of ${totalRows} records`;
        searchCount.style.color = visibleCount > 0 ? '#16a34a' : '#dc2626';
      } else {
        searchCount.textContent = '';
      }
      console.log('[LOGS SEARCH] Visible count:', visibleCount);
    });

    // ESC key clears search
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        this.value = '';
        this.dispatchEvent(new Event('input'));
      }
    });

    console.log('[LOGS SEARCH] Search initialized successfully');
  }

  /* ---------- Access Logs Controls ---------- */
  function attachLogsControls() {
    console.log('[LOGS] Attaching controls...');

    // Initialize search functionality
    initializeLogsSearch();

    // Refresh logs button
    document.getElementById("refreshLogsBtn")?.addEventListener("click", () => {
      loadPage("logs");
    });

    // Export logs CSV button
    document.getElementById("exportLogsBtn")?.addEventListener("click", () => {
      exportTableToCSV('logsTable', 'access_logs_export.csv');
    });

    // Pagination buttons
    document.querySelectorAll('.pagination-btn').forEach(btn => {
      btn.addEventListener('click', async function () {
        const page = this.dataset.page;
        console.log('[LOGS] Loading page:', page);

        contentArea.innerHTML = `
          <div class="flex items-center justify-center min-h-[400px]">
            <div class="text-center">
              <div class="spinner spinner-lg mx-auto mb-4"></div>
              <p class="text-gray-500 text-sm">Loading...</p>
            </div>
          </div>
        `;

        try {
          const res = await fetch(`fetch/fetch_logs.php?page=${page}&_=${Date.now()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });

          if (!res.ok) throw new Error(`HTTP ${res.status}`);

          const html = await res.text();
          contentArea.innerHTML = html;

          // Scroll to top for better UX
          contentArea.scrollTo({ top: 0, behavior: 'smooth' });

          // Re-attach controls after loading new page
          attachLogsControls();
        } catch (err) {
          console.error('[LOGS] Error:', err);
          showGrowl('Failed to load page', 'error');
        }
      });
    });

    // Delete log buttons
    document.querySelectorAll(".deleteLogBtn").forEach((btn) => {
      btn.addEventListener("click", async () => {
        const log_id = btn.dataset.id;

        const { value: typed } = await Swal.fire({
          title: "Confirm deletion",
          html: `Type <strong>DELETE</strong> to delete access log #${log_id}`,
          input: "text",
          inputPlaceholder: "Type DELETE to confirm",
          showCancelButton: true,
          confirmButtonText: "Delete",
          confirmButtonColor: "#e74c3c",
          preConfirm: (v) => {
            if (v !== "DELETE")
              Swal.showValidationMessage("You must type DELETE to confirm");
            return v;
          },
          allowOutsideClick: false,
          width: "340px",
          heightAuto: false,
        });

        if (typed === "DELETE") {
          try {
            const form = new FormData();
            form.append("csrf", csrf);
            form.append("log_id", log_id);
            const res = await fetch("fetch/delete_access_log.php", {
              method: "POST",
              body: form,
            });
            let json;
            try {
              json = await res.json();
            } catch (err) {
              json = { success: false, message: 'Invalid response' };
            }
            if (json && json.success) {
              showGrowl(json.message || "Log deleted");
              loadPage("logs");
            } else {
              showGrowl(json.message || "Delete failed", "error");
            }
          } catch (err) {
            console.error("Delete log error:", err);
            showGrowl("Delete failed", "error");
          }
        } else {
          showGrowl("Delete cancelled", "error");
        }
      });
    });
  }

  /* ---------- Dashboard (Placeholder) ---------- */
  function attachDashboardControls() { }

  /* ---------- Database Backup Button ---------- */
  if (backupBtn) {
    backupBtn.addEventListener('click', async () => {
      console.log('[ADMIN] Database backup triggered');

      const originalContent = backupBtn.innerHTML;
      backupBtn.disabled = true;
      backupBtn.innerHTML = `
        <svg class="h-4 w-4 flex-shrink-0 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <span class="sidebar-text">Creating Backup...</span>
      `;

      try {
        const response = await fetch('utilities/backup_database.php', {
          method: 'POST'
        });

        if (!response.ok) {
          throw new Error('Backup request failed');
        }

        const result = await response.json();

        if (result.success) {
          Swal.fire({
            icon: 'success',
            title: 'Backup Created',
            html: `
              <p class="text-sm text-gray-600">Database backup completed successfully!</p>
              <div class="mt-3 p-3 bg-gray-50 rounded text-left text-sm">
                <p><strong>Filename:</strong> ${result.filename}</p>
                <p><strong>Size:</strong> ${result.size}</p>
              </div>
            `,
            confirmButtonText: 'OK',
            confirmButtonColor: '#4b5563'
          });
        } else {
          throw new Error(result.message || 'Backup failed');
        }
      } catch (error) {
        console.error('[ADMIN] Backup error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Backup Failed',
          text: error.message || 'Failed to create database backup',
          confirmButtonText: 'OK',
          confirmButtonColor: '#ef4444'
        });
      } finally {
        backupBtn.disabled = false;
        backupBtn.innerHTML = originalContent;
      }
    });
  }

  /* ---------- Make functions globally accessible ---------- */
  window.loadPage = loadPage;
  window.showGrowl = showGrowl;
  window.exportTableToCSV = exportTableToCSV;
});