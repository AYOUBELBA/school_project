<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/Route.php';

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

$routeModel = new Route($db);

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'name' => $_POST['name'],
                    'start_point' => $_POST['start_point'],
                    'end_point' => $_POST['end_point'],
                    'start_lat' => !empty($_POST['start_lat']) ? $_POST['start_lat'] : null,
                    'start_lng' => !empty($_POST['start_lng']) ? $_POST['start_lng'] : null,
                    'end_lat' => !empty($_POST['end_lat']) ? $_POST['end_lat'] : null,
                    'end_lng' => !empty($_POST['end_lng']) ? $_POST['end_lng'] : null,
                    'estimated_distance_km' => !empty($_POST['estimated_distance_km']) ? $_POST['estimated_distance_km'] : null,
                    'estimated_duration_min' => !empty($_POST['estimated_duration_min']) ? $_POST['estimated_duration_min'] : null,
                    'is_active' => isset($_POST['is_active']) ? true : false
                ];
                $routeModel->create($data);
                break;
            case 'update':
                $data = [
                    'name' => $_POST['name'],
                    'start_point' => $_POST['start_point'],
                    'end_point' => $_POST['end_point'],
                    'start_lat' => !empty($_POST['start_lat']) ? $_POST['start_lat'] : null,
                    'start_lng' => !empty($_POST['start_lng']) ? $_POST['start_lng'] : null,
                    'end_lat' => !empty($_POST['end_lat']) ? $_POST['end_lat'] : null,
                    'end_lng' => !empty($_POST['end_lng']) ? $_POST['end_lng'] : null,
                    'estimated_distance_km' => !empty($_POST['estimated_distance_km']) ? $_POST['estimated_distance_km'] : null,
                    'estimated_duration_min' => !empty($_POST['estimated_duration_min']) ? $_POST['estimated_duration_min'] : null,
                    'is_active' => isset($_POST['is_active']) ? true : false
                ];
                $routeModel->update($_POST['id'], $data);
                break;
            case 'delete':
                $routeModel->delete($_POST['id']);
                break;
        }
        header("Location: routes.php");
        exit();
    }
}

$routes = $routeModel->getAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Routes Management | Fleet System</title>
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
                <h2 class="text-2xl font-bold text-gray-800">Routes & Scheduling</h2>
                <p class="text-gray-500">Manage delivery routes and schedules</p>
            </div>
            <button onclick="openModal('addRouteModal')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <i class="ri-add-line"></i> Add Route
            </button>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php foreach($routes as $route): ?>
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($route['name']); ?></h3>
                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($route['start_point']); ?> → <?php echo htmlspecialchars($route['end_point']); ?></p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="editRoute(<?php echo htmlspecialchars(json_encode($route)); ?>)" class="text-blue-600 hover:text-blue-800">
                            <i class="ri-edit-line"></i>
                        </button>
                        <button onclick="deleteRoute(<?php echo $route['id']; ?>)" class="text-red-600 hover:text-red-800">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Distance:</span>
                        <span class="font-medium ml-2"><?php echo $route['estimated_distance_km'] ? number_format($route['estimated_distance_km'], 1) . ' km' : 'N/A'; ?></span>
                    </div>
                    <div>
                        <span class="text-gray-500">Duration:</span>
                        <span class="font-medium ml-2"><?php echo $route['estimated_duration_min'] ? $route['estimated_duration_min'] . ' min' : 'N/A'; ?></span>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs <?php echo $route['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'; ?>">
                        <?php echo $route['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<!-- Add Route Modal -->
<div id="addRouteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Add New Route</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="space-y-4">
                <input type="text" name="name" placeholder="Route Name" required class="w-full px-3 py-2 border rounded-lg">
                <input type="text" name="start_point" placeholder="Start Point" required class="w-full px-3 py-2 border rounded-lg">
                <input type="text" name="end_point" placeholder="End Point" required class="w-full px-3 py-2 border rounded-lg">
                <input type="number" step="0.1" name="estimated_distance_km" placeholder="Distance (km)" class="w-full px-3 py-2 border rounded-lg">
                <input type="number" name="estimated_duration_min" placeholder="Duration (minutes)" class="w-full px-3 py-2 border rounded-lg">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" class="mr-2" checked> Active
                </label>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('addRouteModal')" class="px-4 py-2 border rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Save</button>
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

function editRoute(route) {
    alert('Edit route: ' + route.name);
}

function deleteRoute(id) {
    if(confirm('Are you sure you want to delete this route?')) {
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