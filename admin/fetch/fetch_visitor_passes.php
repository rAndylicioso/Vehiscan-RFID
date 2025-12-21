<!-- Visitor Pass Management Section for Admin -->
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Pending Visitor Pass Requests</h2>

    <div id="pendingPassesContainer">
        <div class="space-y-4 py-4 animate-pulse">
            <div class="h-24 bg-gray-100 dark:bg-slate-700 rounded-lg"></div>
            <div class="h-24 bg-gray-100 dark:bg-slate-700 rounded-lg"></div>
            <div class="h-24 bg-gray-100 dark:bg-slate-700 rounded-lg"></div>
        </div>
    </div>
</div>

<script>
    async function loadPendingPasses() {
        try {
            const response = await fetch('../api/get_pending_passes.php');
            const passes = await response.json();

            const container = document.getElementById('pendingPassesContainer');

            if (passes.length === 0) {
                container.innerHTML = `
                <div class="flex flex-col items-center justify-center py-12">
                     <svg class="empty-state-illustration" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                     </svg>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">No pending visitor pass requests.</p>
                </div>
            `;
                return;
            }

            container.innerHTML = passes.map(pass => `
            <div class="border rounded-lg p-4 mb-3 hover:bg-gray-50">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-900">${pass.visitor_name}</h3>
                        <p class="text-sm text-gray-600 mt-1">Purpose: ${pass.purpose}</p>
                        <p class="text-sm text-gray-600">Homeowner: ${pass.homeowner_name}</p>
                        <p class="text-xs text-gray-500 mt-1">
                            Valid: ${new Date(pass.valid_from).toLocaleString()} to ${new Date(pass.valid_until).toLocaleString()}
                        </p>
                        ${pass.visitor_plate ? `<p class="text-xs text-gray-500">Plate: ${pass.visitor_plate}</p>` : ''}
                        <p class="text-xs text-gray-400 mt-1">Requested: ${new Date(pass.created_at).toLocaleString()}</p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="approvePass(${pass.id})" 
                                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm font-medium">
                            Approve
                        </button>
                        <button onclick="rejectPass(${pass.id})"
                                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm font-medium">
                            Reject
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

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
                const response = await fetch('../api/approve_visitor_pass.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pass_id: passId })
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
                const response = await fetch('../api/reject_visitor_pass.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pass_id: passId, reason: reason })
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