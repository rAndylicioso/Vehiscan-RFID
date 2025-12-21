// guard/js/guard_side.js

// SweetAlert2 Fallback - Must be defined before any Swal usage
if (typeof Swal === 'undefined') {
  console.warn('[GUARD] SweetAlert2 not loaded, using fallback alert/confirm');
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
    }
  };
} else {
  console.log('[GUARD] SweetAlert2 loaded successfully');
}

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

/* ---------- No Session Timeout for Guard (24/7 Access) ---------- */
// Guard needs to be logged in 24/7, so no timeout warnings
// Only logout when explicitly requested

document.addEventListener('DOMContentLoaded', function () {
  // Use global logger provided by `logger.js`
  __vsLog('[GUARD] Initializing guard panel...');

  // ====== MOBILE MENU TOGGLE ======
  const mobileMenuBtn = document.getElementById('mobile-menu-btn');
  const mobileOverlay = document.getElementById('mobile-overlay');
  const sidebar = document.getElementById('sidebar');
  const menuItems = document.querySelectorAll('.menu-item[data-page]');

  if (mobileMenuBtn && mobileOverlay && sidebar) {
    mobileMenuBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      sidebar.classList.toggle('mobile-open');
      mobileOverlay.classList.toggle('active');
      __vsLog('[GUARD] Mobile menu toggled');
    });

    mobileOverlay.addEventListener('click', () => {
      sidebar.classList.remove('mobile-open');
      mobileOverlay.classList.remove('active');
      __vsLog('[GUARD] Mobile overlay clicked - menu closed');
    });

    // Close mobile menu when a menu item is clicked
    menuItems.forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
          sidebar.classList.remove('mobile-open');
          mobileOverlay.classList.remove('active');
        }
      });
    });

    __vsLog('[GUARD] Mobile menu initialized');
  }

  // ====== TOGGLE GROUP COMPONENT ======
  function initToggleGroups() {
    document.querySelectorAll('.filter-toggle-group').forEach(group => {
      const type = group.getAttribute('data-type') || 'single';
      const items = group.querySelectorAll('.filter-toggle-item');

      items.forEach(item => {
        // Skip visitor filter - it has custom handler
        if (item.id === 'filterVisitors') {
          __vsLog('[TOGGLE] Skipping filterVisitors - has custom handler');
          return;
        }

        item.addEventListener('click', () => {
          if (item.disabled) return;

          const isActive = item.classList.contains('toggle-active');

          if (type === 'single') {
            // Single selection - deselect all others
            items.forEach(i => i.classList.remove('toggle-active'));
            if (!isActive) {
              item.classList.add('toggle-active');
            }
          } else {
            // Multiple selection - toggle current
            item.classList.toggle('toggle-active');
          }

          // Trigger existing filter logic
          const value = item.getAttribute('data-value');
          __vsLog('[TOGGLE] Filter toggled:', value, item.classList.contains('toggle-active'));
        });
      });
    });
  }

  initToggleGroups();

  // ====== DROPDOWN MENU HANDLERS ======
  function initDropdowns() {
    // Filter Dropdown
    const filterDropdownBtn = document.getElementById('filterDropdownBtn');
    const filterDropdownContent = document.getElementById('filterDropdownContent');

    // Actions Dropdown
    const actionsDropdownBtn = document.getElementById('actionsDropdownBtn');
    const actionsDropdownContent = document.getElementById('actionsDropdownContent');

    function closeAllDropdowns() {
      if (filterDropdownContent) filterDropdownContent.classList.add('hidden');
      if (actionsDropdownContent) actionsDropdownContent.classList.add('hidden');
    }

    function toggleDropdown(content, otherContent) {
      if (otherContent) otherContent.classList.add('hidden');
      content.classList.toggle('hidden');
    }

    if (filterDropdownBtn && filterDropdownContent) {
      filterDropdownBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleDropdown(filterDropdownContent, actionsDropdownContent);
      });
    }

    if (actionsDropdownBtn && actionsDropdownContent) {
      actionsDropdownBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleDropdown(actionsDropdownContent, filterDropdownContent);
      });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
      if (!e.target.closest('#filterDropdown') && !e.target.closest('#actionsDropdown')) {
        closeAllDropdowns();
      }
    });

    // Prevent dropdown from closing when clicking inside
    [filterDropdownContent, actionsDropdownContent].forEach(content => {
      if (content) {
        content.addEventListener('click', (e) => {
          // Only stop propagation if not clicking a menu item
          if (e.target.tagName !== 'BUTTON') {
            e.stopPropagation();
          }
        });
      }
    });

    __vsLog('[GUARD] Dropdowns initialized');
  }

  // Initialize dropdowns
  initDropdowns();

  // ====== PAGE SWITCHING ======
  window.switchPage = function (pageName) {
    __vsLog('[GUARD] Switching to page:', pageName);

    // Get all pages
    const allPages = document.querySelectorAll('.page-content');
    const targetPage = document.getElementById(`page-${pageName}`);

    if (!targetPage) {
      __vsLog('[GUARD] Target page not found:', pageName);
      return;
    }

    // Fade out all pages except target
    allPages.forEach(page => {
      if (page !== targetPage) {
        page.style.opacity = '0';
        page.style.transform = 'translateY(10px)';
        setTimeout(() => {
          page.classList.add('hidden');
          page.classList.remove('active');
        }, 300);
      }
    });

    // Show target page immediately, then fade in
    targetPage.classList.remove('hidden');
    targetPage.classList.add('active');
    targetPage.style.opacity = '0';
    targetPage.style.transform = 'translateY(10px)';

    // Force reflow
    targetPage.offsetHeight;

    // Fade in target page
    requestAnimationFrame(() => {
      targetPage.style.opacity = '1';
      targetPage.style.transform = 'translateY(0)';
    });

    // Update page title
    const pageTitle = document.getElementById('page-title');
    const titles = {
      'logs': 'Access Logs',
      'homeowners': 'Homeowners',
      'camera': 'Live Camera',
      'visitor': 'Visitor Passes'
    };
    if (pageTitle && titles[pageName]) {
      pageTitle.textContent = titles[pageName];
    }

    // Update active menu item
    document.querySelectorAll('.menu-item').forEach(item => {
      item.classList.remove('active');
      const itemPage = item.getAttribute('data-page');
      if (itemPage === pageName) {
        item.classList.add('active');
      }
    });

    // Page-specific initialization
    if (pageName === 'logs') {
      loadLogs();
    } else if (pageName === 'homeowners') {
      if (typeof loadHomeowners === 'function') loadHomeowners(true);
    } else if (pageName === 'camera') {
      __vsLog('[GUARD] Camera page loaded');
    } else if (pageName === 'visitor') {
      loadVisitorPasses();
    }
  };

  // Initialize menu items click handlers
  document.querySelectorAll('.menu-item').forEach(item => {
    item.addEventListener('click', (e) => {
      e.preventDefault();
      const pageName = item.getAttribute('data-page');
      if (pageName) {
        switchPage(pageName);
      }
    });
  });

  // ====== USER DROPDOWN (Match Admin Panel) ======
  const userTrigger = document.getElementById('user-trigger');
  const userDropdown = document.getElementById('user-dropdown');
  const userChevron = document.getElementById('user-chevron');

  __vsLog('[USER-DROPDOWN] Elements found:', {
    trigger: !!userTrigger,
    dropdown: !!userDropdown,
    chevron: !!userChevron
  });

  // Position dropdown dynamically (from bottom)
  function positionDropdown() {
    if (!userTrigger || !userDropdown) {
      __vsLog('[USER-DROPDOWN] Missing elements, cannot position');
      return;
    }

    const triggerRect = userTrigger.getBoundingClientRect();
    const sidebar = document.getElementById('sidebar');
    const sidebarRect = sidebar ? sidebar.getBoundingClientRect() : null;
    const gap = 8; // 0.5rem gap

    // Position relative to sidebar width
    const left = sidebarRect ? sidebarRect.left : triggerRect.left;
    const bottom = window.innerHeight - triggerRect.top + gap;
    const width = sidebarRect ? sidebarRect.width : triggerRect.width;

    __vsLog('[USER-DROPDOWN] Positioning:', {
      left,
      bottom,
      width,
      sidebarWidth: sidebarRect?.width
    });

    // Use bottom positioning and align with sidebar
    userDropdown.style.left = `${left}px`;
    userDropdown.style.bottom = `${bottom}px`;
    userDropdown.style.top = 'auto';
    userDropdown.style.width = `${width}px`;
    userDropdown.style.display = 'block';
  }

  if (userTrigger && userDropdown) {
    userTrigger.addEventListener('click', (e) => {
      __vsLog('[USER-DROPDOWN] Trigger clicked!');
      e.stopPropagation();
      const isHidden = userDropdown.style.display === 'none' || userDropdown.style.display === '';

      __vsLog('[USER-DROPDOWN] Current state:', { isHidden, display: userDropdown.style.display });

      if (isHidden) {
        positionDropdown();
        userDropdown.style.display = 'block';
        __vsLog('[USER-DROPDOWN] Dropdown opened');
      } else {
        userDropdown.style.display = 'none';
        __vsLog('[USER-DROPDOWN] Dropdown closed');
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

    userDropdown.addEventListener('click', (e) => {
      e.stopPropagation();
    });

    // Reposition on window resize
    window.addEventListener('resize', () => {
      if (userDropdown.style.display === 'block') {
        positionDropdown();
      }
    });
  }

  // Sign Out button in dropdown
  const signOutBtn = document.getElementById('signOutBtn');
  if (signOutBtn) {
    signOutBtn.addEventListener('click', async (e) => {
      e.preventDefault();

      const result = await Swal.fire({
        title: 'Confirm Logout',
        text: 'Are you sure you want to logout?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Logout',
        cancelButtonText: 'Cancel',
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--guard-warn') || getComputedStyle(document.documentElement).getPropertyValue('--warn') || '#ef4444',
        cancelButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--guard-accent') || getComputedStyle(document.documentElement).getPropertyValue('--accent') || '#6b7280',
        heightAuto: false,
        reverseButtons: true
      });

      if (result.isConfirmed) {
        try {
          const res = await fetch('../../auth/logout.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });

          if (res.ok) {
            window.location.href = '../../auth/login.php';
          } else {
            throw new Error('Logout failed');
          }
        } catch (err) {
          console.error('[GUARD] Logout error:', err);
          window.location.href = '../../auth/logout.php';
        }
      }
    });
  }

  // ====== DARK MODE ======
  // Dark mode is now handled by guard-dark-mode.js (separate dedicated file)
  // This keeps guard panel dark mode independent from admin panel

  // ====== SEARCH HISTORY ======
  let searchHistory = JSON.parse(localStorage.getItem('guardSearchHistory') || '[]');
  const MAX_HISTORY = 5;

  function addToSearchHistory(term) {
    if (!term || term.length < 2) return;

    // Remove duplicates and add to front
    searchHistory = searchHistory.filter(item => item !== term);
    searchHistory.unshift(term);

    // Limit to MAX_HISTORY items
    if (searchHistory.length > MAX_HISTORY) {
      searchHistory = searchHistory.slice(0, MAX_HISTORY);
    }

    localStorage.setItem('guardSearchHistory', JSON.stringify(searchHistory));
  }

  function showSearchHistory() {
    const dropdown = document.getElementById('searchHistory');
    if (!dropdown || searchHistory.length === 0) return;

    dropdown.innerHTML = searchHistory.map(term =>
      `<div class="history-item" data-term="${term}">
        <span class="history-icon">🕐</span>
        <span>${term}</span>
      </div>`
    ).join('') +
      '<div class="history-clear">🗑️ Clear History</div>';

    dropdown.classList.remove('hidden');

    // Add click handlers
    dropdown.querySelectorAll('.history-item').forEach(item => {
      item.addEventListener('click', () => {
        const term = item.dataset.term;
        logsSearch.value = term;
        logsSearch.dispatchEvent(new Event('input'));
        dropdown.classList.add('hidden');
      });
    });

    dropdown.querySelector('.history-clear')?.addEventListener('click', () => {
      searchHistory = [];
      localStorage.removeItem('guardSearchHistory');
      dropdown.classList.add('hidden');
      if (window.toast) {
        window.toast.success('🗑️ Search history cleared');
      }
    });
  }

  function hideSearchHistory() {
    setTimeout(() => {
      const dropdown = document.getElementById('searchHistory');
      if (dropdown) dropdown.classList.add('hidden');
    }, 200);
  }

  // Elements
  const ownerImage = document.getElementById('ownerImage');
  const carImage = document.getElementById('carImage');
  const ownerName = document.getElementById('ownerName');
  const ownerAddress = document.getElementById('ownerAddress');
  const ownerContact = document.getElementById('ownerContact');
  const vehicleType = document.getElementById('vehicleType');
  const vehicleColor = document.getElementById('vehicleColor');
  const plateNumber = document.getElementById('plateNumber');
  const searchInput = document.getElementById('homeownerSearch');
  const clearSearch = document.getElementById('clearSearch');
  const prevBtn = document.getElementById('prevOwner');
  const nextBtn = document.getElementById('nextOwner');
  const ownerCounter = document.getElementById('ownerCounter');
  const logoutBtn = document.getElementById('signOutBtn');
  const clockEl = document.getElementById('clock'); // May not exist in new UI
  const toggleCamera = document.getElementById('toggleCamera');
  const liveCamera = document.getElementById('liveCamera');
  const powerIcon = document.getElementById('powerIcon');

  let allHomeowners = [];
  let currentIndex = 0;
  let cameraStream = null;
  let cameraEnabled = false;

  // Placeholder images using data URIs (no network calls)
  const PLACEHOLDER_CAR = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="200"%3E%3Crect fill="%23ddd" width="400" height="200"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23999" font-size="18" font-family="Arial"%3ENo Vehicle%3C/text%3E%3C/svg%3E';
  const PLACEHOLDER_OWNER = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="200"%3E%3Crect fill="%23ddd" width="400" height="200"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23999" font-size="18" font-family="Arial"%3ENo Owner%3C/text%3E%3C/svg%3E';

  // Set default placeholders
  carImage.src = PLACEHOLDER_CAR;
  ownerImage.src = PLACEHOLDER_OWNER;

  // Clock
  function updateClock() {
    if (!clockEl) return; // Guard against missing element
    const now = new Date();
    clockEl.textContent = now.toLocaleTimeString('en-US', {
      hour12: false,
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit'
    });
  }
  if (clockEl) {
    updateClock();
    setInterval(updateClock, 1000);
  }

  // Track active fetch requests
  let activeLogsFetch = false;
  let currentLogPage = 1;

  // Track the last seen log ID to detect NEW logs
  let lastSeenLogId = parseInt(localStorage.getItem('lastSeenLogId')) || 0;
  __vsLog('[GUARD] Starting with lastSeenLogId:', lastSeenLogId);

  // Load recent logs with SERVER-SIDE PAGINATION (matching admin panel architecture)
  async function loadLogs(page = 1) {
    if (activeLogsFetch) return;
    activeLogsFetch = true;

    const logsWrapper = document.getElementById('logsContainerWrapper');
    if (!logsWrapper) {
      console.error('[GUARD] logsContainerWrapper not found');
      activeLogsFetch = false;
      return;
    }

    try {
      __vsLog('[GUARD] Fetching logs page', page, 'from ../fetch/fetch_logs.php');

      // Show loading state
      logsWrapper.innerHTML = `
        <div class="logs-table-container">
          <div class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-gray-300 border-t-gray-600"></div>
            <p class="mt-2 text-gray-500">Loading logs...</p>
          </div>
        </div>
      `;

      // Fetch HTML from server (matching admin pattern)
      const res = await fetch(`../fetch/fetch_logs.php?page=${page}&_=${Date.now()}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });

      __vsLog('[GUARD] Response status:', res.status);

      if (!res.ok) {
        if (res.status === 403) {
          window.location.href = '../../auth/login.php?timeout=1';
          return;
        }
        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
      }

      // Get HTML content from server
      const html = await res.text();
      __vsLog('[GUARD] Received HTML, length:', html.length);

      // Replace wrapper content with server-rendered HTML
      logsWrapper.innerHTML = html;

      // Store current page
      currentLogPage = page;
      __vsLog('[GUARD] Updated currentLogPage to:', currentLogPage);

      // Scroll to top for better UX
      const mainContent = document.querySelector('.content-scroll');
      if (mainContent) {
        mainContent.scrollTo({ top: 0, behavior: 'smooth' });
      }

      // Re-attach pagination event listeners after DOM update
      setTimeout(() => {
        attachLogsPaginationHandlers();

        // Re-apply filters if any are active
        const searchInput = document.getElementById('logsSearch');
        if (currentFilter || dateRangeFilter || (searchInput && searchInput.value)) {
          __vsLog('[GUARD] Re-applying filters after page load');
          filterLogs();
        }
      }, 10);

      __vsLog('[GUARD] Logs loaded successfully for page', page);

    } catch (err) {
      console.error('[GUARD] Load logs error:', err);
      logsWrapper.innerHTML = `
        <div class="logs-table-container">
          <div class="text-center py-12 text-red-500">Error: ${err.message}</div>
        </div>
      `;
    } finally {
      activeLogsFetch = false;
    }
  }

  // Attach pagination handlers (matching admin panel pattern)
  function attachLogsPaginationHandlers() {
    const buttons = document.querySelectorAll('.pagination-btn, .pagination-page');
    __vsLog('[GUARD] Found', buttons.length, 'pagination buttons');

    buttons.forEach(btn => {
      // Remove any existing handlers to prevent duplicates
      btn.replaceWith(btn.cloneNode(true));
    });

    // Re-query after cloning
    const freshButtons = document.querySelectorAll('.pagination-btn, .pagination-page');

    freshButtons.forEach(btn => {
      btn.addEventListener('click', async function (e) {
        e.preventDefault();
        e.stopPropagation();

        __vsLog('[GUARD] Pagination button clicked:', this.className, 'dataset.page:', this.dataset.page);

        // Skip if disabled (but NOT if just active)
        if (this.disabled || this.classList.contains('disabled')) {
          __vsLog('[GUARD] Button is disabled, skipping');
          return;
        }

        const page = parseInt(this.dataset.page);
        __vsLog('[GUARD] Parsed page number:', page, 'Current page:', currentLogPage);

        if (!page || page < 1) {
          __vsLog('[GUARD] Invalid page number, skipping');
          return;
        }

        if (page === currentLogPage) {
          __vsLog('[GUARD] Already on page', page, 'skipping reload');
          return;
        }

        __vsLog('[GUARD] Loading page:', page);
        await loadLogs(page);
      });
    });

    __vsLog('[GUARD] Attached handlers to', freshButtons.length, 'buttons');
  }

  // Load all homeowners for search/carousel with better state management
  async function loadHomeowners(preserveIndex = false) {
    try {
      __vsLog('[GUARD] Fetching homeowners...');
      // Use configured endpoint or fallback and build absolute URL
      const endpoint = window.vehiscanConfig?.apiEndpoints?.homeowners || 'fetch_homeowners.php';
      const base = window.vehiscanConfig?.baseUrl || window.baseUrl || window.location.origin;
      let baseResolved = base;
      if (baseResolved.startsWith('/')) baseResolved = window.location.origin + baseResolved;
      else if (!/^https?:\/\//i.test(baseResolved)) baseResolved = window.location.origin + '/' + baseResolved.replace(/^\/+/, '');
      const endpointUrl = new URL(endpoint, baseResolved).toString();
      __vsLog('[GUARD] Fetching homeowners from:', endpointUrl);

      const res = await fetch(endpointUrl, { credentials: 'same-origin' });
      const jsonResponse = await res.json();
      __vsLog('[GUARD] API Response:', jsonResponse);

      if (!res.ok || jsonResponse.error) {
        throw new Error(jsonResponse.error || 'Failed to load homeowners');
      }

      const newHomeowners = Array.isArray(jsonResponse) ? jsonResponse : (jsonResponse.data || []);
      __vsLog('[GUARD] Loaded homeowners:', newHomeowners.length);

      // Get last known state
      const lastIndex = parseInt(localStorage.getItem('lastHomeownerIndex') || '0');
      const lastId = localStorage.getItem('lastHomeownerId');

      // Sort homeowners by ID to maintain consistent order
      newHomeowners.sort((a, b) => parseInt(a.id) - parseInt(b.id));
      allHomeowners = newHomeowners;

      if (allHomeowners.length > 0) {
        if (preserveIndex && lastId) {
          // Try to find the same homeowner by ID first
          const newIndex = allHomeowners.findIndex(h => h.id.toString() === lastId);
          if (newIndex !== -1) {
            currentIndex = newIndex;
          } else {
            // If not found, try to use the last known index if it's valid
            currentIndex = lastIndex < allHomeowners.length ? lastIndex : 0;
          }
        } else {
          currentIndex = 0;
        }

        __vsLog('[GUARD] Setting index to:', {
          preserveIndex,
          currentIndex,
          totalHomeowners: allHomeowners.length,
          currentHomeowner: allHomeowners[currentIndex]?.name
        });

        displayHomeowner(currentIndex);
      }
    } catch (err) {
      console.error('[GUARD] Load homeowners error:', err);
      // Don't show error to user, just log it
    }
  }

  // Display homeowner at index with better tracking and async handling
  async function displayHomeowner(index, skipAnimation = false) {
    if (!Array.isArray(allHomeowners) || allHomeowners.length === 0) {
      __vsLog('[GUARD] No homeowners to display');
      return;
    }

    // Ensure index is within bounds
    if (index < 0 || index >= allHomeowners.length) {
      console.warn('[GUARD] Invalid index:', index);
      index = 0;
    }

    const h = allHomeowners[index];
    if (!h || !h.id) {
      console.warn('[GUARD] Invalid homeowner data at index:', index);
      return;
    }

    // Save current state
    localStorage.setItem('lastHomeownerIndex', index.toString());
    localStorage.setItem('lastHomeownerId', h.id.toString());

    // Add animation unless skipped
    if (!skipAnimation) {
      const container = document.querySelector('.homeowner-details-container');
      if (container) {
        container.style.animation = 'none';
        container.offsetHeight; // Trigger reflow
        container.style.animation = 'fadeInRight 0.3s ease-out';
      }
    }

    updateHomeownerDisplay(h);
    ownerCounter.textContent = `${index + 1}/${allHomeowners.length}`;

    // Update navigation buttons state
    if (prevBtn && nextBtn) {
      prevBtn.disabled = allHomeowners.length <= 1;
      nextBtn.disabled = allHomeowners.length <= 1;
    }

    __vsLog('[GUARD] Displaying homeowner:', {
      index,
      id: h.id,
      name: h.name,
      plate: h.plate_number
    });
  }

  // Add animation styles if not present
  if (!document.getElementById('homeownerStyles')) {
    const style = document.createElement('style');
    style.id = 'homeownerStyles';
    style.textContent = `
      @keyframes fadeInRight {
        from {
          opacity: 0;
          transform: translateX(10px);
        }
        to {
          opacity: 1;
          transform: translateX(0);
        }
      }
      
      .homeowner-details-container {
        animation: fadeInRight 0.3s ease-out;
      }
    `;
    document.head.appendChild(style);
  }

  // Update homeowner display
  function updateHomeownerDisplay(data) {
    __vsLog('[GUARD] Updating homeowner display:', data);

    ownerName.textContent = `Name: ${data.name || '-'}`;
    ownerAddress.textContent = `Address: ${data.address || '-'}`;
    ownerContact.textContent = `Contact: ${data.contact || '-'}`;
    vehicleType.textContent = `Vehicle Type: ${data.vehicle_type || '-'}`;
    vehicleColor.textContent = `Color: ${data.color || '-'}`;
    plateNumber.textContent = `Plate Number: ${data.plate_number || '-'}`;

    // Images with error handling
    const tryLoadImage = async (imageElement, filePath, type, placeholder) => {
      if (!filePath) {
        __vsLog(`[GUARD] No ${type} image provided, using placeholder`);
        imageElement.src = placeholder;
        return;
      }
      __vsLog(`[GUARD] Loading ${type} image:`, filePath);

      // Build a robust absolute URL for the image. Support:
      //  - full http(s) URLs
      //  - paths starting with '/'
      //  - stored values like 'uploads/vehicles/foo.jpg' or just 'foo.jpg'
      const buildImageUrl = (rawPath, kind) => {
        if (!rawPath) return null;
        // Full URL
        if (/^https?:\/\//i.test(rawPath)) return rawPath;

        // Normalize leading slashes
        let p = rawPath.replace(/^\/+/, '');

        // If it's already under uploads/ use it; if it starts with vehicles/ or homeowners/ prefix uploads/
        if (/^(uploads\/)/i.test(p)) {
          // OK
        } else if (/^vehicles\//i.test(p)) {
          p = 'uploads/' + p;
        } else if (/^homeowners\//i.test(p)) {
          p = 'uploads/' + p;
        } else {
          // Bare filename — decide folder based on kind
          if (kind === 'vehicle') p = 'uploads/vehicles/' + p;
          else if (kind === 'owner') p = 'uploads/homeowners/' + p;
          else p = 'uploads/' + p;
        }

        // Resolve base (vehiscanConfig.baseUrl may be a site-relative path)
        let base = window.vehiscanConfig?.baseUrl || window.baseUrl || '';
        const origin = window.location.origin;
        if (!base) base = origin;
        else if (base.startsWith('//')) base = window.location.protocol + base;
        else if (base.startsWith('/')) base = origin + base;
        else if (!/^https?:\/\//i.test(base)) base = origin + '/' + base.replace(/^\/+/, '');

        return base.replace(/\/$/, '') + '/' + p.replace(/^\/+/, '');
      };

      const imageUrl = buildImageUrl(filePath, type);
      __vsLog(`[GUARD] Attempting to load image from: ${imageUrl}`);

      // Function to check if image URL is accessible
      const checkImage = (url) => {
        return new Promise((resolve) => {
          const img = new Image();
          img.onload = () => resolve(true);
          img.onerror = () => resolve(false);
          img.src = url;
        });
      };

      try {
        // First try HEAD request
        const response = await fetch(imageUrl, { method: 'HEAD' }).catch(() => ({ ok: false }));

        if (response.ok) {
          imageElement.src = imageUrl;
          __vsLog(`[GUARD] Successfully loaded ${type} image`);
        } else {
          // If HEAD fails, try Image loading
          const isValid = await checkImage(imageUrl);
          if (isValid) {
            imageElement.src = imageUrl;
            __vsLog(`[GUARD] Successfully loaded ${type} image after retry`);
          } else {
            __vsLog(`[GUARD] Image not found at ${imageUrl}, using placeholder`);
            imageElement.src = placeholder;
          }
        }
      } catch (error) {
        console.error(`[GUARD] Error loading ${type} image:`, error);
        imageElement.src = placeholder;
      }
    };

    // Load images
    // If the server indicated the file doesn't exist, skip network attempts and use placeholder
    const ownerPath = data.owner_img_url || data.owner_img;
    const carPath = data.car_img_url || data.car_img;

    if (data.owner_img_exists === false) {
      __vsLog('[GUARD] Server reports owner image missing, using placeholder');
      ownerImage.src = PLACEHOLDER_OWNER;
    } else {
      tryLoadImage(ownerImage, ownerPath, 'owner', PLACEHOLDER_OWNER);
    }

    if (data.car_img_exists === false) {
      __vsLog('[GUARD] Server reports car image missing, using placeholder');
      carImage.src = PLACEHOLDER_CAR;
    } else {
      tryLoadImage(carImage, carPath, 'vehicle', PLACEHOLDER_CAR);
    }
  }



  // Global error handler to catch unexpected runtime errors and log them
  window.addEventListener('error', function (evt) {
    console.error('[GUARD] Uncaught error:', evt.error || evt.message, evt);
  });

  // Carousel controls with index tracking and lock
  let lastClickTime = 0;
  let isNavigating = false;
  const DEBOUNCE_TIME = 300; // Prevent rapid clicking

  async function navigateCarousel(direction) {
    // Prevent navigation if already in progress
    if (isNavigating) {
      __vsLog('[GUARD] Navigation in progress, skipping request');
      return;
    }

    const now = Date.now();
    if (now - lastClickTime < DEBOUNCE_TIME) return;
    lastClickTime = now;

    if (allHomeowners.length === 0) return;

    isNavigating = true;
    try {
      const currentId = allHomeowners[currentIndex]?.id;

      // Calculate new index
      if (direction === 'next') {
        currentIndex = (currentIndex + 1) % allHomeowners.length;
      } else {
        currentIndex = (currentIndex - 1 + allHomeowners.length) % allHomeowners.length;
      }

      // Ensure we're not showing the same homeowner
      if (allHomeowners[currentIndex]?.id === currentId) {
        if (direction === 'next') {
          currentIndex = (currentIndex + 1) % allHomeowners.length;
        } else {
          currentIndex = (currentIndex - 1 + allHomeowners.length) % allHomeowners.length;
        }
      }

      await displayHomeowner(currentIndex);

      __vsLog(`[GUARD] Navigated ${direction}:`, {
        newIndex: currentIndex,
        totalItems: allHomeowners.length,
        currentHomeowner: allHomeowners[currentIndex]?.name,
        homeownerId: allHomeowners[currentIndex]?.id
      });
    } finally {
      isNavigating = false;
    }
  }

  // Attach navigation event handlers (guard against missing elements)
  if (prevBtn) prevBtn.addEventListener('click', () => navigateCarousel('prev'));
  else console.warn('[GUARD] prevOwner button not found');
  if (nextBtn) nextBtn.addEventListener('click', () => navigateCarousel('next'));
  else console.warn('[GUARD] nextOwner button not found');

  // Add keyboard navigation (defensive - ensure button exists)
  document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') {
      if (prevBtn) prevBtn.click();
    } else if (e.key === 'ArrowRight') {
      if (nextBtn) nextBtn.click();
    }
  });

  // Search functionality with better index handling
  let searchTimeout;
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      clearTimeout(searchTimeout);

      searchTimeout = setTimeout(() => {
        const query = this.value.toLowerCase().trim();

        if (query === '') {
          // Don't reset to first item if we're just clearing the search
          return;
        }

        // Find all matching homeowners
        const matches = allHomeowners.filter(h =>
          (h.name && h.name.toLowerCase().includes(query)) ||
          (h.plate_number && h.plate_number.toLowerCase().includes(query)) ||
          (h.address && h.address.toLowerCase().includes(query))
        );

        if (matches.length > 0) {
          // Find the first match that comes after our current position
          const nextMatch = matches.find(h =>
            allHomeowners.indexOf(h) > currentIndex
          );

          // If found, use that, otherwise use the first match
          if (nextMatch) {
            currentIndex = allHomeowners.indexOf(nextMatch);
          } else {
            currentIndex = allHomeowners.indexOf(matches[0]);
          }

          displayHomeowner(currentIndex);
        }
      }, 300); // Debounce search for performance
    });
  } else {
    console.warn('[GUARD] Search input not found');
  }

  // Clear button - only clears the search input
  if (clearSearch) {
    clearSearch.addEventListener('click', () => {
      if (searchInput) {
        searchInput.value = '';
        searchInput.focus();
        console.log('[GUARD] Search input cleared');
      }
    });
  } else {
    console.warn('[GUARD] clearSearch button not found');
  }

  // Camera toggle: the dedicated camera modules handle camera lifecycle and UI binding.
  // `main-camera-handler.js` and `camera-handler.js` expose `window.startCamera`,
  // `window.stopCamera`, `window.startFloatingCamera` and `window.stopFloatingCamera`.
  if (toggleCamera) {
    __vsLog('[GUARD] Camera UI is managed by main-camera-handler.js');
  } else {
    __vsLog('[GUARD] toggleCamera button not found');
  }

  // Logout with confirmation
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async (e) => {
      e.preventDefault();

      const result = await Swal.fire({
        title: 'Confirm Logout',
        text: 'Are you sure you want to logout?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Logout',
        cancelButtonText: 'Cancel',
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--guard-warn') || getComputedStyle(document.documentElement).getPropertyValue('--warn') || '#ef4444',
        cancelButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--guard-accent') || getComputedStyle(document.documentElement).getPropertyValue('--accent') || '#6b7280',
        heightAuto: false,
        reverseButtons: true
      });

      if (result.isConfirmed) {
        try {
          const res = await fetch('../../auth/logout.php', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          });

          if (res.ok) {
            window.location.href = '../../auth/login.php';
          } else {
            throw new Error('Logout failed');
          }
        } catch (err) {
          console.error('[GUARD] Logout error:', err);
          window.location.href = '../../auth/logout.php';
        }
      }
    });
  } else {
    console.warn('[GUARD] logoutBtn not found');
  }

  // Refresh button - reloads homeowners list from server
  const reloadBtn = document.getElementById('reloadHomeowners') || document.getElementById('reloadList');
  if (reloadBtn) {
    console.log('[GUARD] reload button found, attaching click handler');
    reloadBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      try {
        reloadBtn.disabled = true;

        // Show loading state
        if (window.toast) {
          window.toast.info('🔄 Refreshing homeowners list...');
        }

        // Reload all homeowners from server
        await loadHomeowners();

        // Success message
        if (window.toast) {
          window.toast.success(`✅ Refreshed! ${allHomeowners.length} homeowners loaded`);
        }

        console.log('[GUARD] Homeowners list refreshed successfully');
      } catch (err) {
        console.error('[GUARD] Refresh error:', err);
        if (window.toast) {
          window.toast.error('❌ Failed to refresh homeowners list');
        }
      } finally {
        reloadBtn.disabled = false;
      }
    });
  } else {
    console.warn('[GUARD] reload button not present at bind time, adding delegated listener for #reloadHomeowners');
    // Delegated click handler as a fallback
    document.body.addEventListener('click', async (e) => {
      const target = e.target;
      if (!target) return;
      if (target.id === 'reloadHomeowners' || target.closest && target.closest('#reloadHomeowners')) {
        e.preventDefault();
        try {
          console.log('[GUARD] Delegated refresh click detected');
          await loadHomeowners();
          if (window.toast) {
            window.toast.success(`✅ Refreshed! ${allHomeowners.length} homeowners loaded`);
          }
        } catch (err) {
          console.error('[GUARD] Delegated refresh error:', err);
          if (window.toast) {
            window.toast.error('❌ Failed to refresh');
          }
        }
      }
    });
  }

  // Refresh only the currently displayed homeowner by plate number
  async function refreshCurrentHomeowner() {
    try {
      if (!Array.isArray(allHomeowners) || allHomeowners.length === 0) return;
      const current = allHomeowners[currentIndex];
      if (!current || !current.plate_number) return;

      // Request single homeowner by plate (tolerant) to get updated image flags/urls
      const endpoint = window.vehiscanConfig?.apiEndpoints?.homeowners || 'fetch_homeowners.php';
      const base = window.vehiscanConfig?.baseUrl || window.baseUrl || window.location.origin;
      let baseResolved = base;
      if (baseResolved.startsWith('/')) baseResolved = window.location.origin + baseResolved;
      else if (!/^https?:\/\//i.test(baseResolved)) baseResolved = window.location.origin + '/' + baseResolved.replace(/^\/+/, '');
      const url = new URL(endpoint, baseResolved);
      url.searchParams.set('plate', current.plate_number);

      const res = await fetch(url.toString(), { credentials: 'same-origin' });
      if (!res.ok) {
        throw new Error('Failed to fetch homeowner details');
      }
      const payload = await res.json();
      const single = Array.isArray(payload) ? payload[0] : (payload.data ? payload.data[0] : payload[0]);
      if (single) {
        // Replace the homeowner entry in our list with fresh data
        allHomeowners[currentIndex] = Object.assign({}, allHomeowners[currentIndex], single);
        // Re-display using updated data
        displayHomeowner(currentIndex, true);
      }
    } catch (err) {
      console.error('[GUARD] refreshCurrentHomeowner error:', err);
    }
  }

  // ====== LOGS SEARCH & FILTER ======
  let currentFilter = null; // 'today', 'in', 'out', 'visitors', or null
  let allLogs = []; // Store all logs for filtering
  let dateRangeFilter = null; // { start: Date, end: Date }

  function updateLogsCounter() {
    // Counter is now server-rendered with pagination info
    // This function updates when client-side filters are applied
    const allRows = document.querySelectorAll('#logsContainerWrapper tr.log-row[data-log-id]');
    const visibleRows = Array.from(allRows).filter(row => row.style.display !== 'none');
    const counter = document.getElementById('logsCounter');

    // Only update if filters are active (otherwise keep server-rendered text)
    if (counter && (currentFilter || dateRangeFilter || window.activeUserFilter)) {
      counter.textContent = `Showing ${visibleRows.length} of ${allRows.length} logs (filtered)`;
    }
  }

  function highlightSearchTerms(text, searchTerm) {
    if (!searchTerm) return text;
    const regex = new RegExp(`(${searchTerm})`, 'gi');
    return text.replace(regex, '<span class="search-highlight">$1</span>');
  }

  function filterLogs() {
    // NOTE: With server-side pagination, filters only work on current page
    // To filter all logs, would need to send filter params to server
    const searchTerm = document.getElementById('logsSearch')?.value.toLowerCase() || '';
    const rows = document.querySelectorAll('#logsContainerWrapper tr.log-row[data-log-id]');

    if (rows.length === 0) {
      __vsLog('[GUARD] No log rows found for filtering');
      return;
    }

    let visibleCount = 0;
    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      const logDate = row.dataset.logDate;
      const status = row.dataset.status;
      let show = true;

      // Text search
      if (searchTerm && !text.includes(searchTerm)) {
        show = false;
      }

      // Filter by status
      if (currentFilter === 'in' && status !== 'IN') {
        show = false;
      }
      if (currentFilter === 'out' && status !== 'OUT') {
        show = false;
      }

      // Filter visitors only (check for visitor pass data attribute)
      // Only show logs with active visitor passes
      if (currentFilter === 'visitors') {
        const hasVisitorPass = row.getAttribute('data-visitor') === '1';
        if (!hasVisitorPass) {
          show = false;
        }
      }

      // Filter by today
      if (currentFilter === 'today') {
        const today = new Date().toDateString();
        const rowDateObj = new Date(logDate);
        if (rowDateObj.toDateString() !== today) {
          show = false;
        }
      }

      // Filter by date range
      if (dateRangeFilter && logDate) {
        const rowDate = new Date(logDate);
        if (rowDate < dateRangeFilter.start || rowDate > dateRangeFilter.end) {
          show = false;
        }
      }

      row.style.display = show ? '' : 'none';
      if (show) visibleCount++;
    });

    // Update counter or show message
    __vsLog('[GUARD] Filtered logs:', visibleCount, 'visible out of', rows.length);

    // Show empty state if no results
    if (visibleCount === 0 && rows.length > 0) {
      // Could add empty state message here if needed
    }
  }

  // Search input
  const logsSearch = document.getElementById('logsSearch');
  if (logsSearch) {
    logsSearch.addEventListener('input', filterLogs);

    // Show history on focus
    logsSearch.addEventListener('focus', showSearchHistory);
    logsSearch.addEventListener('blur', hideSearchHistory);

    // Add to history on Enter
    logsSearch.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && logsSearch.value.trim()) {
        addToSearchHistory(logsSearch.value.trim());
        hideSearchHistory();
      }
    });

    // Keyboard shortcut: Ctrl/Cmd + K
    document.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        logsSearch.focus();
        logsSearch.select();
      }
    });
  }

  // Status filter dropdown
  const statusFilter = document.getElementById('statusFilter');
  if (statusFilter) {
    statusFilter.addEventListener('change', (e) => {
      const value = e.target.value;
      if (value === 'all') {
        currentFilter = null;
      } else if (value === 'in') {
        currentFilter = 'in';
      } else if (value === 'out') {
        currentFilter = 'out';
      }
      filterLogs();
    });
  }

  // Filter buttons
  const filterToday = document.getElementById('filterToday');
  const filterIn = document.getElementById('filterIn');
  const filterOut = document.getElementById('filterOut');
  const clearLogsFilter = document.getElementById('clearLogsFilter');

  // Helper function to close dropdowns
  function closeFilterDropdown() {
    const dropdown = document.getElementById('filterDropdownContent');
    if (dropdown) dropdown.classList.add('hidden');
  }

  // Helper function to update filter button text
  function updateFilterButtonText(icon, text) {
    const filterBtn = document.getElementById('filterDropdownBtn');
    if (filterBtn) {
      const btnIcon = filterBtn.querySelector('.btn-icon');
      const btnText = filterBtn.querySelector('.btn-text');
      if (btnIcon) btnIcon.textContent = icon;
      if (btnText) btnText.textContent = text;
    }
  }

  if (filterToday) {
    filterToday.addEventListener('click', () => {
      currentFilter = currentFilter === 'today' ? null : 'today';
      // Toggle active class is handled by toggle group
      filterIn.classList.remove('toggle-active');
      filterOut.classList.remove('toggle-active');
      filterVisitors?.classList.remove('toggle-active');

      filterLogs();
    });
  }

  if (filterIn) {
    filterIn.addEventListener('click', () => {
      currentFilter = currentFilter === 'in' ? null : 'in';
      // Toggle active class is handled by toggle group
      filterToday.classList.remove('toggle-active');
      filterOut.classList.remove('toggle-active');
      filterVisitors?.classList.remove('toggle-active');

      filterLogs();
    });
  }

  if (filterOut) {
    filterOut.addEventListener('click', () => {
      currentFilter = currentFilter === 'out' ? null : 'out';
      // Toggle active class is handled by toggle group
      filterToday.classList.remove('toggle-active');
      filterIn.classList.remove('toggle-active');
      filterVisitors?.classList.remove('toggle-active');

      filterLogs();
    });
  }

  // Filter visitors
  const filterVisitors = document.getElementById('filterVisitors');
  if (filterVisitors) {
    filterVisitors.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();

      const isActive = filterVisitors.classList.contains('toggle-active');

      __vsLog('[VISITOR] Filter clicked, isActive:', isActive);

      // Deactivate other filters
      filterToday?.classList.remove('toggle-active');
      filterIn?.classList.remove('toggle-active');
      filterOut?.classList.remove('toggle-active');

      if (isActive) {
        // Deactivate and show all logs
        __vsLog('[VISITOR] Deactivating, loading regular logs');
        filterVisitors.classList.remove('toggle-active');
        currentFilter = null;
        loadLogs();
      } else {
        // Activate and fetch visitor passes
        __vsLog('[VISITOR] Activating, fetching passes...');
        filterVisitors.classList.add('toggle-active');
        currentFilter = 'visitors';

        try {
          __vsLog('[VISITOR] Fetching active visitor passes...');
          const response = await fetch('../fetch/fetch_visitors.php');

          if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
          }

          const data = await response.json();
          __vsLog('[VISITOR] Response:', data);

          if (data.success && data.passes && data.passes.length > 0) {
            __vsLog(`[VISITOR] Loaded ${data.passes.length} visitor passes`);
            displayVisitorPasses(data.passes);
            window.toast.show(`Loaded ${data.passes.length} active visitor passes`, 'success');
          } else {
            __vsLog('[VISITOR] No active visitor passes found');
            window.toast.show('No active visitor passes found', 'info');
            displayVisitorPasses([]); // Show empty state
          }
        } catch (error) {
          console.error('[VISITOR] Error fetching visitor passes:', error);
          window.toast.show('Failed to load visitor passes: ' + error.message, 'error');
          filterVisitors.classList.remove('toggle-active');
          currentFilter = null;
          loadLogs();
        }
      }
    }, true); // USE CAPTURE PHASE - runs before toggle group
  }

  // Function to display visitor passes in the logs table
  function displayVisitorPasses(passes) {
    const logsContainer = document.getElementById('logsContainerWrapper');
    if (!logsContainer) {
      console.error('[VISITOR] logsContainerWrapper element not found');
      return;
    }

    if (passes.length === 0) {
      logsContainer.innerHTML = `
        <div class="logs-table-container">
          <div class="flex flex-col items-center justify-center py-16 text-gray-500 dark:text-gray-400">
            <svg class="h-16 w-16 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
            </svg>
            <p class="text-lg font-medium mb-2">No Active Visitor Passes</p>
            <p class="text-sm text-gray-400 dark:text-gray-500">Visitor passes will appear here when approved by admin</p>
          </div>
        </div>
      `;
      return;
    }

    // Create full table HTML
    const tableHTML = `
      <div class="logs-table-container">
        <table class="w-full">
          <thead class="bg-gray-50 dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Visitor</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Plate</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Purpose</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valid Period</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">QR Code</th>
            </tr>
          </thead>
          <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
            ${passes.map(pass => {
      const validFrom = new Date(pass.valid_from);
      const validUntil = new Date(pass.valid_until);
      const now = new Date();
      const isCurrentlyValid = now >= validFrom && now <= validUntil;

      return `
        <tr class="hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors border-b border-gray-200 dark:border-slate-700">
          <td class="px-6 py-4">
            <div class="text-sm font-medium text-gray-900 dark:text-white">${escapeHtml(pass.visitor_name)}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Visitor Pass</div>
          </td>
          <td class="px-6 py-4">
            <div class="text-sm text-gray-900 dark:text-white font-mono">${escapeHtml(pass.visitor_plate || 'Walk-in')}</div>
          </td>
          <td class="px-6 py-4">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${isCurrentlyValid ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'}">
              ${isCurrentlyValid ? '✓ Active Now' : '⏳ Scheduled'}
            </span>
          </td>
          <td class="px-6 py-4">
            <div class="text-sm text-gray-900 dark:text-white">${escapeHtml(pass.purpose || 'Visit')}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Host: ${escapeHtml(pass.homeowner_name || 'Unknown')}</div>
          </td>
          <td class="px-6 py-4">
            <div class="text-sm text-gray-900 dark:text-white">${formatDateTime(pass.valid_from)}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">Until: ${formatDateTime(pass.valid_until)}</div>
          </td>
          <td class="px-6 py-4 text-right">
            ${pass.qr_code ? `
              <button onclick="viewVisitorPassQR('${escapeHtml(pass.qr_code)}')" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium">
                View QR
              </button>
            ` : '<span class="text-gray-400 text-xs">No QR</span>'}
          </td>
        </tr>
      `;
    }).join('')}
          </tbody>
        </table>
      </div>
    `;

    logsContainer.innerHTML = tableHTML;
    __vsLog(`[VISITOR] Displayed ${passes.length} visitor passes in table`);
  }

  // Helper function to format date/time
  function formatDateTime(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  // Helper function to escape HTML
  function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Global function to view visitor pass QR code
  window.viewVisitorPassQR = function (qrCode) {
    Swal.fire({
      title: 'Visitor Pass QR Code',
      html: `<img src="${qrCode}" alt="QR Code" style="max-width: 300px; margin: 0 auto; image-rendering: pixelated;">`,
      confirmButtonText: 'Close',
      width: 400,
      heightAuto: false
    });
  };

  // Date range filter
  const filterDateRange = document.getElementById('filterDateRange');
  if (filterDateRange) {
    filterDateRange.addEventListener('click', async () => {
      const result = await Swal.fire({
        title: 'Select Date Range',
        html: `
          <div style="display: flex; flex-direction: column; gap: 1rem; text-align: left;">
            <div>
              <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">From:</label>
              <input type="date" id="dateFrom" class="swal2-input" style="margin: 0; width: 100%;">
            </div>
            <div>
              <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">To:</label>
              <input type="date" id="dateTo" class="swal2-input" style="margin: 0; width: 100%;">
            </div>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Apply',
        cancelButtonText: 'Clear',
        heightAuto: false,
        preConfirm: () => {
          const from = document.getElementById('dateFrom').value;
          const to = document.getElementById('dateTo').value;

          if (!from || !to) {
            Swal.showValidationMessage('Please select both dates');
            return false;
          }

          return { from, to };
        }
      });

      if (result.isConfirmed && result.value) {
        dateRangeFilter = {
          start: new Date(result.value.from),
          end: new Date(result.value.to + 'T23:59:59')
        };
        filterDateRange.classList.add('active');

        // Update button text with date range
        const fromDate = result.value.from.split('-').slice(1).join('/');
        const toDate = result.value.to.split('-').slice(1).join('/');
        updateFilterButtonText('📅', `${fromDate}-${toDate}`);

        filterLogs();

        if (window.toast) {
          window.toast.success(`📅 Filtered: ${result.value.from} to ${result.value.to}`);
        }
      } else if (result.dismiss === Swal.DismissReason.cancel) {
        dateRangeFilter = null;
        filterDateRange.classList.remove('active');
        updateFilterButtonText('⚙️', 'Filters');
        filterLogs();
      }
      closeFilterDropdown();
    });
  }

  if (clearLogsFilter) {
    clearLogsFilter.addEventListener('click', () => {
      currentFilter = null;
      dateRangeFilter = null;
      window.activeUserFilter = null;
      if (logsSearch) logsSearch.value = '';

      // Remove toggle-active from all toggle group items
      if (filterToday) filterToday.classList.remove('toggle-active');
      if (filterIn) filterIn.classList.remove('toggle-active');
      if (filterOut) filterOut.classList.remove('toggle-active');
      if (filterVisitors) filterVisitors.classList.remove('toggle-active');
      if (filterDateRange) filterDateRange.classList.remove('toggle-active');

      // Show all log rows
      const logRows = document.querySelectorAll('#logsContainerWrapper tr.log-row[data-log-id]');
      logRows.forEach(row => {
        row.style.display = '';
      });

      __vsLog('[GUARD] Cleared all filters, showing', logRows.length, 'rows');

      if (window.toast) {
        window.toast.success('✨ All filters cleared');
      }
    });
  }

  // CSV Export Function (reusable)
  function exportLogsToCSV(filename) {
    const table = document.getElementById('logsTable');
    if (!table) return 0;

    const rows = [];
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent);
    rows.push(headers.join(','));

    // Export ALL rows (not just visible)
    const allRows = Array.from(table.querySelectorAll('tbody tr'));

    allRows.forEach(row => {
      const cells = Array.from(row.querySelectorAll('td')).map(td => {
        let text = td.textContent.trim();
        // Remove emojis and clean up
        text = text.replace(/[🆕🚗🟢🔴🎫]/g, '').trim();
        // Escape commas and quotes
        if (text.includes(',') || text.includes('"')) {
          text = `"${text.replace(/"/g, '""')}"`;
        }
        return text;
      });
      rows.push(cells.join(','));
    });

    const csvContent = rows.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);

    return allRows.length;
  }

  // CSV Export Button
  // Helper function to close actions dropdown
  function closeActionsDropdown() {
    const dropdown = document.getElementById('actionsDropdownContent');
    if (dropdown) dropdown.classList.add('hidden');
  }

  const exportLogs = document.getElementById('exportLogs');
  if (exportLogs) {
    exportLogs.addEventListener('click', () => {
      const count = exportLogsToCSV(`guard_logs_${new Date().toISOString().split('T')[0]}.csv`);

      if (window.toast) {
        window.toast.success(`📥 Exported ${count} logs to CSV`);
      }
      closeActionsDropdown();
    });
  }

  // Clear All Logs Button
  const clearAllLogsBtn = document.getElementById('clearAllLogs');
  if (clearAllLogsBtn) {
    clearAllLogsBtn.addEventListener('click', async () => {
      console.log('[GUARD] Clear all logs button clicked');

      // Get current log count
      const logsTable = document.getElementById('logsTable');
      const currentLogs = logsTable ? logsTable.querySelectorAll('tbody tr').length : 0;

      if (currentLogs === 0) {
        if (window.toast) {
          window.toast.warning('⚠️ No logs to clear');
        }
        return;
      }

      // Confirmation dialog
      const result = await Swal.fire({
        title: 'Clear All Logs?',
        html: `
          <p>This will permanently delete <strong>${currentLogs} log(s)</strong> from the database.</p>
          <p style="margin-top: 10px; color: var(--guard-success, #16a34a);">✅ A backup CSV will be downloaded automatically before deletion.</p>
          <p style="margin-top: 10px; color: var(--guard-warn, #dc2626); font-weight: 600;">⚠️ This action cannot be undone!</p>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '🗑️ Yes, Clear All Logs',
        cancelButtonText: 'Cancel',
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--guard-warn') || getComputedStyle(document.documentElement).getPropertyValue('--warn') || '#dc2626',
        cancelButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--guard-accent') || getComputedStyle(document.documentElement).getPropertyValue('--accent') || '#6b7280',
        reverseButtons: true,
        heightAuto: false
      });

      if (!result.isConfirmed) {
        console.log('[GUARD] User cancelled clear all logs');
        return;
      }

      try {
        // Step 1: Download CSV backup
        if (window.toast) {
          window.toast.info('📥 Creating backup...');
        }

        const timestamp = new Date().toISOString().replace(/[:.]/g, '-').split('T')[0];
        const backupFilename = `guard_logs_backup_${timestamp}_${Date.now()}.csv`;
        const exportedCount = exportLogsToCSV(backupFilename);

        console.log('[GUARD] Backup created:', backupFilename, 'Count:', exportedCount);

        // Wait a moment for download to start
        await new Promise(resolve => setTimeout(resolve, 500));

        // Step 2: Call API to clear all logs
        if (window.toast) {
          window.toast.info('🗑️ Clearing logs from database...');
        }

        const response = await fetch('../clear_all_logs.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'same-origin'
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        console.log('[GUARD] Clear logs response:', data);

        if (data.success) {
          // Success notification - using modal only (no duplicate toast)
          await Swal.fire({
            title: 'Success!',
            html: `
              <p>✅ <strong>${data.deleted_count || exportedCount} log(s)</strong> have been cleared.</p>
              <p style="margin-top: 10px;">📥 Backup saved as: <code style="font-size: 11px;">${backupFilename}</code></p>
            `,
            icon: 'success',
            confirmButtonText: 'OK',
            confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--guard-success') || getComputedStyle(document.documentElement).getPropertyValue('--success') || '#16a34a',
            heightAuto: false
          });

          // Reload logs to show empty state
          await loadLogs();
          clearNewLogsBadge();
        } else {
          throw new Error(data.message || 'Failed to clear logs');
        }

      } catch (error) {
        console.error('[GUARD] Error clearing logs:', error);

        await Swal.fire({
          title: 'Error',
          html: `
            <p>❌ Failed to clear logs from database.</p>
            <p style="margin-top: 10px; font-size: 12px; color: var(--text-muted);">${error.message}</p>
            <p style="margin-top: 10px;">✅ However, your backup CSV was downloaded successfully.</p>
          `,
          icon: 'error',
          confirmButtonText: 'OK',
          confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--guard-warn') || getComputedStyle(document.documentElement).getPropertyValue('--warn') || '#dc2626',
          heightAuto: false
        });

        if (window.toast) {
          window.toast.error('❌ Failed to clear logs');
        }
      }
      closeActionsDropdown();
    });
  }

  // Refresh logs button (reload current page - matching admin panel pattern)
  const refreshLogsBtn = document.getElementById('refreshLogs');
  if (refreshLogsBtn) {
    refreshLogsBtn.addEventListener('click', async () => {
      console.log('[GUARD] Manual refresh triggered for page', currentLogPage);
      refreshLogsBtn.disabled = true;
      const originalContent = refreshLogsBtn.innerHTML;
      refreshLogsBtn.innerHTML = '<svg class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg><span>Refreshing...</span>';

      await loadLogs(currentLogPage);

      refreshLogsBtn.disabled = false;
      refreshLogsBtn.innerHTML = originalContent;

      if (window.toast) {
        window.toast.success('✅ Logs refreshed');
      }
    });
  }

  // Refresh All button (sidebar)
  const refreshAllBtn = document.getElementById('refreshAllBtn');
  if (refreshAllBtn) {
    refreshAllBtn.addEventListener('click', async () => {
      console.log('[GUARD] Refresh All triggered');

      const originalText = refreshAllBtn.innerHTML;
      refreshAllBtn.disabled = true;
      refreshAllBtn.innerHTML = '<svg class="h-4 w-4 flex-shrink-0 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg><span>Refreshing...</span>';

      await Promise.all([loadLogs(), loadHomeowners(true)]);

      refreshAllBtn.disabled = false;
      refreshAllBtn.innerHTML = originalText;

      if (window.toast) {
        window.toast.success('✅ All data refreshed');
      }
    });
  }

  // Export Logs button (sidebar)
  const exportLogsBtn = document.getElementById('exportLogsBtn');
  if (exportLogsBtn) {
    exportLogsBtn.addEventListener('click', async () => {
      console.log('[GUARD] Export Logs triggered');

      try {
        const response = await fetch('../export_logs.php');
        if (!response.ok) throw new Error('Export failed');

        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `access_logs_${new Date().toISOString().split('T')[0]}.csv`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        if (window.toast) {
          window.toast.success('✅ Logs exported successfully');
        }
      } catch (error) {
        console.error('[GUARD] Export error:', error);
        if (window.toast) {
          window.toast.error('❌ Failed to export logs');
        }
      }
    });
  }

  // REMOVED: Delete All Logs button - Guards can no longer delete logs (security restriction)
  // Only administrators can delete logs from the admin panel

  // Update counter after each loadLogs
  const originalLoadLogs = loadLogs;
  loadLogs = async function (page = 1) {
    await originalLoadLogs(page);
    setTimeout(updateLogsCounter, 100); // Wait for DOM update
  };

  // ====== LIVE LOG COUNTER BADGE ======
  let badgeCounter = 0;

  function updateNewLogsBadge(count) {
    badgeCounter += count;
    const badge = document.getElementById('newLogsBadge');
    const badgeCount = document.getElementById('newLogsCount');

    if (badge && badgeCount && badgeCounter > 0) {
      badgeCount.textContent = badgeCounter;
      badge.style.display = 'flex';
    }
  }

  function clearNewLogsBadge() {
    badgeCounter = 0;
    const badge = document.getElementById('newLogsBadge');
    if (badge) {
      badge.style.display = 'none';
    }
  }

  // Clear badge on click
  const newLogsBadge = document.getElementById('newLogsBadge');
  if (newLogsBadge) {
    newLogsBadge.addEventListener('click', clearNewLogsBadge);
  }

  // Clear badge when user interacts with logs container
  const logsContainerElement = document.getElementById('logsContainerWrapper');
  if (logsContainerElement) {
    logsContainerElement.addEventListener('click', clearNewLogsBadge);
    logsContainerElement.addEventListener('scroll', clearNewLogsBadge);

    // Event delegation for table row clicks
    logsContainerElement.addEventListener('click', (e) => {
      const row = e.target.closest('tr.log-row[data-log-id]');
      if (row) {
        const plateNumber = row.dataset.plate;
        const userName = row.dataset.name;
        if (plateNumber) {
          __vsLog('[GUARD] Log row clicked:', userName, plateNumber);
          // Show homeowner details or filter by user
          window.jumpToHomeowner?.(plateNumber);
        }
      }
    });
  }

  // ====== IMAGE ZOOM FUNCTIONALITY ======
  window.openImageZoom = function (src) {
    const modal = document.getElementById('imageZoomModal');
    const img = document.getElementById('zoomedImage');
    if (modal && img && src) {
      img.src = src;
      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }
  };

  window.closeImageZoom = function () {
    const modal = document.getElementById('imageZoomModal');
    if (modal) {
      modal.classList.add('hidden');
      document.body.style.overflow = 'auto';
    }
  };

  // Add click handlers to images
  document.querySelectorAll('[data-zoom-target]').forEach(container => {
    container.addEventListener('click', function () {
      const targetId = this.dataset.zoomTarget;
      const img = document.getElementById(targetId);
      if (img && img.src && !img.src.includes('data:image/svg')) {
        openImageZoom(img.src);
      }
    });
  });

  // ESC key to close
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeImageZoom();
    }
  });
  // ====== FILTER LOGS BY USER ======
  window.filterLogsByUser = function (plateNumber, userName) {
    console.log('[GUARD] Filtering logs by user:', userName, plateNumber);

    // Store the active filter
    window.activeUserFilter = plateNumber;

    // Get all log rows
    const logRows = document.querySelectorAll('#logsContainerWrapper tr.log-row[data-log-id]');
    let visibleCount = 0;

    logRows.forEach(row => {
      const rowPlate = row.getAttribute('data-plate');
      if (rowPlate && rowPlate.toLowerCase() === plateNumber.toLowerCase()) {
        row.style.display = '';
        visibleCount++;
      } else {
        row.style.display = 'none';
      }
    });

    // Update logs counter
    const counter = document.getElementById('logsCounter');
    if (counter) {
      counter.textContent = `Showing ${visibleCount} logs for ${userName}`;
    }

    // Show toast notification
    if (window.toast) {
      window.toast.info(`📊 Filtered to show ${userName}'s logs. Click "Clear" to reset.`);
    }

    // Highlight the clear button
    const clearBtn = document.getElementById('clearLogsFilter');
    if (clearBtn) {
      clearBtn.classList.add('ring-2', 'ring-blue-400', 'animate-pulse');
      setTimeout(() => {
        clearBtn.classList.remove('animate-pulse');
      }, 2000);
    }
  };

  // ====== JUMP TO HOMEOWNER FROM LOG ======
  window.jumpToHomeowner = async function (plateNumber) {
    console.log('[GUARD] Jump to homeowner:', plateNumber);

    if (!plateNumber || !Array.isArray(allHomeowners)) return;

    const index = allHomeowners.findIndex(h =>
      h.plate_number && h.plate_number.toLowerCase() === plateNumber.toLowerCase()
    );

    if (index !== -1) {
      currentIndex = index;
      await displayHomeowner(currentIndex);

      if (window.toast) {
        window.toast.success(`🚗 Showing: ${allHomeowners[index].name}`);
      }

      // Smooth scroll to homeowner section
      document.querySelector('.homeowner-box')?.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest'
      });
    } else {
      if (window.toast) {
        window.toast.warning(`⚠️ Homeowner not found for plate: ${plateNumber}`);
      }
    }
  };

  // ====== KEYBOARD SHORTCUTS ======
  document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + R - Refresh logs
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
      e.preventDefault();
      document.getElementById('refreshLogs')?.click();
    }

    // Ctrl/Cmd + E - Export CSV
    if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
      e.preventDefault();
      document.getElementById('exportLogs')?.click();
    }
  });

  // ====== VISITOR PASSES FUNCTIONALITY ======
  async function loadVisitorPasses() {
    const container = document.getElementById('visitorPassesContainer');
    if (!container) {
      console.warn('[VISITOR] Container not found');
      return;
    }

    // Skeleton loader
    container.innerHTML = `
      <div class="col-span-full">
        <div class="skeleton skeleton-card"></div>
        <div class="skeleton skeleton-card"></div>
        <div class="skeleton skeleton-card"></div>
      </div>
    `;

    try {
      const res = await fetch('../fetch/fetch_visitors.php');

      console.log('[VISITOR] Response status:', res.status);

      if (!res.ok) {
        const errorText = await res.text();
        console.error('[VISITOR] Fetch failed:', res.status, errorText);
        throw new Error(`Server error: ${res.status}`);
      }

      const data = await res.json();

      if (!data.success || !data.passes || data.passes.length === 0) {
        container.innerHTML = '<div class="col-span-full text-center py-12"><div class="text-6xl mb-4">🎫</div><p class="text-gray-600">No visitor passes found</p></div>';
        return;
      }

      const passes = data.passes;

      // Render cards
      container.innerHTML = passes.map(pass => {
        const statusColors = {
          active: 'bg-green-100 text-green-800 border-green-500',
          used: 'bg-blue-100 text-blue-800 border-blue-500',
          expired: 'bg-yellow-100 text-yellow-800 border-yellow-500',
          cancelled: 'bg-red-100 text-red-800 border-red-500'
        };

        const statusColor = statusColors[pass.status] || 'bg-[color:var(--badge-in-bg)] text-[color:var(--badge-in-text)] border-[color:var(--border)]';

        // Format dates
        const formatDate = (dateStr) => {
          const date = new Date(dateStr);
          return date.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          });
        };

        const qrCodeHtml = pass.qr_code ? `
          <div class="flex items-center justify-center bg-white p-2 rounded border border-gray-200">
            <img src="${pass.qr_code}" alt="QR Code" class="w-24 h-24 qr-clickable" style="image-rendering: pixelated;" title="Click to zoom">
          </div>
        ` : '';

        return `
          <div class="bg-white rounded-lg shadow-sm border-l-4 ${statusColor} overflow-hidden hover:shadow-md transition-shadow" style="min-height: 200px; max-height: 320px;">
            <div class="p-3">
              <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                  <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-xl font-bold text-white shadow-sm flex-shrink-0">
                    ${pass.visitor_name.charAt(0).toUpperCase()}
                  </div>
                  <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-gray-900 text-base truncate">${pass.visitor_name}</h3>
                    <p class="text-sm text-gray-600 font-mono font-semibold">${pass.visitor_plate}</p>
                  </div>
                </div>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold uppercase ${statusColor} whitespace-nowrap ml-2">
                  ${pass.status}
                </span>
              </div>
              
              <div class="space-y-2 text-sm mb-3">
                <div class="flex items-center gap-2 text-gray-700">
                  <svg class="w-4 h-4" style="color: var(--accent);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                  </svg>
                  <span class="font-medium truncate">${pass.homeowner_name}</span>
                </div>
              </div>
              
              ${qrCodeHtml}
              
              <div class="space-y-1.5 text-xs bg-gray-50 rounded-lg p-2.5 mt-2">
                <div class="flex items-start gap-2">
                  <span class="text-gray-500 font-semibold whitespace-nowrap">Valid From:</span>
                  <span class="font-medium text-gray-900 text-right flex-1">${formatDate(pass.valid_from)}</span>
                </div>
                <div class="flex items-start gap-2">
                  <span class="text-gray-500 font-semibold whitespace-nowrap">Valid Until:</span>
                  <span class="font-medium text-gray-900 text-right flex-1">${formatDate(pass.valid_until)}</span>
                </div>
              </div>
              
              <div class="mt-2 pt-2 border-t border-gray-200">
                <div class="flex items-center justify-center gap-1.5 text-xs text-gray-500">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                  </svg>
                  <span class="font-semibold">Pass ID: ${pass.id}</span>
                </div>
              </div>
            </div>
          </div>
        `;
      }).join('');

      console.log('[VISITOR] Loaded', passes.length, 'visitor passes');
    } catch (err) {
      console.error('[VISITOR] Error loading passes:', err);
      container.innerHTML = '<div class="col-span-full text-center py-12 text-red-500">Error loading visitor passes</div>';
    }
  }

  // Refresh visitor passes button
  const refreshVisitorBtn = document.getElementById('refreshVisitorPasses');
  if (refreshVisitorBtn) {
    refreshVisitorBtn.addEventListener('click', () => {
      console.log('[VISITOR] Refresh button clicked');
      loadVisitorPasses();
    });
  }

  // Visitor search
  const visitorSearchInput = document.getElementById('visitorSearchInput');
  if (visitorSearchInput) {
    visitorSearchInput.addEventListener('input', function () {
      const searchTerm = this.value.toLowerCase();
      const cards = document.querySelectorAll('#visitorPassesContainer > div');

      cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(searchTerm) ? '' : 'none';
      });
    });
  }

  // Initial load
  loadLogs();
  loadHomeowners();

  console.log('[GUARD] Guard panel initialized successfully');
  console.log('[GUARD] Keyboard shortcuts: Ctrl+K (Search), Ctrl+R (Refresh), Ctrl+E (Export)');
});