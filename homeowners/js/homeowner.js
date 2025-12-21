// Homeowner Portal JavaScript
let currentPage = 'dashboard';
let visitorPasses = [];

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initializeNavigation();
    initializeUserMenu();
    initializeMobileMenu();
    initializeSecurityFeatures();
    initializeScrollEffects();
    updateLiveTime();
    setInterval(updateLiveTime, 1000);
    loadPage('dashboard');
    
    // Add page visibility handler
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

// Navigation
function initializeNavigation() {
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.getAttribute('data-page');
            if (page) {
                loadPage(page);
            }
        });
    });
}

function loadPage(page) {
    // Update active menu item with smooth transition
    document.querySelectorAll('.menu-item').forEach(item => {
        item.classList.remove('active');
    });
    const activeItem = document.querySelector(`[data-page="${page}"]`);
    if (activeItem) {
        activeItem.classList.add('active');
    }
    
    // Update page title with animation
    const titles = {
        'dashboard': 'Dashboard',
        'passes': 'Visitor Passes',
        'vehicles': 'My Vehicles',
        'activity': 'Vehicle Activity',
        'profile': 'My Profile'
    };
    const titleElement = document.getElementById('page-title');
    titleElement.style.opacity = '0';
    titleElement.style.transform = 'translateX(-10px)';
    
    setTimeout(() => {
        titleElement.textContent = titles[page] || page;
        titleElement.style.transition = 'all 0.3s ease';
        titleElement.style.opacity = '1';
        titleElement.style.transform = 'translateX(0)';
    }, 150);
    
    // Show/hide page content with fade effect
    document.querySelectorAll('.page-content').forEach(content => {
        content.classList.remove('active');
    });
    
    const pageContent = document.getElementById(`page-${page}`);
    if (pageContent) {
        // Small delay for smooth transition
        setTimeout(() => {
            pageContent.classList.add('active');
        }, 100);
    }
    
    currentPage = page;
    
    // Load data if needed
    if (page === 'passes') {
        loadVisitorPasses();
    }
    
    // Scroll to top smoothly
    document.querySelector('.flex-1.overflow-y-auto')?.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Mobile Menu
function initializeMobileMenu() {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');
    
    if (mobileMenuBtn && sidebar && overlay) {
        // Toggle menu
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        });
        
        // Close menu when clicking overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        });
        
        // Close menu when clicking menu item on mobile
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('mobile-open');
                    overlay.classList.remove('active');
                }
            });
        });
    }
}

// User Menu
function initializeUserMenu() {
    const userTrigger = document.getElementById('user-trigger');
    const userDropdown = document.getElementById('user-dropdown');
    const signOutBtn = document.getElementById('signOutBtn');
    
    if (userTrigger && userDropdown) {
        userTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            const isHidden = userDropdown.classList.contains('hidden');
            
            if (isHidden) {
                const rect = userTrigger.getBoundingClientRect();
                userDropdown.style.bottom = `${window.innerHeight - rect.top}px`;
                userDropdown.style.left = `${rect.left}px`;
                userDropdown.classList.remove('hidden');
            } else {
                userDropdown.classList.add('hidden');
            }
        });
    }
    
    if (signOutBtn) {
        signOutBtn.addEventListener('click', function() {
            window.location.href = '../auth/logout.php';
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        if (userDropdown && !userDropdown.classList.contains('hidden')) {
            userDropdown.classList.add('hidden');
        }
    });
}

// Security Features
function initializeSecurityFeatures() {
    // Auto-logout warning (5 minutes before timeout)
    const warningTime = 25 * 60 * 1000; // 25 minutes (5 min before 30 min timeout)
    setTimeout(() => {
        Swal.fire({
            icon: 'warning',
            title: 'Session Expiring Soon',
            text: 'Your session will expire in 5 minutes due to inactivity.',
            showCancelButton: true,
            confirmButtonText: 'Stay Logged In',
            cancelButtonText: 'Logout Now',
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#6b7280'
        }).then((result) => {
            if (!result.isConfirmed) {
                window.location.href = '../auth/logout.php';
            } else {
                // Refresh page to reset session
                location.reload();
            }
        });
    }, warningTime);
    
    // Prevent right-click on sensitive areas (optional security)
    // Uncomment if needed
    // document.addEventListener('contextmenu', (e) => {
    //     if (e.target.closest('.stat-card, .pass-card')) {
    //         e.preventDefault();
    //     }
    // });
    
    // Log user activity for security
    let activityTimer;
    const logActivity = () => {
        clearTimeout(activityTimer);
        activityTimer = setTimeout(() => {
            console.log('User inactive for extended period');
        }, 5 * 60 * 1000);
    };
    
    ['mousedown', 'keypress', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, logActivity, { passive: true });
    });
}

// Scroll Effects
function initializeScrollEffects() {
    const contentArea = document.querySelector('.flex-1.overflow-y-auto');
    const header = document.querySelector('header');
    
    if (contentArea && header) {
        contentArea.addEventListener('scroll', () => {
            if (contentArea.scrollTop > 20) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }
}

// Handle page visibility (security feature)
function handleVisibilityChange() {
    if (document.hidden) {
        console.log('User left the page');
    } else {
        console.log('User returned to page');
        // Optional: Check if session is still valid
        checkSessionValidity();
    }
}

// Check session validity
async function checkSessionValidity() {
    try {
        const response = await fetch('api/check_session.php');
        const result = await response.json();
        
        if (!result.valid) {
            Swal.fire({
                icon: 'error',
                title: 'Session Expired',
                text: 'Your session has expired. Please login again.',
                confirmButtonText: 'Login',
                confirmButtonColor: '#3b82f6',
                allowOutsideClick: false
            }).then(() => {
                window.location.href = '../auth/login.php?timeout=1';
            });
        }
    } catch (error) {
        console.error('Session check failed:', error);
    }
}

// Live Time
function updateLiveTime() {
    const timeElement = document.getElementById('liveTime');
    if (timeElement) {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('en-US', { 
            hour12: false, 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit' 
        });
        timeElement.textContent = timeStr;
    }
}

// Load Visitor Passes
async function loadVisitorPasses() {
    try {
        const response = await fetch('api/get_visitor_passes.php');
        const result = await response.json();
        
        if (result.success) {
            visitorPasses = result.passes;
            displayVisitorPasses();
        }
    } catch (error) {
        console.error('Error loading visitor passes:', error);
    }
}

// Display Visitor Passes
function displayVisitorPasses() {
    const container = document.getElementById('passes-list');
    if (!container) return;
    
    if (visitorPasses.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
                </svg>
                <p class="text-lg font-medium">No visitor passes yet</p>
                <p class="text-sm mt-2">Click "Add Visitor Pass" to create one.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = visitorPasses.map(pass => createPassCard(pass)).join('');
}

// Create Pass Card
function createPassCard(pass) {
    const statusClass = pass.display_status === 'active' ? 'active' : 
                       pass.display_status === 'approved' ? 'approved' : '';
    
    const qrButton = (pass.display_status === 'active' || pass.display_status === 'approved') ? `
        <button onclick="viewQRCode('${pass.qr_token}', '${escapeHtml(pass.visitor_name)}')" 
                class="btn-primary">
            📱 View QR Code
        </button>
    ` : '';
    
    const rejectionNote = pass.display_status === 'rejected' && pass.rejection_reason ? `
        <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
            <strong class="flex items-center gap-1">❌ Rejection Reason:</strong>
            <p class="mt-1">${escapeHtml(pass.rejection_reason)}</p>
        </div>
    ` : '';
    
    const pendingNote = pass.display_status === 'pending' ? `
        <div class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded-lg text-xs text-yellow-800">
            ⏳ Waiting for admin approval
        </div>
    ` : '';
    
    return `
        <div class="visitor-pass-card ${statusClass}">
            <div class="flex justify-between items-start mb-3">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-3">
                        <h3 class="font-bold text-lg text-gray-900">${escapeHtml(pass.visitor_name)}</h3>
                        <span class="status-badge status-${pass.display_status}">
                            ${pass.display_status}
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                        <div>
                            <span class="font-medium text-gray-500">Purpose:</span>
                            <p class="text-gray-900 mt-0.5">${escapeHtml(pass.purpose)}</p>
                        </div>
                        ${pass.visitor_plate ? `
                        <div>
                            <span class="font-medium text-gray-500">Vehicle Plate:</span>
                            <p class="text-gray-900 font-mono mt-0.5">${escapeHtml(pass.visitor_plate)}</p>
                        </div>
                        ` : ''}
                        <div>
                            <span class="font-medium text-gray-500">Valid From:</span>
                            <p class="text-gray-900 mt-0.5">${formatDateTime(pass.valid_from)}</p>
                        </div>
                        <div>
                            <span class="font-medium text-gray-500">Valid Until:</span>
                            <p class="text-gray-900 mt-0.5">${formatDateTime(pass.valid_until)}</p>
                        </div>
                    </div>
                    
                    <div class="mt-2 text-xs text-gray-500">
                        Created: ${formatDateTime(pass.created_at)}
                    </div>
                </div>
                
                ${qrButton ? `<div class="ml-4">${qrButton}</div>` : ''}
            </div>
            
            ${rejectionNote}
            ${pendingNote}
        </div>
    `;
}

// Add Visitor Pass
async function showAddVisitorPassModal() {
    const defaults = getDefaultDates();
    
    const { value: formValues } = await Swal.fire({
        title: 'Add Visitor Pass',
        width: '600px',
        html: `
            <div class="text-left space-y-4" style="padding: 10px;">
                <div>
                    <label for="visitor_name" class="block text-sm font-medium text-gray-700 mb-1">Visitor Name *</label>
                    <input id="visitor_name" class="swal2-input" placeholder="e.g., Juan Dela Cruz" 
                           style="width: 100%; margin: 0;" required aria-required="true">
                </div>
                <div>
                    <label for="purpose_select" class="block text-sm font-medium text-gray-700 mb-1">Purpose *</label>
                    <select id="purpose_select" class="swal2-input" style="width: 100%; margin: 0;" aria-required="true">
                        <option value="">-- Select Purpose --</option>
                        <option value="Delivery">Delivery</option>
                        <option value="Service Provider">Service Provider</option>
                        <option value="Guest">Guest</option>
                        <option value="Contractor">Contractor</option>
                        <option value="Other">Other (Specify below)</option>
                    </select>
                    <textarea id="purpose" class="swal2-textarea" placeholder="Additional details or specify if 'Other'" 
                              style="width: 100%; margin-top: 8px; min-height: 60px;" aria-label="Purpose details"></textarea>
                </div>
                <div>
                    <label for="visitor_plate" class="block text-sm font-medium text-gray-700 mb-1">Vehicle Plate Number (Optional)</label>
                    <input id="visitor_plate" class="swal2-input" placeholder="e.g., ABC-1234" 
                           style="width: 100%; margin: 0; text-transform: uppercase;" aria-label="Vehicle plate number">
                    <small class="text-gray-500 text-xs">Leave blank if visitor has no vehicle</small>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="valid_from" class="block text-sm font-medium text-gray-700 mb-1">Valid From *</label>
                        <input id="valid_from" type="datetime-local" class="swal2-input" 
                               value="${defaults.from}" style="width: 100%; margin: 0;" required aria-required="true">
                    </div>
                    <div>
                        <label for="valid_until" class="block text-sm font-medium text-gray-700 mb-1">Valid Until *</label>
                        <input id="valid_until" type="datetime-local" class="swal2-input" 
                               value="${defaults.until}" style="width: 100%; margin: 0;" required aria-required="true">
                    </div>
                </div>
                <div class="bg-blue-50 border border-blue-200 rounded p-3 text-sm text-gray-700">
                    <strong>Note:</strong> Your visitor pass will be sent to admin for approval. 
                    You'll be notified once it's processed.
                </div>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Submit Request',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        didOpen: () => {
            const plateInput = document.getElementById('visitor_plate');
            plateInput.addEventListener('input', (e) => {
                e.target.value = e.target.value.toUpperCase();
            });
            
            const purposeSelect = document.getElementById('purpose_select');
            const purposeText = document.getElementById('purpose');
            purposeSelect.addEventListener('change', (e) => {
                if (e.target.value && e.target.value !== 'Other') {
                    purposeText.value = e.target.value;
                } else if (e.target.value === 'Other') {
                    purposeText.value = '';
                    purposeText.focus();
                }
            });
        },
        preConfirm: () => {
            const visitor_name = document.getElementById('visitor_name').value.trim();
            const purpose_select = document.getElementById('purpose_select').value;
            const purpose_text = document.getElementById('purpose').value.trim();
            const visitor_plate = document.getElementById('visitor_plate').value.trim().toUpperCase();
            const valid_from = document.getElementById('valid_from').value;
            const valid_until = document.getElementById('valid_until').value;

            // Basic validation (backend will do comprehensive validation)
            if (!visitor_name) {
                Swal.showValidationMessage('Visitor name is required');
                return false;
            }
            
            if (visitor_name.length < 2) {
                Swal.showValidationMessage('Visitor name must be at least 2 characters');
                return false;
            }
            
            const purpose = purpose_text || purpose_select;
            if (!purpose) {
                Swal.showValidationMessage('Please select or specify a purpose');
                return false;
            }
            
            if (!valid_from || !valid_until) {
                Swal.showValidationMessage('Please select valid from and until dates');
                return false;
            }
            
            const fromDate = new Date(valid_from);
            const untilDate = new Date(valid_until);
            
            if (untilDate <= fromDate) {
                Swal.showValidationMessage('Valid until must be after valid from date');
                return false;
            }
            
            // Check minimum duration
            const durationMinutes = (untilDate - fromDate) / (1000 * 60);
            if (durationMinutes < 30) {
                Swal.showValidationMessage('Visit duration must be at least 30 minutes');
                return false;
            }
            
            if (durationMinutes > 10080) { // 7 days
                Swal.showValidationMessage('Visit duration cannot exceed 7 days');
                return false;
            }

            return { visitor_name, purpose, visitor_plate, valid_from, valid_until };
        }
    });

    if (formValues) {
        await submitVisitorPass(formValues);
    }
}

// Submit Visitor Pass
async function submitVisitorPass(formData) {
    try {
        const response = await fetch('api/create_visitor_pass.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                ...formData,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            })
        });

        const result = await response.json();

        if (result.success) {
            await Swal.fire({
                icon: 'success',
                title: '✓ Visitor Pass Created!',
                html: `
                    <div class="text-left">
                        <p class="mb-3">Your visitor pass request has been submitted successfully.</p>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-sm text-gray-700">
                            <strong>⏳ Status:</strong> Pending Admin Approval<br>
                            <span class="text-xs text-gray-600 mt-1 block">You will be notified once the admin reviews your request.</span>
                        </div>
                    </div>
                `,
                confirmButtonText: 'OK, Got it!',
                confirmButtonColor: '#3b82f6'
            });
            loadVisitorPasses();
        } else {
            throw new Error(result.message || 'Failed to create visitor pass');
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error Creating Pass',
            text: error.message,
            confirmButtonColor: '#ef4444'
        });
    }
}

// View QR Code
function viewQRCode(token, visitorName) {
    const qrUrl = `../visitor/view_pass.php?token=${token}`;
    
    // Find the pass with this token to get the QR code
    const pass = visitorPasses.find(p => p.qr_token === token);
    const qrCodeSrc = pass && pass.qr_code ? pass.qr_code : pass.qr_code || 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
    
    Swal.fire({
        title: 'Visitor Pass QR Code',
        html: `
            <div class="text-center">
                <p class="mb-4 text-gray-700">Share this QR code or link with <strong>${visitorName}</strong></p>
                <div class="mb-4 p-4 bg-white border-2 border-gray-300 rounded-lg inline-block">
                    <img src="${qrCodeSrc}" alt="QR Code" style="width: 200px; height: 200px; image-rendering: pixelated;">
                </div>
                <div class="mt-4 p-3 bg-gray-50 rounded-lg text-left">
                    <p class="text-xs font-medium text-gray-600 mb-2">Share Link:</p>
                    <div class="flex gap-2">
                        <input type="text" value="${qrUrl}" readonly 
                               class="flex-1 px-3 py-2 text-xs border border-gray-300 rounded bg-white font-mono">
                        <button onclick="copyToClipboard('${qrUrl}')" 
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-xs font-medium">
                            Copy
                        </button>
                    </div>
                </div>
            </div>
        `,
        width: '500px',
        showConfirmButton: true,
        confirmButtonText: 'Close',
        confirmButtonColor: '#6b7280'
    });
}

// Copy to Clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        Swal.fire({
            icon: 'success',
            title: 'Copied!',
            text: 'Link copied to clipboard',
            timer: 1500,
            showConfirmButton: false
        });
    });
}

// Utility Functions
function getDefaultDates() {
    const now = new Date();
    // Round to next 5 minutes
    const minutes = Math.ceil(now.getMinutes() / 5) * 5;
    now.setMinutes(minutes);
    now.setSeconds(0);
    now.setMilliseconds(0);
    
    const later = new Date(now.getTime() + (4 * 60 * 60 * 1000)); // +4 hours
    
    const formatDateTime = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${year}-${month}-${day}T${hours}:${minutes}`;
    };
    
    return {
        from: formatDateTime(now),
        until: formatDateTime(later)
    };
}

function formatDateTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// View Image in Modal
function viewImage(imagePath, imageTitle) {
    Swal.fire({
        title: imageTitle,
        imageUrl: imagePath,
        imageAlt: imageTitle,
        showCloseButton: true,
        showConfirmButton: false,
        customClass: {
            popup: 'rounded-2xl',
            image: 'rounded-lg shadow-2xl',
            title: 'text-xl font-bold'
        },
        imageWidth: '90%',
        imageHeight: 'auto',
        background: '#ffffff',
        backdrop: 'rgba(0,0,0,0.8)',
        showClass: {
            popup: 'animate__animated animate__zoomIn animate__faster'
        },
        hideClass: {
            popup: 'animate__animated animate__zoomOut animate__faster'
        }
    });
}
