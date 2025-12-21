// Vehicle Management JavaScript for Homeowner Portal

let activityChart = null;
let currentPeriod = 'week';

// Load vehicles
async function loadVehicles() {
    try {
        const response = await fetch('api/get_vehicles.php');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load vehicles');
        }
        
        renderVehicles(data.vehicles);
    } catch (error) {
        console.error('Load vehicles error:', error);
        document.getElementById('vehiclesGrid').innerHTML = `
            <div class="col-span-full text-center py-8 text-red-600">
                <p>Error loading vehicles: ${error.message}</p>
            </div>
        `;
    }
}

// Render vehicles grid
function renderVehicles(vehicles) {
    const grid = document.getElementById('vehiclesGrid');
    
    if (vehicles.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <svg class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                </svg>
                <p class="text-gray-600 mb-4">No vehicles registered yet</p>
                <button onclick="showAddVehicleModal()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Add Your First Vehicle
                </button>
            </div>
        `;
        return;
    }
    
    grid.innerHTML = vehicles.map(vehicle => `
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow">
            ${vehicle.vehicle_img ? `
                <img src="../uploads/${vehicle.vehicle_img}" alt="${vehicle.vehicle_type}" class="w-full h-48 object-cover">
            ` : `
                <div class="w-full h-48 bg-gray-100 flex items-center justify-center">
                    <svg class="h-20 w-20 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                    </svg>
                </div>
            `}
            <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-bold text-gray-900">${vehicle.vehicle_type}</h3>
                    ${vehicle.is_primary ? '<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">Primary</span>' : ''}
                </div>
                <p class="text-sm text-gray-600 mb-1">Color: ${vehicle.color}</p>
                <p class="text-sm font-mono bg-gray-50 px-2 py-1 rounded inline-block">${vehicle.plate_number}</p>
                <div class="mt-4 flex gap-2">
                    <button onclick="deleteVehicle(${vehicle.id})" class="px-3 py-2 bg-red-50 text-red-600 rounded hover:bg-red-100 text-sm font-medium flex items-center gap-1">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Remove
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Show add vehicle modal
async function showAddVehicleModal() {
    const { value: formValues } = await Swal.fire({
        title: 'Add New Vehicle',
        html: `
            <div class="space-y-4 text-left">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Type *</label>
                    <select id="vehicleType" class="swal2-input w-full">
                        <option value="">Select type</option>
                        <option value="Car">Car</option>
                        <option value="Motorcycle">Motorcycle</option>
                        <option value="SUV">SUV</option>
                        <option value="Van">Van</option>
                        <option value="Truck">Truck</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color *</label>
                    <input type="text" id="vehicleColor" class="swal2-input w-full" placeholder="e.g., White, Black">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Plate Number *</label>
                    <input type="text" id="vehiclePlate" class="swal2-input w-full" placeholder="e.g., ABC-1234" style="text-transform: uppercase;">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Photo (Optional)</label>
                    <input type="file" id="vehicleImg" class="swal2-file w-full" accept="image/*">
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="isPrimary" class="mr-2">
                    <label for="isPrimary" class="text-sm text-gray-700">Set as primary vehicle</label>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Add Vehicle',
        cancelButtonText: 'Cancel',
        width: '500px',
        preConfirm: () => {
            const type = document.getElementById('vehicleType').value;
            const color = document.getElementById('vehicleColor').value;
            const plate = document.getElementById('vehiclePlate').value;
            const img = document.getElementById('vehicleImg').files[0];
            const isPrimary = document.getElementById('isPrimary').checked;
            
            if (!type || !color || !plate) {
                Swal.showValidationMessage('Please fill in all required fields');
                return false;
            }
            
            return { type, color, plate, img, isPrimary };
        }
    });
    
    if (formValues) {
        await addVehicle(formValues);
    }
}

// Add vehicle
async function addVehicle(data) {
    try {
        const formData = new FormData();
        formData.append('vehicle_type', data.type);
        formData.append('color', data.color);
        formData.append('plate_number', data.plate.toUpperCase());
        formData.append('is_primary', data.isPrimary);
        
        if (data.img) {
            formData.append('vehicle_img', data.img);
        }
        
        const response = await fetch('api/add_vehicle.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Failed to add vehicle');
        }
        
        Swal.fire('Success!', 'Vehicle added successfully', 'success');
        loadVehicles();
    } catch (error) {
        console.error('Add vehicle error:', error);
        Swal.fire('Error!', error.message, 'error');
    }
}

// Delete vehicle
async function deleteVehicle(vehicleId) {
    const result = await Swal.fire({
        title: 'Remove Vehicle?',
        text: 'This will deactivate the vehicle from your account.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626'
    });
    
    if (result.isConfirmed) {
        try {
            const response = await fetch('api/delete_vehicle.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ vehicle_id: vehicleId })
            });
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to delete vehicle');
            }
            
            Swal.fire('Removed!', 'Vehicle has been removed', 'success');
            loadVehicles();
        } catch (error) {
            console.error('Delete vehicle error:', error);
            Swal.fire('Error!', error.message, 'error');
        }
    }
}

// Load vehicle activity
async function loadVehicleActivity(period = 'week') {
    currentPeriod = period;
    
    // Update active period button
    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.classList.remove('active', 'bg-blue-100', 'text-blue-700');
        if (btn.dataset.period === period) {
            btn.classList.add('active', 'bg-blue-100', 'text-blue-700');
        }
    });
    
    try {
        const response = await fetch(`api/get_vehicle_activity.php?period=${period}`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to load activity');
        }
        
        // Update summary cards
        document.getElementById('totalEntries').textContent = data.summary.total_entries || 0;
        document.getElementById('totalExits').textContent = data.summary.total_exits || 0;
        document.getElementById('totalActivity').textContent = data.summary.total_logs || 0;
        
        // Render chart
        renderActivityChart(data.activity);
    } catch (error) {
        console.error('Load activity error:', error);
    }
}

// Render activity chart
function renderActivityChart(activity) {
    const ctx = document.getElementById('activityChart');
    
    if (!ctx) return;
    
    if (activityChart) {
        activityChart.destroy();
    }
    
    const labels = activity.map(a => a.period);
    const entries = activity.map(a => parseInt(a.entries) || 0);
    const exits = activity.map(a => parseInt(a.exits) || 0);
    
    activityChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Entries',
                    data: entries,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    fill: true,
                    tension: 0.4
                },
                {
                    label: 'Exits',
                    data: exits,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    fill: true,
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

// Hook into loadPage function
document.addEventListener('DOMContentLoaded', function() {
    const originalLoadPage = window.loadPage;
    window.loadPage = function(page) {
        if (typeof originalLoadPage === 'function') {
            originalLoadPage(page);
        }
        
        if (page === 'vehicles') {
            loadVehicles();
        } else if (page === 'activity') {
            loadVehicleActivity(currentPeriod);
        }
    };
    
    // Add event listeners
    const addVehicleBtn = document.getElementById('addVehicleBtn');
    if (addVehicleBtn) {
        addVehicleBtn.addEventListener('click', showAddVehicleModal);
    }
    
    document.querySelectorAll('.period-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            loadVehicleActivity(this.dataset.period);
        });
    });
});
