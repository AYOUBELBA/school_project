<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../models/Truck.php';
require_once '../models/Driver.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireLogin();

$truckModel = new Truck($db);
$driverModel = new Driver($db);

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'plate_number' => $_POST['plate_number'],
                    'model' => $_POST['model'],
                    'brand' => $_POST['brand'],
                    'year' => $_POST['year'],
                    'capacity_kg' => $_POST['capacity_kg'],
                    'fuel_type' => $_POST['fuel_type'],
                    'status' => $_POST['status']
                ];
                $truckModel->create($data);
                break;
            case 'update':
                $data = [
                    'plate_number' => $_POST['plate_number'],
                    'model' => $_POST['model'],
                    'brand' => $_POST['brand'],
                    'year' => $_POST['year'],
                    'capacity_kg' => $_POST['capacity_kg'],
                    'fuel_type' => $_POST['fuel_type'],
                    'status' => $_POST['status'],
                    'current_driver_id' => $_POST['current_driver_id'] ?: null
                ];
                $truckModel->update($_POST['id'], $data);
                break;
            case 'delete':
                $truckModel->delete($_POST['id']);
                break;
        }
        header("Location: trucks.php");
        exit();
    }
}

$trucks = $truckModel->getAll();
$availableDrivers = $driverModel->getAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trucks Management | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Trucks Management</h2>
                <p class="text-gray-500">Manage your fleet vehicles</p>
            </div>
            <button onclick="openModal('addTruckModal')" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-blue-dark transition flex items-center gap-2">
                <i class="ri-add-line"></i> Add Truck
            </button>
        </div>
        
        <!-- Trucks Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plate Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Model</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Brand</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Capacity (kg)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fuel Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($trucks as $truck): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm"><?php echo $truck['id']; ?></td>
                            <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($truck['plate_number']); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($truck['model']); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($truck['brand']); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo $truck['year']; ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo number_format($truck['capacity_kg']); ?></td>
                            <td class="px-6 py-4 text-sm capitalize"><?php echo $truck['fuel_type']; ?></td>
                            <td class="px-6 py-4">
                                <span class="status-badge status-<?php echo $truck['status']; ?>">
                                    <?php echo ucfirst($truck['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($truck['driver_name'] ?? 'Not assigned'); ?></td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="editTruck(<?php echo htmlspecialchars(json_encode($truck)); ?>)" class="text-blue-600 hover:text-blue-800 mr-2">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button onclick="deleteTruck(<?php echo $truck['id']; ?>)" class="text-red-600 hover:text-red-800">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Add/Edit Truck Modal -->
<div id="addTruckModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Add New Truck</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="space-y-4">
                <input type="text" name="plate_number" placeholder="Plate Number" required class="w-full px-3 py-2 border rounded-lg">
                <input type="text" name="model" placeholder="Model" required class="w-full px-3 py-2 border rounded-lg">
                <input type="text" name="brand" placeholder="Brand" required class="w-full px-3 py-2 border rounded-lg">
                <input type="number" name="year" placeholder="Year" required class="w-full px-3 py-2 border rounded-lg">
                <input type="number" step="0.01" name="capacity_kg" placeholder="Capacity (kg)" required class="w-full px-3 py-2 border rounded-lg">
                <select name="fuel_type" class="w-full px-3 py-2 border rounded-lg">
                    <option value="diesel">Diesel</option>
                    <option value="petrol">Petrol</option>
                    <option value="electric">Electric</option>
                    <option value="hybrid">Hybrid</option>
                </select>
                <select name="status" class="w-full px-3 py-2 border rounded-lg">
                    <option value="active">Active</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('addTruckModal')" class="px-4 py-2 border rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}

function editTruck(truck) {
    // Implement edit functionality
    alert('Edit truck: ' + truck.plate_number);
}

function deleteTruck(id) {
    if(confirm('Are you sure you want to delete this truck?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<style>
.status-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
.status-active { background: #d1fae5; color: #065f46; }
.status-maintenance { background: #fed7aa; color: #92400e; }
.status-inactive { background: #fee2e2; color: #991b1b; }
</style>
</body>
</html>