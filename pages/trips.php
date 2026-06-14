<?php
// pages/trips.php - Cleaned up version
require_once '../includes/check_auth.php';
require_once '../models/Trip.php';
require_once '../models/Truck.php';
require_once '../models/Driver.php';
require_once '../models/Route.php';

// $db is already available from check_auth.php
$tripModel = new Trip($db);
$truckModel = new Truck($db);
$driverModel = new Driver($db);
$routeModel = new Route($db);

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'trip_number' => $tripModel->generateTripNumber(),
                    'truck_id' => $_POST['truck_id'],
                    'driver_id' => $_POST['driver_id'],
                    'route_id' => !empty($_POST['route_id']) ? $_POST['route_id'] : null,
                    'client_name' => $_POST['client_name'],
                    'client_phone' => $_POST['client_phone'],
                    'start_location' => $_POST['start_location'],
                    'end_location' => $_POST['end_location'],
                    'start_time' => $_POST['start_time'],
                    'status' => $_POST['status'],
                    'priority' => $_POST['priority'],
                    'notes' => $_POST['notes'],
                    'created_by' => $_SESSION['user_id']
                ];
                $tripModel->create($data);
                break;
            case 'update_status':
                $tripModel->updateStatus($_POST['id'], $_POST['status']);
                break;
        }
        header("Location: trips.php");
        exit();
    }
}

// Get all data (now automatically filtered by company_id in models)
$trips = $tripModel->getAll();
$trucks = $truckModel->getAll();
$drivers = $driverModel->getAll();
$routes = $routeModel->getAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trips Management | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .status-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .status-planned { background: #e0e7ff; color: #3730a3; }
        .status-ongoing { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .priority-high { background: #fee2e2; color: #991b1b; }
        .priority-medium { background: #fed7aa; color: #92400e; }
        .priority-low { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Trips & Assignments</h2>
                <p class="text-gray-500">Manage all trips and driver assignments</p>
            </div>
            <button onclick="openModal('addTripModal')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <i class="ri-add-line"></i> Create Trip
            </button>
        </div>
        
        <!-- Trips Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trip #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Truck</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($trips as $trip): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($trip['trip_number']); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($trip['truck_plate'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($trip['driver_name'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($trip['client_name'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($trip['route_name'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo $trip['start_time'] ? date('Y-m-d H:i', strtotime($trip['start_time'])) : 'N/A'; ?></td>
                            <td class="px-6 py-4">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?php echo $trip['id']; ?>">
                                    <select name="status" onchange="this.form.submit()" class="status-badge status-<?php echo $trip['status']; ?> border-0 cursor-pointer">
                                        <option value="planned" <?php echo $trip['status'] == 'planned' ? 'selected' : ''; ?>>Planned</option>
                                        <option value="ongoing" <?php echo $trip['status'] == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                        <option value="completed" <?php echo $trip['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="cancelled" <?php echo $trip['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-6 py-4">
                                <span class="status-badge priority-<?php echo $trip['priority']; ?>">
                                    <?php echo ucfirst($trip['priority']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="viewTripOnMap(<?php echo $trip['id']; ?>)" class="text-green-600 hover:text-green-800 mr-2">
                                    <i class="ri-map-pin-line"></i> Track
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

<!-- Add Trip Modal -->
<div id="addTripModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold mb-4">Create New Trip</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Truck *</label>
                    <select name="truck_id" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="">Select Truck</option>
                        <?php foreach($trucks as $truck): ?>
                        <option value="<?php echo $truck['id']; ?>"><?php echo $truck['plate_number'] . ' - ' . $truck['model']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Driver *</label>
                    <select name="driver_id" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="">Select Driver</option>
                        <?php foreach($drivers as $driver): ?>
                        <option value="<?php echo $driver['id']; ?>"><?php echo $driver['full_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Route (Optional)</label>
                    <select name="route_id" class="w-full px-3 py-2 border rounded-lg">
                        <option value="">Select Route</option>
                        <?php foreach($routes as $route): ?>
                        <option value="<?php echo $route['id']; ?>"><?php echo $route['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Start Time *</label>
                    <input type="datetime-local" name="start_time" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Client Name</label>
                    <input type="text" name="client_name" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Client Phone</label>
                    <input type="text" name="client_phone" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-1">Start Location</label>
                    <input type="text" name="start_location" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-1">End Location</label>
                    <input type="text" name="end_location" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border rounded-lg">
                        <option value="planned">Planned</option>
                        <option value="ongoing">Ongoing</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Priority</label>
                    <select name="priority" class="w-full px-3 py-2 border rounded-lg">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium mb-1">Notes</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('addTripModal')" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Create Trip</button>
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

function viewTrip(id) {
    alert('View trip details for ID: ' + id);
}

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}

// NEW: View trip on map
function viewTripOnMap(tripId) {
    // Open the React Native app with trip ID
    // For web testing, you can show coordinates in alert
    fetch(`../api/get_trip_coords.php?id=${tripId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // For React Native deep linking (if app is installed)
                window.location.href = `truckdriverapp://trip/${tripId}`;
                // Or for testing, show coordinates
                alert(`Start: ${data.start_lat}, ${data.start_lng}\nEnd: ${data.end_lat}, ${data.end_lng}`);
            } else {
                alert('Coordinates not available for this trip');
            }
        });
}
</script>
</body>
</html>