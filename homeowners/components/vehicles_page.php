<?php
// Multi-vehicle management component for homeowners portal
require_once __DIR__ . '/../../db.php';

$homeownerId = $_SESSION['homeowner_id'] ?? null;
if (!$homeownerId) {
    echo '<div class="p-6 text-red-600">Error: Not logged in</div>';
    exit();
}

// Get all vehicles for this homeowner
$stmt = $pdo->prepare("
    SELECT * FROM vehicles 
    WHERE homeowner_id = ? AND is_active = TRUE 
    ORDER BY is_primary DESC, registered_at DESC
");
$stmt->execute([$homeownerId]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">My Vehicles</h2>
            <p class="text-sm text-gray-600 mt-1">Manage your registered vehicles</p>
        </div>
        <button onclick="openAddVehicleModal()" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            <svg class="inline w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Vehicle
        </button>
    </div>

    <?php if (empty($vehicles)): ?>
        <div class="text-center py-12 bg-gray-50 rounded-lg">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No vehicles yet</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by adding your first vehicle.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($vehicles as $vehicle): ?>
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4 hover:shadow-md transition-shadow">
                    <?php if ($vehicle['is_primary']): ?>
                        <div class="mb-2">
                            <span class="px-2 py-1 text-xs font-semibold bg-blue-100 text-blue-800 rounded-full">PRIMARY</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($vehicle['plate_number']); ?></h3>
                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($vehicle['vehicle_type']); ?></p>
                    </div>
                    
                    <div class="space-y-2 text-sm mb-4">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            <span class="text-gray-700">Color: <?php echo htmlspecialchars($vehicle['color']); ?></span>
                        </div>
                        <?php if ($vehicle['brand']): ?>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <span class="text-gray-700"><?php echo htmlspecialchars($vehicle['brand']) . ' ' . htmlspecialchars($vehicle['model']) . ($vehicle['year'] ? ' (' . $vehicle['year'] . ')' : ''); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span class="text-gray-500 text-xs">Registered: <?php echo date('M d, Y', strtotime($vehicle['registered_at'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <?php if (!$vehicle['is_primary']): ?>
                            <button onclick="setPrimaryVehicle(<?php echo $vehicle['id']; ?>)" class="flex-1 px-3 py-2 text-sm bg-blue-50 text-blue-700 rounded-md hover:bg-blue-100">
                                Set Primary
                            </button>
                        <?php endif; ?>
                        <button onclick="editVehicle(<?php echo $vehicle['id']; ?>)" class="flex-1 px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                            Edit
                        </button>
                        <button onclick="removeVehicle(<?php echo $vehicle['id']; ?>)" class="px-3 py-2 text-sm bg-red-50 text-red-700 rounded-md hover:bg-red-100">
                            Remove
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Vehicle Modal -->
<div id="vehicleModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 id="vehicleModalTitle" class="text-lg leading-6 font-medium text-gray-900 mb-4">Add Vehicle</h3>
            <form id="vehicleForm">
                <input type="hidden" id="vehicleId" name="vehicle_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Plate Number *</label>
                    <input type="text" id="platNumber" name="plate_number" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle Type *</label>
                    <select id="vehicleType" name="vehicle_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select type</option>
                        <option value="Car">Car</option>
                        <option value="SUV">SUV</option>
                        <option value="Motorcycle">Motorcycle</option>
                        <option value="Truck">Truck</option>
                        <option value="Van">Van</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color *</label>
                    <input type="text" id="vehicleColor" name="color" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                    <input type="text" id="vehicleBrand" name="brand" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                        <input type="text" id="vehicleModel" name="model" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                        <input type="number" id="vehicleYear" name="year" min="1900" max="2099" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeVehicleModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Save Vehicle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddVehicleModal() {
    document.getElementById('vehicleModalTitle').textContent = 'Add Vehicle';
    document.getElementById('vehicleForm').reset();
    document.getElementById('vehicleId').value = '';
    document.getElementById('vehicleModal').classList.remove('hidden');
}

function closeVehicleModal() {
    document.getElementById('vehicleModal').classList.add('hidden');
}

function editVehicle(id) {
    // Load vehicle data and open modal
    fetch(`api/get_vehicle.php?id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const v = data.vehicle;
                document.getElementById('vehicleModalTitle').textContent = 'Edit Vehicle';
                document.getElementById('vehicleId').value = v.id;
                document.getElementById('plateNumber').value = v.plate_number;
                document.getElementById('vehicleType').value = v.vehicle_type;
                document.getElementById('vehicleColor').value = v.color;
                document.getElementById('vehicleBrand').value = v.brand || '';
                document.getElementById('vehicleModel').value = v.model || '';
                document.getElementById('vehicleYear').value = v.year || '';
                document.getElementById('vehicleModal').classList.remove('hidden');
            }
        });
}

function setPrimaryVehicle(id) {
    if (!confirm('Set this as your primary vehicle?')) return;
    
    fetch('api/set_primary_vehicle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ vehicle_id: id })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function removeVehicle(id) {
    if (!confirm('Remove this vehicle? This cannot be undone.')) return;
    
    fetch('api/remove_vehicle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ vehicle_id: id })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

document.getElementById('vehicleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('api/save_vehicle.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            closeVehicleModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
});
</script>
