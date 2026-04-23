<?php
require_once '../includes/check_auth.php';
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/Shipment.php';
require_once '../models/Trip.php';

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

$shipmentModel = new Shipment($db);
$tripModel = new Trip($db);

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'shipment_number' => $shipmentModel->generateShipmentNumber(),
                    'trip_id' => $_POST['trip_id'],
                    'description' => $_POST['description'],
                    'weight_kg' => $_POST['weight_kg'],
                    'quantity' => $_POST['quantity'],
                    'dimensions' => $_POST['dimensions'],
                    'cargo_type' => $_POST['cargo_type'],
                    'status' => $_POST['status']
                ];
                $shipmentModel->create($data);
                break;
            case 'update':
                $data = [
                    'shipment_number' => $_POST['shipment_number'],
                    'trip_id' => $_POST['trip_id'],
                    'description' => $_POST['description'],
                    'weight_kg' => $_POST['weight_kg'],
                    'quantity' => $_POST['quantity'],
                    'dimensions' => $_POST['dimensions'],
                    'cargo_type' => $_POST['cargo_type'],
                    'status' => $_POST['status']
                ];
                $shipmentModel->update($_POST['id'], $data);
                break;
            case 'delete':
                $shipmentModel->delete($_POST['id']);
                break;
        }
        header("Location: shipments.php");
        exit();
    }
}

$shipments = $shipmentModel->getAll();
$trips = $tripModel->getAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipments Management | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .status-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .status-pending { background: #fed7aa; color: #92400e; }
        .status-loaded { background: #dbeafe; color: #1e40af; }
        .status-in_transit { background: #e0e7ff; color: #3730a3; }
        .status-delivered { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Shipments & Cargo</h2>
                <p class="text-gray-500">Manage all shipments and cargo</p>
            </div>
            <button onclick="openModal('addShipmentModal')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <i class="ri-add-line"></i> Add Shipment
            </button>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shipment #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trip #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Weight (kg)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cargo Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($shipments as $shipment): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($shipment['shipment_number']); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($shipment['trip_number'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars(substr($shipment['description'], 0, 50)); ?>...</td>
                            <td class="px-6 py-4 text-sm"><?php echo number_format($shipment['weight_kg'], 2); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo $shipment['quantity']; ?></td>
                            <td class="px-6 py-4 text-sm capitalize"><?php echo $shipment['cargo_type']; ?></td>
                            <td class="px-6 py-4">
                                <span class="status-badge status-<?php echo $shipment['status']; ?>">
                                    <?php echo str_replace('_', ' ', ucfirst($shipment['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="editShipment(<?php echo htmlspecialchars(json_encode($shipment)); ?>)" class="text-blue-600 hover:text-blue-800 mr-2">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button onclick="deleteShipment(<?php echo $shipment['id']; ?>)" class="text-red-600 hover:text-red-800">
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

<!-- Add Shipment Modal -->
<div id="addShipmentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Add New Shipment</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="space-y-4">
                <select name="trip_id" required class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Select Trip</option>
                    <?php foreach($trips as $trip): ?>
                    <option value="<?php echo $trip['id']; ?>"><?php echo $trip['trip_number']; ?></option>
                    <?php endforeach; ?>
                </select>
                <textarea name="description" placeholder="Description" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
                <input type="number" step="0.01" name="weight_kg" placeholder="Weight (kg)" required class="w-full px-3 py-2 border rounded-lg">
                <input type="number" name="quantity" placeholder="Quantity" required class="w-full px-3 py-2 border rounded-lg">
                <input type="text" name="dimensions" placeholder="Dimensions (LxWxH)" class="w-full px-3 py-2 border rounded-lg">
                <select name="cargo_type" class="w-full px-3 py-2 border rounded-lg">
                    <option value="general">General Cargo</option>
                    <option value="perishable">Perishable</option>
                    <option value="hazardous">Hazardous</option>
                    <option value="fragile">Fragile</option>
                </select>
                <select name="status" class="w-full px-3 py-2 border rounded-lg">
                    <option value="pending">Pending</option>
                    <option value="loaded">Loaded</option>
                    <option value="in_transit">In Transit</option>
                    <option value="delivered">Delivered</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('addShipmentModal')" class="px-4 py-2 border rounded-lg">Cancel</button>
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

function editShipment(shipment) {
    alert('Edit shipment: ' + shipment.shipment_number);
}

function deleteShipment(id) {
    if(confirm('Are you sure you want to delete this shipment?')) {
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