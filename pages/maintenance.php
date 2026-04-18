<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/Maintenance.php';
require_once '../models/Truck.php';

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


$maintenanceModel = new Maintenance($db);
$truckModel = new Truck($db);

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'truck_id' => $_POST['truck_id'],
                    'maintenance_type' => $_POST['maintenance_type'],
                    'description' => $_POST['description'],
                    'cost' => !empty($_POST['cost']) ? $_POST['cost'] : null,
                    'maintenance_date' => $_POST['maintenance_date'],
                    'next_due_date' => !empty($_POST['next_due_date']) ? $_POST['next_due_date'] : null,
                    'odometer_at_service' => !empty($_POST['odometer_at_service']) ? $_POST['odometer_at_service'] : null,
                    'performed_by' => $_POST['performed_by'],
                    'status' => $_POST['status'],
                    'notes' => $_POST['notes']
                ];
                $maintenanceModel->create($data);
                break;
            case 'update':
                $data = [
                    'truck_id' => $_POST['truck_id'],
                    'maintenance_type' => $_POST['maintenance_type'],
                    'description' => $_POST['description'],
                    'cost' => !empty($_POST['cost']) ? $_POST['cost'] : null,
                    'maintenance_date' => $_POST['maintenance_date'],
                    'next_due_date' => !empty($_POST['next_due_date']) ? $_POST['next_due_date'] : null,
                    'odometer_at_service' => !empty($_POST['odometer_at_service']) ? $_POST['odometer_at_service'] : null,
                    'performed_by' => $_POST['performed_by'],
                    'status' => $_POST['status'],
                    'notes' => $_POST['notes']
                ];
                $maintenanceModel->update($_POST['id'], $data);
                break;
            case 'delete':
                $maintenanceModel->delete($_POST['id']);
                break;
        }
        header("Location: maintenance.php");
        exit();
    }
}

$maintenances = $maintenanceModel->getAll();
$trucks = $truckModel->getAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance & Incidents | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .status-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .status-scheduled { background: #fed7aa; color: #92400e; }
        .status-in_progress { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .severity-low { background: #d1fae5; color: #065f46; }
        .severity-medium { background: #fed7aa; color: #92400e; }
        .severity-high { background: #fee2e2; color: #991b1b; }
        .severity-critical { background: #fecaca; color: #7f1d1d; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Maintenance & Incidents</h2>
                <p class="text-gray-500">Track truck maintenance and record incidents</p>
            </div>
            <div class="flex gap-3">
                <button onclick="openModal('addMaintenanceModal')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="ri-tools-line"></i> Log Maintenance
                </button>
                <button onclick="openModal('addIncidentModal')" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition flex items-center gap-2">
                    <i class="ri-alert-line"></i> Report Incident
                </button>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="mb-6 border-b border-gray-200">
            <ul class="flex flex-wrap -mb-px">
                <li class="mr-2">
                    <button onclick="showTab('maintenance')" id="tabMaintenanceBtn" class="inline-block p-4 border-b-2 border-blue-600 text-blue-600 font-medium">Maintenance Records</button>
                </li>
                <li class="mr-2">
                    <button onclick="showTab('incidents')" id="tabIncidentsBtn" class="inline-block p-4 border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300">Incidents</button>
                </li>
            </ul>
        </div>
        
        <!-- Maintenance Tab -->
        <div id="maintenanceTab" class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Truck</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Next Due</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($maintenances as $item): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm"><?php echo $item['id']; ?></td>
                            <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($item['truck_plate']); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($item['maintenance_type']); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars(substr($item['description'], 0, 30)); ?>...</td>
                            <td class="px-6 py-4 text-sm">$<?php echo number_format($item['cost'], 2); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo date('Y-m-d', strtotime($item['maintenance_date'])); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo $item['next_due_date'] ? date('Y-m-d', strtotime($item['next_due_date'])) : 'N/A'; ?></td>
                            <td class="px-6 py-4">
                                <span class="status-badge status-<?php echo $item['status']; ?>">
                                    <?php echo str_replace('_', ' ', ucfirst($item['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="editMaintenance(<?php echo htmlspecialchars(json_encode($item)); ?>)" class="text-blue-600 hover:text-blue-800 mr-2">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button onclick="deleteMaintenance(<?php echo $item['id']; ?>)" class="text-red-600 hover:text-red-800">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Incidents Tab (Placeholder) -->
        <div id="incidentsTab" class="hidden bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-8 text-center text-gray-500">
                <i class="ri-alert-line text-6xl mb-4"></i>
                <p>Incidents module coming soon. You can report incidents using the button above.</p>
            </div>
        </div>
    </div>
</main>

<!-- Add Maintenance Modal -->
<div id="addMaintenanceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold mb-4">Log Maintenance</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="space-y-4">
                <select name="truck_id" required class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Select Truck</option>
                    <?php foreach($trucks as $truck): ?>
                    <option value="<?php echo $truck['id']; ?>"><?php echo $truck['plate_number'] . ' - ' . $truck['model']; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="maintenance_type" placeholder="Maintenance Type" required class="w-full px-3 py-2 border rounded-lg">
                <textarea name="description" placeholder="Description" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
                <input type="number" step="0.01" name="cost" placeholder="Cost ($)" class="w-full px-3 py-2 border rounded-lg">
                <input type="date" name="maintenance_date" required class="w-full px-3 py-2 border rounded-lg">
                <input type="date" name="next_due_date" class="w-full px-3 py-2 border rounded-lg">
                <input type="number" name="odometer_at_service" placeholder="Odometer at Service (km)" class="w-full px-3 py-2 border rounded-lg">
                <input type="text" name="performed_by" placeholder="Performed By" class="w-full px-3 py-2 border rounded-lg">
                <select name="status" class="w-full px-3 py-2 border rounded-lg">
                    <option value="scheduled">Scheduled</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
                <textarea name="notes" placeholder="Additional Notes" rows="2" class="w-full px-3 py-2 border rounded-lg"></textarea>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('addMaintenanceModal')" class="px-4 py-2 border rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Incident Modal -->
<div id="addIncidentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Report Incident</h3>
        <form method="POST" action="incident_process.php">
            <div class="space-y-4">
                <select name="truck_id" required class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Select Truck</option>
                    <?php foreach($trucks as $truck): ?>
                    <option value="<?php echo $truck['id']; ?>"><?php echo $truck['plate_number']; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="incident_number" placeholder="Incident Number" required class="w-full px-3 py-2 border rounded-lg">
                <input type="datetime-local" name="incident_date" required class="w-full px-3 py-2 border rounded-lg">
                <input type="text" name="location" placeholder="Location" required class="w-full px-3 py-2 border rounded-lg">
                <textarea name="description" placeholder="Incident Description" rows="3" required class="w-full px-3 py-2 border rounded-lg"></textarea>
                <select name="severity" class="w-full px-3 py-2 border rounded-lg">
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="critical">Critical</option>
                </select>
                <input type="number" step="0.01" name="cost_impact" placeholder="Cost Impact ($)" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('addIncidentModal')" class="px-4 py-2 border rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg">Report Incident</button>
            </div>
        </form>
    </div>
</div>

<script>
function showTab(tab) {
    if(tab === 'maintenance') {
        document.getElementById('maintenanceTab').classList.remove('hidden');
        document.getElementById('incidentsTab').classList.add('hidden');
        document.getElementById('tabMaintenanceBtn').classList.add('border-blue-600', 'text-blue-600');
        document.getElementById('tabIncidentsBtn').classList.remove('border-blue-600', 'text-blue-600');
        document.getElementById('tabIncidentsBtn').classList.add('border-transparent');
    } else {
        document.getElementById('maintenanceTab').classList.add('hidden');
        document.getElementById('incidentsTab').classList.remove('hidden');
        document.getElementById('tabIncidentsBtn').classList.add('border-blue-600', 'text-blue-600');
        document.getElementById('tabMaintenanceBtn').classList.remove('border-blue-600', 'text-blue-600');
        document.getElementById('tabMaintenanceBtn').classList.add('border-transparent');
    }
}

function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}

function editMaintenance(item) {
    alert('Edit maintenance record for truck: ' + item.truck_plate);
}

function deleteMaintenance(id) {
    if(confirm('Are you sure you want to delete this maintenance record?')) {
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