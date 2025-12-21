<?php
// Account Approvals Page
require_once __DIR__ . '/../../db.php';
?>

<div class="p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Account Approvals</h2>
        <p class="text-sm text-gray-600 mt-1">Review and approve pending account registrations</p>
    </div>

    <!-- Pending Accounts Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Pending Registrations</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table id="approvalsTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="approvalsBody" class="bg-white divide-y divide-gray-200">
                    <!-- Loaded via JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Approve/Reject Modal -->
<div id="actionModal" class="hidden fixed inset-0 z-[9999] overflow-y-auto" style="background-color: rgba(0, 0, 0, 0.5);">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 transition-opacity" aria-hidden="true" onclick="window.closeActionModal()">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>

        <!-- Center modal -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full" style="position: relative; z-index: 10000;">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 id="modalTitle" class="text-lg leading-6 font-medium text-gray-900 mb-4"></h3>
                        <input type="hidden" id="actionUserId">
                        <input type="hidden" id="actionType">
                        
                        <div class="mt-4">
                            <label for="actionReason" class="block text-sm font-medium text-gray-700 mb-2">
                                Reason/Notes (optional)
                            </label>
                            <textarea 
                                id="actionReason" 
                                rows="4" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Enter any notes or reason for this action..."
                            ></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                <button 
                    id="confirmActionBtn" 
                    onclick="window.confirmAction()" 
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm"
                ></button>
                <button 
                    onclick="window.closeActionModal()" 
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:w-auto sm:text-sm"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
  'use strict';
  
  console.log('[Approvals] Initializing approval controls...');

// Toggle dropdown visibility with smart positioning (matches sign-out dropdown behavior)
window.toggleActionDropdown = function(userId) {
    const dropdown = document.getElementById(`dropdown-${userId}`);
    const button = document.getElementById(`action-menu-${userId}`);
    const chevron = button.querySelector('svg');
    
    if (!dropdown || !button) {
        console.warn(`Dropdown elements not found for user ${userId}`);
        return;
    }
    
    // Close all other dropdowns first
    document.querySelectorAll('[id^="dropdown-"]').forEach(dd => {
        if (dd.id !== `dropdown-${userId}`) {
            dd.style.display = 'none';
            // Reset other chevrons to point down
            const otherButton = dd.previousElementSibling;
            if (otherButton) {
                const otherChevron = otherButton.querySelector('svg');
                if (otherChevron) otherChevron.style.transform = 'rotate(0deg)';
            }
        }
    });
    
    // Toggle current dropdown with smooth transition
    const isHidden = dropdown.style.display === 'none' || dropdown.style.display === '';
    
    if (isHidden) {
        // Position dropdown dynamically
        const buttonRect = button.getBoundingClientRect();
        const dropdownHeight = 120; // Approximate height
        const viewportHeight = window.innerHeight;
        const spaceBelow = viewportHeight - buttonRect.bottom;
        
        // Smart positioning: above or below based on available space
        if (spaceBelow < dropdownHeight && buttonRect.top > dropdownHeight) {
            dropdown.style.bottom = '100%';
            dropdown.style.top = 'auto';
            dropdown.style.marginBottom = '0.5rem';
            dropdown.style.marginTop = '0';
        } else {
            dropdown.style.top = '100%';
            dropdown.style.bottom = 'auto';
            dropdown.style.marginTop = '0.5rem';
            dropdown.style.marginBottom = '0';
        }
        
        dropdown.style.display = 'block';
        if (chevron) chevron.style.transform = 'rotate(180deg)'; // Chevron points up
    } else {
        dropdown.style.display = 'none';
        if (chevron) chevron.style.transform = 'rotate(0deg)'; // Chevron points down
    }
};

// Close dropdowns when clicking outside (matches sign-out dropdown behavior)
document.addEventListener('click', function(e) {
    if (!e.target.closest('[id^="action-menu-"]') && !e.target.closest('[id^="dropdown-"]')) {
        document.querySelectorAll('[id^="dropdown-"]').forEach(dd => {
            dd.style.display = 'none';
            // Reset chevron rotation
            const button = dd.previousElementSibling;
            if (button) {
                const chevron = button.querySelector('svg');
                if (chevron) chevron.style.transform = 'rotate(0deg)';
            }
        });
    }
});

// Load pending accounts
function loadPendingAccounts() {
    const tbody = document.getElementById('approvalsBody');
    
    // Show skeleton loader
    tbody.innerHTML = `
        <tr><td colspan="6" class="px-6 py-4">
            <div class="skeleton skeleton-table-row"></div>
            <div class="skeleton skeleton-table-row"></div>
            <div class="skeleton skeleton-table-row"></div>
        </td></tr>
    `;
    
    fetch('api/get_pending_accounts.php')
        .then(r => {
            if (!r.ok) throw new Error('Network response was not ok');
            return r.json();
        })
        .then(data => {
            const tbody = document.getElementById('approvalsBody');
            
            // Handle error response
            if (data.error) {
                tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Error loading accounts: ${data.error}</td></tr>`;
                return;
            }
            
            // Handle empty array
            if (!Array.isArray(data) || data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No pending accounts</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.map(acc => {
                const fullName = [acc.first_name, acc.middle_name, acc.last_name, acc.suffix].filter(Boolean).join(' ') || acc.name || 'Unknown';
                const date = new Date(acc.created_at).toLocaleDateString();
                const username = acc.username || 'N/A';
                const role = acc.role || 'homeowner';
                
                return `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${fullName}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${username}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${acc.email || 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${
                                role === 'admin' ? 'bg-purple-100 text-purple-800' :
                                role === 'guard' ? 'bg-blue-100 text-blue-800' :
                                'bg-green-100 text-green-800'
                            }">${role}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${date}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="relative inline-block text-left">
                                <button type="button" class="inline-flex justify-center items-center gap-x-1.5 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-colors" id="action-menu-${acc.id}" onclick="toggleActionDropdown(${acc.id})">
                                    Actions
                                    <svg class="-mr-1 h-5 w-5 text-gray-400 transition-transform duration-200" viewBox="0 0 20 20" fill="currentColor" style="transform: rotate(0deg);">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                <div id="dropdown-${acc.id}" class="absolute right-0 z-10 w-48 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none transition-all duration-200" style="display: none;">
                                    <div class="py-1">
                                        <button onclick="window.openActionModal(${acc.id}, 'approve', '${fullName.replace(/'/g, "\\'")}'); toggleActionDropdown(${acc.id})" class="w-full text-left px-4 py-2 text-sm text-green-600 hover:bg-green-50 hover:text-green-900 flex items-center gap-2 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Approve Account
                                        </button>
                                        <button onclick="window.openActionModal(${acc.id}, 'reject', '${fullName.replace(/'/g, "\\'")}'); toggleActionDropdown(${acc.id})" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-900 flex items-center gap-2 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            Reject Account
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        })
        .catch(err => console.error('Error loading accounts:', err));
}

// Expose globally to fix onclick handlers
window.openActionModal = function(userId, action, userName) {
    const modal = document.getElementById('actionModal');
    const title = document.getElementById('modalTitle');
    const btn = document.getElementById('confirmActionBtn');
    
    document.getElementById('actionUserId').value = userId;
    document.getElementById('actionType').value = action;
    document.getElementById('actionReason').value = '';
    
    if (action === 'approve') {
        title.textContent = `Approve ${userName}?`;
        btn.textContent = 'Approve';
        btn.className = 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm';
    } else {
        title.textContent = `Reject ${userName}?`;
        btn.textContent = 'Reject';
        btn.className = 'w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm';
    }
    
    modal.classList.remove('hidden');
    // Prevent body scroll when modal is open
    document.body.style.overflow = 'hidden';
};

// Close modal and re-enable body scroll
window.closeActionModal = function() {
    document.getElementById('actionModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
};

window.confirmAction = function() {
    const userId = document.getElementById('actionUserId').value;
    const action = document.getElementById('actionType').value;
    const reason = document.getElementById('actionReason').value;
    
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('action', action);
    formData.append('reason', reason);
    formData.append('csrf_token', window.__ADMIN_CSRF__ || '');
    
    fetch('api/approve_user_account.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        window.closeActionModal();
        if (data.success) {
            // Show success message
            showNotification(data.message, 'success');
            // Reload pending accounts
            setTimeout(() => loadPendingAccounts(), 500);
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(err => {
        window.closeActionModal();
        console.error('Error:', err);
        showNotification('An error occurred while processing the request', 'error');
    });
}

// Simple notification function
function showNotification(message, type) {
    const notificationDiv = document.createElement('div');
    notificationDiv.className = `fixed top-4 right-4 z-[10000] max-w-sm w-full ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    } text-white px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300`;
    notificationDiv.innerHTML = `
        <div class="flex items-center justify-between">
            <p class="font-medium">${message}</p>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    `;
    document.body.appendChild(notificationDiv);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notificationDiv.style.opacity = '0';
        setTimeout(() => notificationDiv.remove(), 300);
    }, 5000);
}

// Load on page show
try {
    loadPendingAccounts();
} catch (error) {
    console.error('[Approvals] Error loading pending accounts:', error);
}

// Close modal on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('actionModal');
        if (modal && !modal.classList.contains('hidden')) {
            window.closeActionModal();
        }
    }
});

console.log('[Approvals] Controls initialized successfully');

})(); // End of IIFE
</script>

<style>
/* Ensure modal appears above all content */
#actionModal {
    z-index: 9999 !important;
}

/* Ensure table is not overlapping */
#approvalsTable {
    position: relative;
    z-index: 1;
}

/* Smooth transitions for modal */
#actionModal > div > div {
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Better table row hover */
#approvalsTable tbody tr:hover {
    background-color: #f9fafb;
}

/* Ensure buttons don't wrap awkwardly */
#approvalsTable td button {
    white-space: nowrap;
}

/* Fix textarea in modal */
#actionReason {
    resize: vertical;
    min-height: 80px;
}
</style>
