<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/Truck.php';
require_once '../models/Driver.php';
require_once '../includes/check_auth.php';
$database = new Database();
$db = $database->getConnection();

if(!isset($_SESSION['user_id'])) {
    header("Location: ../signin.php");
    exit();
}
if($_SESSION['user_role'] == "driver"){
    header("Location: ../signin.php");
    exit();
}

$truckModel = new Truck($db);
$driverModel = new Driver($db);

// Function to check if driver is already assigned to another truck
function isDriverAssignedToOtherTruck(PDO $db, int $driver_id, ?int $current_truck_id = null): array|bool {
    $sql = "SELECT id, plate_number FROM trucks WHERE current_driver_id = :driver_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':driver_id', $driver_id);
    $stmt->execute();
    $truck = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($truck && $truck['id'] != $current_truck_id) {
        return $truck;
    }
    return false;
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                    $assigned_driver_id = !empty($_POST['current_driver_id']) ? $_POST['current_driver_id'] : null;
    
                // Check if driver is already assigned to another truck
                if($assigned_driver_id) {
                    $conflict = isDriverAssignedToOtherTruck($db, $assigned_driver_id);
                    if($conflict) {
                        $_SESSION['flash_error'] = "Driver is already assigned to truck " . $conflict['plate_number'];
                        header("Location: trucks.php");
                        exit();
                    }
                }
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
                    $id = $_POST['id'];
                    $assigned_driver_id = !empty($_POST['current_driver_id']) ? $_POST['current_driver_id'] : null;
                    
                    // Check if driver is already assigned to another truck
                    if($assigned_driver_id) {
                        $conflict = isDriverAssignedToOtherTruck($db, $assigned_driver_id, $id);
                        if($conflict) {
                            $_SESSION['flash_error'] = "Driver is already assigned to truck " . $conflict['plate_number'] . ". Please unassign them first.";
                            header("Location: trucks.php");
                            exit();
                        }
                    }
                $data = [
                    'plate_number' => $_POST['plate_number'],
                    'model' => $_POST['model'],
                    'brand' => $_POST['brand'],
                    'year' => $_POST['year'],
                    'capacity_kg' => $_POST['capacity_kg'],
                    'fuel_type' => $_POST['fuel_type'],
                    'status' => $_POST['status'],
                    'current_driver_id' => !empty($_POST['current_driver_id']) ? $_POST['current_driver_id'] : null
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
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .status-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-maintenance { background: #fed7aa; color: #92400e; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .modal { transition: all 0.3s ease; }
    </style>
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
            <button onclick="openAddModal()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
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
                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($truck)); ?>)" class="text-blue-600 hover:text-blue-800 mr-2">
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

<!-- Add Truck Modal -->
<div id="addTruckModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Add New Truck</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="space-y-4">
                <input type="text" name="plate_number" placeholder="Plate Number" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="text" name="model" placeholder="Model" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="text" name="brand" placeholder="Brand" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="number" name="year" placeholder="Year" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="number" step="0.01" name="capacity_kg" placeholder="Capacity (kg)" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <select name="fuel_type" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="diesel">Diesel</option>
                    <option value="petrol">Petrol</option>
                    <option value="electric">Electric</option>
                    <option value="hybrid">Hybrid</option>
                </select>
                <select name="status" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="active">Active</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
                        <!--set a truck driver-->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Assign Driver (Optional)</label>
                <select name="current_driver_id" id="edit_driver_id" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- No Driver Assigned --</option>
                    <?php 
                    // Get all available drivers
                    $driverSql = "SELECT id, full_name FROM drivers WHERE status IN ('available', 'on_trip') ORDER BY full_name";
                    $driverStmt = $db->prepare($driverSql);
                    $driverStmt->execute();
                    $allDrivers = $driverStmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach($allDrivers as $driver): 
                    ?>
                    <option value="<?php echo $driver['id']; ?>"><?php echo htmlspecialchars($driver['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">Select a driver to assign to this truck</p>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('addTruckModal')" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Truck Modal -->
<div id="editTruckModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Edit Truck</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="space-y-4">
                <input type="text" name="plate_number" id="edit_plate_number" placeholder="Plate Number" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="text" name="model" id="edit_model" placeholder="Model" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="text" name="brand" id="edit_brand" placeholder="Brand" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="number" name="year" id="edit_year" placeholder="Year" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="number" step="0.01" name="capacity_kg" id="edit_capacity_kg" placeholder="Capacity (kg)" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <select name="fuel_type" id="edit_fuel_type" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="diesel">Diesel</option>
                    <option value="petrol">Petrol</option>
                    <option value="electric">Electric</option>
                    <option value="hybrid">Hybrid</option>
                </select>
                <select name="status" id="edit_status" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="active">Active</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="inactive">Inactive</option>
                </select>
                <select name="current_driver_id" id="edit_driver_id" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">No Driver Assigned</option>
                    <?php foreach($availableDrivers as $driver): ?>
                    <option value="<?php echo $driver['id']; ?>"><?php echo htmlspecialchars($driver['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('editTruckModal')" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addTruckModal').classList.remove('hidden');
    document.getElementById('addTruckModal').classList.add('flex');
}

function openEditModal(truck) {
    document.getElementById('edit_id').value = truck.id;
    document.getElementById('edit_plate_number').value = truck.plate_number;
    document.getElementById('edit_model').value = truck.model;
    document.getElementById('edit_brand').value = truck.brand;
    document.getElementById('edit_year').value = truck.year;
    document.getElementById('edit_capacity_kg').value = truck.capacity_kg;
    document.getElementById('edit_fuel_type').value = truck.fuel_type;
    document.getElementById('edit_status').value = truck.status;
    document.getElementById('edit_driver_id').value = truck.driver_id || '';
    
    document.getElementById('editTruckModal').classList.remove('hidden');
    document.getElementById('editTruckModal').classList.add('flex');
}
function editTruck(truck) {
    document.getElementById('edit_id').value = truck.id;
    document.getElementById('edit_plate_number').value = truck.plate_number;
    document.getElementById('edit_model').value = truck.model;
    document.getElementById('edit_brand').value = truck.brand;
    document.getElementById('edit_year').value = truck.year;
    document.getElementById('edit_capacity_kg').value = truck.capacity_kg;
    document.getElementById('edit_fuel_type').value = truck.fuel_type;
    document.getElementById('edit_status').value = truck.status;
    
    const driverSelect = document.getElementById('edit_driver_id');
    if(driverSelect && truck.driver_id) {
        driverSelect.value = truck.driver_id;
    }
    
    openModal('editTruckModal');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
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
</body>
</html>