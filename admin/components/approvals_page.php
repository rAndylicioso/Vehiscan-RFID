<?php
// Account Approvals Page
require_once __DIR__ . '/../../db.php';
?>

<div class="p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Account Approvals</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Review and approve pending account registrations</p>
    </div>

    <!-- Pending Accounts Table -->
    <div class="ta-table-wrapper">
        <div class="px-6 py-4 flex items-center justify-between flex-wrap gap-3" style="border-bottom: 1px solid var(--ta-card-border);">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Pending Registrations</h3>
            <div class="flex items-center gap-2">
                <div class="relative flex items-center">
                    <svg class="absolute left-3 h-4 w-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" id="approvalsSearchInput"
                        class="h-9 px-4 pl-10 border border-gray-300 dark:border-slate-600 rounded-lg min-w-[240px] text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all dark:bg-slate-700 dark:text-gray-200"
                        placeholder="Search accounts...">
                </div>
                <span id="approvalsSearchCount" class="text-sm text-gray-600 font-medium whitespace-nowrap"></span>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table id="approvalsTable" class="ta-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="approvalsBody">
                    <!-- Loaded via JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
  'use strict';
  
  console.log('[Approvals] Initializing approval controls...');

// Toggle dropdown visibility with smart positioning (matches sign-out dropdown behavior)
// Now handled by shared TailAdmin dropdown CSS/JS — .ta-action-dropdown

// Close dropdowns when clicking outside — handled by shared handler in admin_panel.php

// Load pending accounts
function loadPendingAccounts() {
    const tbody = document.getElementById('approvalsBody');
    
    // Show skeleton loader
    tbody.innerHTML = `
        <tr><td colspan="6" class="px-6 py-4">
            <div class="ta-skeleton ta-skeleton-row"></div>
            <div class="ta-skeleton ta-skeleton-row"></div>
            <div class="ta-skeleton ta-skeleton-row"></div>
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
                tbody.innerHTML = '<tr><td colspan="6"><div class="ta-empty-state"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg><p>No pending accounts</p></div></td></tr>';
                return;
            }
            
            tbody.innerHTML = data.map(acc => {
                const fullName = [acc.first_name, acc.middle_name, acc.last_name, acc.suffix].filter(Boolean).join(' ') || acc.name || 'Unknown';
                const date = new Date(acc.created_at).toLocaleDateString();
                const username = acc.username || 'N/A';
                const role = acc.role || 'homeowner';
                
                return `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${fullName}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">${username}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">${acc.email || 'N/A'}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full ${
                                role === 'admin' ? 'bg-purple-100 text-purple-800' :
                                role === 'guard' ? 'bg-blue-100 text-blue-800' :
                                'bg-green-100 text-green-800'
                            }">${role}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">${date}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="ta-action-dropdown">
                                <button type="button" class="ta-action-btn">
                                    Actions
                                    <svg class="ta-chevron" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                                </button>
                                <div class="ta-action-menu">
                                    <button type="button" class="ta-action-menu-item green" onclick="window.openActionModal(${acc.id}, 'approve', '${fullName.replace(/'/g, "\\'")}')">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Approve Account
                                    </button>
                                    <div class="ta-action-divider"></div>
                                    <button type="button" class="ta-action-menu-item red" onclick="window.openActionModal(${acc.id}, 'reject', '${fullName.replace(/'/g, "\\'")}')">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Reject Account
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        })
        .catch(err => console.error('Error loading accounts:', err));
}

// Approve/Reject via SweetAlert2 (replaces custom modal)
window.openActionModal = async function(userId, action, userName) {
    const isApprove = action === 'approve';
    const { value: reason } = await Swal.fire({
        title: `${isApprove ? 'Approve' : 'Reject'} ${userName}?`,
        input: 'textarea',
        inputPlaceholder: 'Enter any notes or reason (optional)...',
        inputAttributes: { 'aria-label': 'Reason', style: 'min-height: 80px;' },
        icon: isApprove ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonText: isApprove ? 'Approve' : 'Reject',
        confirmButtonColor: isApprove ? '#10b981' : '#ef4444',
        cancelButtonColor: '#6b7280',
        inputValidator: () => null // allow empty
    });

    if (reason !== undefined) {
        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('action', action);
            formData.append('reason', reason || '');
            formData.append('csrf_token', window.__ADMIN_CSRF__ || '');

            const res = await fetch('api/approve_user_account.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                await Swal.fire({ icon: 'success', title: isApprove ? 'Approved!' : 'Rejected', text: data.message, confirmButtonColor: '#3b82f6' });
                loadPendingAccounts();
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#ef4444' });
            }
        } catch (err) {
            console.error('Error:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred while processing the request', confirmButtonColor: '#ef4444' });
        }
    }
};

// Load on page show
try {
    loadPendingAccounts();
} catch (error) {
    console.error('[Approvals] Error loading pending accounts:', error);
}

// Client-side search for approvals table
(function() {
    const searchInput = document.getElementById('approvalsSearchInput');
    const searchCount = document.getElementById('approvalsSearchCount');
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const term = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#approvalsBody tr');
        let visible = 0, total = rows.length;

        rows.forEach(row => {
            const cells = Array.from(row.querySelectorAll('td:not(:last-child)'));
            const text = cells.map(c => c.textContent).join(' ').toLowerCase();
            const show = !term || text.includes(term);
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        if (searchCount) {
            searchCount.textContent = term ? `${visible} of ${total} accounts` : '';
            searchCount.style.color = visible > 0 ? '#16a34a' : '#dc2626';
        }
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            this.dispatchEvent(new Event('input'));
        }
    });
})();

console.log('[Approvals] Controls initialized successfully');

})(); // End of IIFE
</script>

<style>
/* Ensure table is not overlapping */
#approvalsTable {
    position: relative;
    z-index: 1;
}

/* Better table row hover */
#approvalsTable tbody tr:hover {
    background-color: #f9fafb;
}

/* Ensure buttons don't wrap awkwardly */
#approvalsTable td button {
    white-space: nowrap;
}
</style>
