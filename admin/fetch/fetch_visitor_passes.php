<!-- Visitor Pass Management Section for Admin -->
<div class="mb-6">
  <div class="flex items-center gap-3 mb-2">
    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-amber-500 to-amber-600 text-white">
      <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"></path>
      </svg>
    </div>
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Visitor Pass Requests</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400">Review and manage pending visitor pass requests</p>
    </div>
  </div>
</div>

<div id="pendingPassesContainer">
    <div class="space-y-4 py-4 animate-pulse">
        <div class="ta-skeleton ta-skeleton-card" style="height:6rem"></div>
        <div class="ta-skeleton ta-skeleton-card" style="height:6rem"></div>
        <div class="ta-skeleton ta-skeleton-card" style="height:6rem"></div>
    </div>
</div>

<script>
    async function loadPendingPasses() {
        try {
            const response = await fetch('api/get_pending_passes.php');
            const passes = await response.json();

            const container = document.getElementById('pendingPassesContainer');

            if (passes.length === 0) {
                container.innerHTML = `
                <div class="ta-empty-state">
                     <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                     </svg>
                    <p>No pending visitor pass requests</p>
                </div>
            `;
                return;
            }

            container.innerHTML = passes.map(pass => {
                const esc = s => { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
                return `
            <div class="ta-card mb-3">
                <div class="ta-card-body">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 dark:text-white">${esc(pass.visitor_name)}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Purpose: ${esc(pass.purpose)}</p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Homeowner: ${esc(pass.homeowner_name)}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Valid: ${esc(new Date(pass.valid_from).toLocaleString())} to ${esc(new Date(pass.valid_until).toLocaleString())}
                            </p>
                            ${pass.visitor_plate ? `<p class="text-xs text-gray-500 dark:text-gray-400">Plate: <span class="ta-badge neutral">${esc(pass.visitor_plate)}</span></p>` : ''}
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Requested: ${esc(new Date(pass.created_at).toLocaleString())}</p>
                        </div>
                        <div class="flex gap-2">
                            <div class="ta-action-dropdown">
                                <button type="button" class="ta-action-btn">
                                    Actions
                                    <svg class="ta-chevron" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
                                </button>
                                <div class="ta-action-menu">
                                    <button type="button" class="ta-action-menu-item green" onclick="approvePass(${pass.id})">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Approve
                                    </button>
                                    <div class="ta-action-divider"></div>
                                    <button type="button" class="ta-action-menu-item red" onclick="rejectPass(${pass.id})">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Reject
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
            }).join('');

        } catch (error) {
            console.error('Error loading passes:', error);
            document.getElementById('pendingPassesContainer').innerHTML = `
            <div class="text-center py-12 text-red-500">
                <p>Error loading visitor passes.</p>
            </div>
        `;
        }
    }

    async function approvePass(passId) {
        const result = await Swal.fire({
            title: 'Approve Visitor Pass?',
            text: 'This will allow the visitor to enter during the specified time period.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Approve',
            confirmButtonColor: '#10b981',
            cancelButtonText: 'Cancel'
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch('api/approve_visitor_pass.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pass_id: passId, csrf_token: window.__ADMIN_CSRF__ })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Activated!',
                        text: 'Visitor pass is now active.',
                        confirmButtonColor: '#3b82f6'
                    });
                    loadPendingPasses();
                } else {
                    throw new Error(data.message);
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
    }

    async function rejectPass(passId) {
        const { value: reason } = await Swal.fire({
            title: 'Reject Visitor Pass',
            input: 'textarea',
            inputLabel: 'Reason for rejection',
            inputPlaceholder: 'Enter reason...',
            showCancelButton: true,
            confirmButtonText: 'Reject',
            confirmButtonColor: '#ef4444',
            inputValidator: (value) => {
                if (!value) {
                    return 'Please provide a reason for rejection';
                }
            }
        });

        if (reason) {
            try {
                const response = await fetch('api/reject_visitor_pass.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pass_id: passId, reason: reason, csrf_token: window.__ADMIN_CSRF__ })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Rejected',
                        text: 'Visitor pass has been rejected.',
                        confirmButtonColor: '#3b82f6'
                    });
                    loadPendingPasses();
                } else {
                    throw new Error(data.message);
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
    }

    // Load on page load
    loadPendingPasses();
</script>