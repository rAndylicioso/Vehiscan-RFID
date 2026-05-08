(function () {
  'use strict';

  const approvalsBody = document.getElementById('approvalsBody');
  if (!approvalsBody) {
    return;
  }

  let applyApprovalsSearchFilter = null;

  function getApprovalRows() {
    return Array.from(document.querySelectorAll('#approvalsBody tr')).filter((row) => {
      const cells = row.querySelectorAll('td');
      return cells.length > 0 && !row.querySelector('td[colspan]');
    });
  }

  function updateApprovalsSearchCount(visibleCount, totalCount) {
    const searchCount = document.getElementById('approvalsSearchCount');
    if (!searchCount) return;
    searchCount.textContent = `${visibleCount} of ${totalCount} accounts`;
    searchCount.style.color = visibleCount > 0 ? '#16a34a' : '#dc2626';
  }

  function loadPendingAccounts() {
    const tbody = document.getElementById('approvalsBody');
    if (!tbody) return;

    tbody.innerHTML = `
      <tr><td colspan="6" class="px-6 py-4">
        <div class="ta-skeleton ta-skeleton-row"></div>
        <div class="ta-skeleton ta-skeleton-row"></div>
        <div class="ta-skeleton ta-skeleton-row"></div>
      </td></tr>
    `;

    fetch('api/get_pending_accounts.php', {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then((r) => {
        if (r.status === 403) {
          throw new Error('Unauthorized: admin access required');
        }
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        return r.json();
      })
      .then((data) => {
        const tableBody = document.getElementById('approvalsBody');
        if (!tableBody) return;

        const accounts = Array.isArray(data) ? data : (Array.isArray(data?.accounts) ? data.accounts : []);

        if (data && data.error) {
          tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Error: ${data.error}</td></tr>`;
          if (window.VehiScanNotifications) {
            window.VehiScanNotifications.addError('Failed to load pending accounts: ' + data.error);
          }
          return;
        }

        if (!Array.isArray(accounts) || accounts.length === 0) {
          tableBody.innerHTML = '<tr><td colspan="6"><div class="ta-empty-state"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg><p>No pending accounts to review</p></div></td></tr>';
          if (window.VehiScanNotifications) {
            window.VehiScanNotifications.addInfo('All pending accounts have been reviewed.');
          }
          return;
        }

        const pendingAccountsEl = document.getElementById('pendingAccountsCount');
        if (pendingAccountsEl) pendingAccountsEl.textContent = String(accounts.length);

        tableBody.innerHTML = accounts.map((acc) => {
          const fullName = [acc.first_name, acc.middle_name, acc.last_name, acc.suffix].filter(Boolean).join(' ') || acc.name || 'Unknown';
          const date = new Date(acc.created_at).toLocaleDateString();
          const username = acc.username || 'N/A';
          const roleRaw = String(acc.role || (acc.account_type === 'user' ? 'user' : 'homeowner'));
          const roleCanonical = roleRaw === 'owner' ? 'homeowner' : roleRaw;
          const roleDisplay = roleCanonical
            .split('_')
            .filter(Boolean)
            .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
            .join(' ') || 'User';
          const accountType = acc.account_type || 'homeowner';

          return `
            <tr>
              <td class="px-6 py-4 whitespace-nowrap">
                <input type="checkbox" class="approval-checkbox rounded border-gray-300 dark:border-slate-600 text-blue-600 focus:ring-blue-500" 
                  data-id="${acc.id}" data-type="${accountType}" onchange="window.updateBulkActionsBar()">
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">${String(fullName).substring(0, 50)}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">${String(username).substring(0, 30)}</td>
              <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 break-all">${String(acc.email || 'N/A').substring(0, 40)}</td>
              <td class="px-6 py-4 whitespace-nowrap"><span class="ta-badge ta-badge-blue text-xs">${roleDisplay}</span></td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">${date}</td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <div class="flex items-center justify-center">
                  <div class="ta-action-dropdown">
                    <button type="button" class="ta-action-btn" aria-haspopup="menu" aria-expanded="false">
                      Actions
                      <svg class="ta-chevron" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                    </button>
                    <div class="ta-action-menu" role="menu" aria-hidden="true">
                      <button type="button" role="menuitem" class="ta-action-menu-item green" onclick="window.openActionModal(${acc.id}, '${accountType}', 'approve', '${String(fullName).replace(/'/g, "\\'")}')">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Approve
                      </button>
                      <div class="ta-action-divider"></div>
                      <button type="button" role="menuitem" class="ta-action-menu-item red" onclick="window.openActionModal(${acc.id}, '${accountType}', 'reject', '${String(fullName).replace(/'/g, "\\'")}')">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Reject
                      </button>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          `;
        }).join('');

        window.updateBulkActionsBar();

        if (typeof applyApprovalsSearchFilter === 'function') {
          applyApprovalsSearchFilter();
        }
      })
      .catch((err) => {
        console.error('Error loading accounts:', err);
        const tableBody = document.getElementById('approvalsBody');
        if (tableBody) {
          tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">${String(err.message || 'Failed to load pending accounts.')}</td></tr>`;
        }
        if (window.VehiScanNotifications) {
          window.VehiScanNotifications.addError(String(err.message || 'Failed to load pending accounts.'));
        }
      });
  }

  function loadApprovalOverview() {
    fetch('api/get_pending_approval_overview.php', {
      method: 'GET',
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then((r) => r.json())
      .then((data) => {
        if (!data || !data.success) {
          console.warn('[Approvals] Invalid overview response:', data);
          return;
        }
        const pendingAccountsEl = document.getElementById('pendingAccountsCount');
        const pendingProfileEl = document.getElementById('pendingProfileCount');
        const pendingTotalEl = document.getElementById('pendingTotalCount');
        if (pendingAccountsEl) pendingAccountsEl.textContent = String(data.pending_accounts ?? 0);
        if (pendingProfileEl) pendingProfileEl.textContent = String(data.pending_profile_requests ?? 0);
        if (pendingTotalEl) pendingTotalEl.textContent = String(data.total_pending_actions ?? 0);
      })
      .catch((err) => {
        console.error('[Approvals] overview error:', err);
        if (window.VehiScanNotifications) {
          window.VehiScanNotifications.addError('Failed to load approval overview.');
        }
      });
  }

  window.openActionModal = async function (userId, accountType, action, userName) {
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
      inputValidator: () => null
    });

    if (reason !== undefined) {
      try {
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('account_type', accountType || 'homeowner');
        formData.append('action', action);
        formData.append('reason', reason || '');
        formData.append('csrf_token', window.__ADMIN_CSRF__ || '');

        const res = await fetch('api/approve_user_account.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
          await Swal.fire({ icon: 'success', title: isApprove ? 'Approved!' : 'Rejected', text: data.message, confirmButtonColor: '#3b82f6' });
          loadPendingAccounts();
          loadApprovalOverview();
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#ef4444' });
        }
      } catch (err) {
        console.error('Error:', err);
        Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred while processing the request', confirmButtonColor: '#ef4444' });
      }
    }
  };

  try {
    loadPendingAccounts();
    loadApprovalOverview();
  } catch (error) {
    console.error('[Approvals] Error loading pending accounts:', error);
  }

  const openProfileRequestsBtn = document.getElementById('openProfileRequestsBtn');
  if (openProfileRequestsBtn) {
    openProfileRequestsBtn.addEventListener('click', () => {
      if (typeof window.loadPage === 'function') {
        window.loadPage('profile_requests');
      }
    });
  }

  (function () {
    const searchInput = document.getElementById('approvalsSearchInput');
    const searchCount = document.getElementById('approvalsSearchCount');
    if (!searchInput) return;

    applyApprovalsSearchFilter = function () {
      const term = searchInput.value.toLowerCase().trim();
      const rows = getApprovalRows();
      let visible = 0;

      rows.forEach((row) => {
        const cells = Array.from(row.querySelectorAll('td:not(:last-child)'));
        const text = cells.map((c) => c.textContent).join(' ').toLowerCase();
        const show = !term || text.includes(term);
        row.style.display = show ? '' : 'none';
        if (show) visible++;
      });

      updateApprovalsSearchCount(visible, rows.length);
    };

    searchInput.addEventListener('input', function () {
      applyApprovalsSearchFilter();
    });

    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        this.value = '';
        applyApprovalsSearchFilter();
      }
    });

    applyApprovalsSearchFilter();
  })();

  const selectAllApprovals = document.getElementById('selectAllApprovals');
  if (selectAllApprovals) {
    selectAllApprovals.addEventListener('change', function() {
      const checkboxes = document.querySelectorAll('.approval-checkbox');
      checkboxes.forEach(cb => {
        if (cb.closest('tr').style.display !== 'none') {
          cb.checked = this.checked;
        }
      });
      window.updateBulkActionsBar();
    });
  }

  window.updateBulkActionsBar = function() {
    const checkboxes = document.querySelectorAll('.approval-checkbox:checked');
    const bar = document.getElementById('bulkActionsBar');
    const countEl = document.getElementById('selectedCount');
    
    if (!bar || !countEl) return;
    
    if (checkboxes.length > 0) {
      bar.classList.remove('hidden');
      countEl.textContent = checkboxes.length;
    } else {
      bar.classList.add('hidden');
      if (selectAllApprovals) selectAllApprovals.checked = false;
    }
  };

  window.processBulkAction = async function(action) {
    const checkboxes = document.querySelectorAll('.approval-checkbox:checked');
    if (checkboxes.length === 0) return;

    const isApprove = action === 'approve';
    const ids = Array.from(checkboxes).map(cb => ({
      id: cb.dataset.id,
      type: cb.dataset.type
    }));

    const { value: reason } = await Swal.fire({
      title: `${isApprove ? 'Approve' : 'Reject'} ${ids.length} accounts?`,
      input: 'textarea',
      inputPlaceholder: 'Enter any notes or reason (optional)...',
      icon: isApprove ? 'question' : 'warning',
      showCancelButton: true,
      confirmButtonText: isApprove ? 'Approve All' : 'Reject All',
      confirmButtonColor: isApprove ? '#10b981' : '#ef4444',
    });

    if (reason !== undefined) {
      Swal.fire({
        title: 'Processing...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
      });

      try {
        const res = await fetch('api/bulk_approve_accounts.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            ids: ids,
            action: action,
            reason: reason || '',
            csrf_token: window.__ADMIN_CSRF__
          })
        });
        const data = await res.json();

        if (data.success) {
          await Swal.fire({ icon: 'success', title: 'Done!', text: data.message });
          loadPendingAccounts();
          loadApprovalOverview();
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
      } catch (err) {
        console.error('Bulk error:', err);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to process bulk action' });
      }
    }
  };
})();