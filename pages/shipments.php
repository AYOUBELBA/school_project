<?php
/**
 * @var PDO $db
 */
require_once '../includes/check_auth.php';
require_once '../models/Shipment.php';
require_once '../models/Trip.php';


// $db is already available from check_auth.php
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
                $_SESSION['flash_success'] = "Shipment added successfully!";
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
                $_SESSION['flash_success'] = "Shipment updated successfully!";
                break;
            case 'delete':
                $shipmentModel->delete($_POST['id']);
                $_SESSION['flash_success'] = "Shipment deleted successfully!";
                break;
        }
        header("Location: shipments.php");
        exit();
    }
}

// Get flash messages
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

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
                <h2 class="text-2xl font-bold text-gray-800">Shipments & Cargo</h2>
                <p class="text-gray-500">Manage all shipments and cargo</p>
            </div>
            <button onclick="openModal('addShipmentModal')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <i class="ri-add-line"></i> Add Shipment
            </button>
        </div>
        
        <?php if($flash_success): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg"><?php echo $flash_success; ?></div>
        <?php endif; ?>
        
        <?php if($flash_error): ?>
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg"><?php echo $flash_error; ?></div>
        <?php endif; ?>
        
        <!-- Shipments Table -->
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
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars(substr($shipment['description'], 0, 30)); ?>...</td>
                            <td class="px-6 py-4 text-sm"><?php echo number_format($shipment['weight_kg'], 2); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo $shipment['quantity']; ?></td>
                            <td class="px-6 py-4 text-sm capitalize"><?php echo $shipment['cargo_type']; ?></td>
                            <td class="px-6 py-4">
                                <span class="status-badge status-<?php echo $shipment['status']; ?>">
                                    <?php echo str_replace('_', ' ', ucfirst($shipment['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="viewShipment(<?php echo htmlspecialchars(json_encode($shipment)); ?>)" class="text-green-600 hover:text-green-800 mr-2" title="View Details">
                                    <i class="ri-eye-line"></i>
                                </button>
                                <button onclick="editShipment(<?php echo htmlspecialchars(json_encode($shipment)); ?>)" class="text-blue-600 hover:text-blue-800 mr-2" title="Edit">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button onclick="deleteShipment(<?php echo $shipment['id']; ?>)" class="text-red-600 hover:text-red-800" title="Delete">
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
                    <option value="livestock">Livestock</option>
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

<!-- Edit Shipment Modal -->
<div id="editShipmentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Edit Shipment</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="space-y-4">
                <input type="text" name="shipment_number" id="edit_shipment_number" readonly class="w-full px-3 py-2 border rounded-lg bg-gray-100">
                <select name="trip_id" id="edit_trip_id" required class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Select Trip</option>
                    <?php foreach($trips as $trip): ?>
                    <option value="<?php echo $trip['id']; ?>"><?php echo $trip['trip_number']; ?></option>
                    <?php endforeach; ?>
                </select>
                <textarea name="description" id="edit_description" placeholder="Description" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
                <input type="number" step="0.01" name="weight_kg" id="edit_weight_kg" placeholder="Weight (kg)" required class="w-full px-3 py-2 border rounded-lg">
                <input type="number" name="quantity" id="edit_quantity" placeholder="Quantity" required class="w-full px-3 py-2 border rounded-lg">
                <input type="text" name="dimensions" id="edit_dimensions" placeholder="Dimensions (LxWxH)" class="w-full px-3 py-2 border rounded-lg">
                <select name="cargo_type" id="edit_cargo_type" class="w-full px-3 py-2 border rounded-lg">
                    <option value="general">General Cargo</option>
                    <option value="perishable">Perishable</option>
                    <option value="hazardous">Hazardous</option>
                    <option value="fragile">Fragile</option>
                    <option value="livestock">Livestock</option>
                </select>
                <select name="status" id="edit_status" class="w-full px-3 py-2 border rounded-lg">
                    <option value="pending">Pending</option>
                    <option value="loaded">Loaded</option>
                    <option value="in_transit">In Transit</option>
                    <option value="delivered">Delivered</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('editShipmentModal')" class="px-4 py-2 border rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Update</button>
            </div>
        </form>
    </div>
</div>
<!-- View Shipment Details Modal -->
<div id="viewShipmentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Shipment Details</h3>
            <button onclick="closeModal('viewShipmentModal')" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
        </div>
        
        <div id="viewShipmentContent" class="space-y-4">
            <!-- Content will be populated by JavaScript -->
        </div>
        
        <div class="flex justify-end mt-6">
            <button onclick="closeModal('viewShipmentModal')" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Close</button>
        </div>
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
    document.getElementById('edit_id').value = shipment.id;
    document.getElementById('edit_shipment_number').value = shipment.shipment_number;
    document.getElementById('edit_trip_id').value = shipment.trip_id;
    document.getElementById('edit_description').value = shipment.description || '';
    document.getElementById('edit_weight_kg').value = shipment.weight_kg;
    document.getElementById('edit_quantity').value = shipment.quantity;
    document.getElementById('edit_dimensions').value = shipment.dimensions || '';
    document.getElementById('edit_cargo_type').value = shipment.cargo_type || 'general';
    document.getElementById('edit_status').value = shipment.status || 'pending';
    
    openModal('editShipmentModal');
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

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.classList.add('hidden');
        event.target.classList.remove('flex');
    }
}
// view shippment in details
function viewShipment(shipment) {
    const content = document.getElementById('viewShipmentContent');
    
    // Format status for display
    const statusDisplay = shipment.status.replace('_', ' ').toUpperCase();
    
    // Build the details HTML
    content.innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2 bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Shipment Number</p>
                <p class="text-lg font-semibold text-gray-800">${shipment.shipment_number}</p>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Trip Number</p>
                <p class="text-lg font-semibold text-gray-800">${shipment.trip_number || 'N/A'}</p>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Truck</p>
                <p class="text-lg font-semibold text-gray-800">${shipment.truck_plate || 'N/A'}</p>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Weight</p>
                <p class="text-lg font-semibold text-gray-800">${Number(shipment.weight_kg).toFixed(2)} kg</p>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Quantity</p>
                <p class="text-lg font-semibold text-gray-800">${shipment.quantity}</p>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Dimensions</p>
                <p class="text-lg font-semibold text-gray-800">${shipment.dimensions || 'N/A'}</p>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Cargo Type</p>
                <p class="text-lg font-semibold text-gray-800 capitalize">${shipment.cargo_type || 'N/A'}</p>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Status</p>
                <p class="text-lg font-semibold">
                    <span class="status-badge status-${shipment.status}">
                        ${statusDisplay}
                    </span>
                </p>
            </div>
            
            <div class="col-span-2 bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Description</p>
                <p class="text-base text-gray-800 mt-1">${shipment.description || 'No description provided'}</p>
            </div>
            
            ${shipment.receiver_name ? `
            <div class="col-span-2 bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Receiver Information</p>
                <p class="text-base text-gray-800 mt-1">${shipment.receiver_name}</p>
                ${shipment.delivery_confirmed_at ? `<p class="text-sm text-gray-500 mt-1">Delivered: ${new Date(shipment.delivery_confirmed_at).toLocaleString()}</p>` : ''}
            </div>
            ` : ''}
            
            <div class="col-span-2 bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Created</p>
                <p class="text-base text-gray-800">${new Date(shipment.created_at).toLocaleString()}</p>
            </div>
        </div>
    `;
    
    openModal('viewShipmentModal');
}
</script>
</body>
</html>