<?php
/**
 * @var PDO $db
 */
require_once '../includes/check_auth.php';

$company_id = $_SESSION['company_id'] ?? 0;

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_maintenance':
                $id = $_POST['id'];
                $status = $_POST['status'];
                $assigned_to = !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null;
                $estimated_cost = !empty($_POST['estimated_cost']) ? $_POST['estimated_cost'] : null;
                $actual_cost = !empty($_POST['actual_cost']) ? $_POST['actual_cost'] : null;
                $completion_notes = $_POST['completion_notes'] ?? '';
                
                $updateSql = "UPDATE truck_maintenance SET 
                              status = :status, 
                              assigned_to = :assigned_to,
                              estimated_cost = :estimated_cost,
                              actual_cost = :actual_cost,
                              completion_notes = :completion_notes,
                              updated_at = CURRENT_TIMESTAMP
                              WHERE id = :id";
                $updateStmt = $db->prepare($updateSql);
                $updateStmt->bindParam(':status', $status);
                $updateStmt->bindParam(':assigned_to', $assigned_to);
                $updateStmt->bindParam(':estimated_cost', $estimated_cost);
                $updateStmt->bindParam(':actual_cost', $actual_cost);
                $updateStmt->bindParam(':completion_notes', $completion_notes);
                $updateStmt->bindParam(':id', $id);
                $updateStmt->execute();
                $_SESSION['flash_success'] = "Maintenance record updated";
                break;
                
            case 'update_incident':
                $id = $_POST['id'];
                $status = $_POST['status'];
                $resolution_notes = $_POST['resolution_notes'] ?? '';
                
                $updateSql = "UPDATE incidents SET 
                              resolution_notes = :resolution_notes,
                              resolved_at = " . ($status === 'resolved' ? 'CURRENT_TIMESTAMP' : 'NULL') . "
                              WHERE id = :id";
                $updateStmt = $db->prepare($updateSql);
                $updateStmt->bindParam(':resolution_notes', $resolution_notes);
                $updateStmt->bindParam(':id', $id);
                $updateStmt->execute();
                $_SESSION['flash_success'] = "Incident updated";
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $type = $_POST['type'];
                if ($type === 'maintenance') {
                    $delSql = "DELETE FROM truck_maintenance WHERE id = :id";
                } else {
                    $delSql = "DELETE FROM incidents WHERE id = :id";
                }
                $delStmt = $db->prepare($delSql);
                $delStmt->bindParam(':id', $id);
                $delStmt->execute();
                $_SESSION['flash_success'] = "Record deleted";
                break;
        }
        header("Location: maintenance.php");
        exit();
    }
}

// Get filters
$status_filter = $_GET['status'] ?? 'all';
$priority_filter = $_GET['priority'] ?? 'all';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Get maintenance records with filters
$mainSql = "SELECT m.*, t.plate_number as truck_plate, t.model as truck_model,
            d.full_name as driver_name,
            u.username as assigned_to_name
            FROM truck_maintenance m
            INNER JOIN trucks t ON m.truck_id = t.id
            LEFT JOIN drivers d ON t.current_driver_id = d.id
            LEFT JOIN users u ON m.assigned_to = u.id
            WHERE t.company_id = :company_id";
$params = [':company_id' => $company_id];

if ($status_filter !== 'all') {
    $mainSql .= " AND m.status = :status";
    $params[':status'] = $status_filter;
}
if ($priority_filter !== 'all') {
    $mainSql .= " AND m.priority = :priority";
    $params[':priority'] = $priority_filter;
}
$mainSql .= " AND DATE(m.created_at) BETWEEN :date_from AND :date_to
              ORDER BY 
                CASE m.priority 
                  WHEN 'critical' THEN 1 
                  WHEN 'high' THEN 2 
                  WHEN 'medium' THEN 3 
                  WHEN 'low' THEN 4 
                END,
                m.created_at DESC";

$mainStmt = $db->prepare($mainSql);
$mainStmt->bindParam(':company_id', $company_id);
if ($status_filter !== 'all') $mainStmt->bindParam(':status', $status_filter);
if ($priority_filter !== 'all') $mainStmt->bindParam(':priority', $priority_filter);
$mainStmt->bindParam(':date_from', $date_from);
$mainStmt->bindParam(':date_to', $date_to);
$mainStmt->execute();
$maintenances = $mainStmt->fetchAll(PDO::FETCH_ASSOC);

// Get incidents with filters
$incSql = "SELECT i.*, t.plate_number as truck_plate, d.full_name as driver_name
           FROM incidents i
           INNER JOIN trucks t ON i.truck_id = t.id
           LEFT JOIN drivers d ON i.driver_id = d.id
           WHERE t.company_id = :company_id";
$paramsInc = [':company_id' => $company_id];

if ($status_filter !== 'all' && $status_filter !== 'planned' && $status_filter !== 'scheduled') {
    $incSql .= " AND i.resolved_at IS " . ($status_filter === 'resolved' ? 'NOT NULL' : 'NULL');
}
if ($priority_filter !== 'all') {
    $incSql .= " AND i.severity = :severity";
    $paramsInc[':severity'] = $priority_filter;
}
$incSql .= " AND DATE(i.created_at) BETWEEN :date_from AND :date_to
             ORDER BY 
               CASE i.severity 
                 WHEN 'critical' THEN 1 
                 WHEN 'high' THEN 2 
                 WHEN 'medium' THEN 3 
                 WHEN 'low' THEN 4 
               END,
               i.created_at DESC";

$incStmt = $db->prepare($incSql);
$incStmt->bindParam(':company_id', $company_id);
if (isset($paramsInc[':severity'])) $incStmt->bindParam(':severity', $paramsInc[':severity']);
$incStmt->bindParam(':date_from', $date_from);
$incStmt->bindParam(':date_to', $date_to);
$incStmt->execute();
$incidents = $incStmt->fetchAll(PDO::FETCH_ASSOC);

// Get available staff for assignment
$staffSql = "SELECT id, username FROM users WHERE company_id = :company_id AND role IN ('admin', 'manager') ORDER BY username";
$staffStmt = $db->prepare($staffSql);
$staffStmt->bindParam(':company_id', $company_id);
$staffStmt->execute();
$staff = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Get status counts
$countSql = "SELECT status, COUNT(*) as count FROM truck_maintenance WHERE company_id = :company_id GROUP BY status";
$countStmt = $db->prepare($countSql);
$countStmt->bindParam(':company_id', $company_id);
$countStmt->execute();
$statusCounts = $countStmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance & Incidents | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Maintenance & Incidents</h2>
        
        <?php if($flash_success): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg"><?php echo $flash_success; ?></div>
        <?php endif; ?>
        
        <!-- Status Cards -->
        <div class="grid grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl p-4 shadow-sm text-center">
                <div class="text-2xl font-bold text-blue-600"><?php echo $statusCounts['scheduled'] ?? 0; ?></div>
                <div class="text-xs text-gray-500">Scheduled</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm text-center">
                <div class="text-2xl font-bold text-yellow-600"><?php echo $statusCounts['in_progress'] ?? 0; ?></div>
                <div class="text-xs text-gray-500">In Progress</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm text-center">
                <div class="text-2xl font-bold text-green-600"><?php echo $statusCounts['completed'] ?? 0; ?></div>
                <div class="text-xs text-gray-500">Completed</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm text-center">
                <div class="text-2xl font-bold text-red-600"><?php echo $statusCounts['cancelled'] ?? 0; ?></div>
                <div class="text-xs text-gray-500">Cancelled</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm text-center">
                <div class="text-2xl font-bold text-purple-600"><?php echo count($maintenances) + count($incidents); ?></div>
                <div class="text-xs text-gray-500">Total Requests</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full px-3 py-2 border rounded-lg">
                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="scheduled" <?php echo $status_filter == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select name="priority" class="w-full px-3 py-2 border rounded-lg">
                        <option value="all" <?php echo $priority_filter == 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="critical" <?php echo $priority_filter == 'critical' ? 'selected' : ''; ?>>Critical</option>
                        <option value="high" <?php echo $priority_filter == 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="medium" <?php echo $priority_filter == 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="low" <?php echo $priority_filter == 'low' ? 'selected' : ''; ?>>Low</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 w-full">Apply</button>
                    <a href="maintenance.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 w-full text-center">Reset</a>
                </div>
            </form>
        </div>

        <!-- Tabs -->
        <div class="mb-6 border-b border-gray-200">
            <ul class="flex flex-wrap -mb-px">
                <li class="mr-2">
                    <button onclick="showTab('maintenance')" id="tabMaintenanceBtn" class="inline-block p-4 border-b-2 border-blue-600 text-blue-600 font-medium">Maintenance</button>
                </li>
                <li class="mr-2">
                    <button onclick="showTab('incidents')" id="tabIncidentsBtn" class="inline-block p-4 border-b-2 border-transparent hover:text-gray-600">Incidents</button>
                </li>
            </ul>
        </div>

        <!-- Maintenance Tab -->
        <div id="maintenanceTab">
            <?php if(empty($maintenances)): ?>
            <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
                <i class="ri-tools-line text-5xl mb-3 block"></i>
                <p>No maintenance records found</p>
            </div>
            <?php else: ?>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Truck</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Priority</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Assigned To</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Cost</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach($maintenances as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($item['truck_plate']); ?></td>
                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($item['maintenance_type']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full <?php 
                                        echo $item['status'] === 'completed' ? 'bg-green-100 text-green-700' : 
                                            ($item['status'] === 'in_progress' ? 'bg-yellow-100 text-yellow-700' :
                                            ($item['status'] === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700')); ?>">
                                        <?php echo str_replace('_', ' ', ucfirst($item['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full <?php 
                                        echo $item['priority'] === 'critical' ? 'bg-red-100 text-red-700' : 
                                            ($item['priority'] === 'high' ? 'bg-orange-100 text-orange-700' :
                                            ($item['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700')); ?>">
                                        <?php echo ucfirst($item['priority'] ?? 'Medium'); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($item['assigned_to_name'] ?? 'Unassigned'); ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <?php if($item['actual_cost']): ?>
                                    $<?php echo number_format($item['actual_cost'], 2); ?>
                                    <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <button onclick="openModal('editMaintenanceModal', <?php echo htmlspecialchars(json_encode($item)); ?>)" class="text-blue-600 hover:text-blue-800 mr-2">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button onclick="deleteRecord(<?php echo $item['id']; ?>, 'maintenance')" class="text-red-600 hover:text-red-800">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Incidents Tab -->
        <div id="incidentsTab" class="hidden">
            <?php if(empty($incidents)): ?>
            <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
                <i class="ri-alert-line text-5xl mb-3 block"></i>
                <p>No incidents found</p>
            </div>
            <?php else: ?>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Incident #</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Truck</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Severity</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach($incidents as $incident): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium"><?php echo htmlspecialchars($incident['incident_number']); ?></td>
                                <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($incident['truck_plate']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full <?php 
                                        echo $incident['severity'] === 'critical' ? 'bg-red-100 text-red-700' : 
                                            ($incident['severity'] === 'high' ? 'bg-orange-100 text-orange-700' :
                                            ($incident['severity'] === 'medium' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700')); ?>">
                                        <?php echo ucfirst($incident['severity']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 text-xs rounded-full <?php 
                                        echo $incident['resolved_at'] ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>">
                                        <?php echo $incident['resolved_at'] ? 'Resolved' : 'Open'; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm"><?php echo date('Y-m-d', strtotime($incident['incident_date'])); ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <button onclick="openModal('editIncidentModal', <?php echo htmlspecialchars(json_encode($incident)); ?>)" class="text-blue-600 hover:text-blue-800 mr-2">
                                        <i class="ri-edit-line"></i>
                                    </button>
                                    <button onclick="deleteRecord(<?php echo $incident['id']; ?>, 'incident')" class="text-red-600 hover:text-red-800">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Edit Maintenance Modal -->
<div id="editMaintenanceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Update Maintenance</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update_maintenance">
            <input type="hidden" name="id" id="maint_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="maint_status" class="w-full px-3 py-2 border rounded-lg">
                        <option value="scheduled">Scheduled</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                    <select name="assigned_to" id="maint_assigned" class="w-full px-3 py-2 border rounded-lg">
                        <option value="">Select Staff</option>
                        <?php foreach($staff as $person): ?>
                        <option value="<?php echo $person['id']; ?>"><?php echo htmlspecialchars($person['username']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Cost ($)</label>
                    <input type="number" step="0.01" name="estimated_cost" id="maint_estimated" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Actual Cost ($)</label>
                    <input type="number" step="0.01" name="actual_cost" id="maint_actual" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Completion Notes</label>
                    <textarea name="completion_notes" id="maint_notes" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('editMaintenanceModal')" class="px-4 py-2 border rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Incident Modal -->
<div id="editIncidentModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Update Incident</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update_incident">
            <input type="hidden" name="id" id="inc_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" id="inc_status" class="w-full px-3 py-2 border rounded-lg">
                        <option value="open">Open</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Resolution Notes</label>
                    <textarea name="resolution_notes" id="inc_notes" rows="3" class="w-full px-3 py-2 border rounded-lg"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('editIncidentModal')" class="px-4 py-2 border rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Update</button>
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
    } else {
        document.getElementById('maintenanceTab').classList.add('hidden');
        document.getElementById('incidentsTab').classList.remove('hidden');
        document.getElementById('tabIncidentsBtn').classList.add('border-blue-600', 'text-blue-600');
        document.getElementById('tabMaintenanceBtn').classList.remove('border-blue-600', 'text-blue-600');
    }
}

function openModal(modalId, data) {
    if (modalId === 'editMaintenanceModal') {
        document.getElementById('maint_id').value = data.id;
        document.getElementById('maint_status').value = data.status || 'scheduled';
        document.getElementById('maint_assigned').value = data.assigned_to || '';
        document.getElementById('maint_estimated').value = data.estimated_cost || '';
        document.getElementById('maint_actual').value = data.actual_cost || '';
        document.getElementById('maint_notes').value = data.completion_notes || '';
    } else if (modalId === 'editIncidentModal') {
        document.getElementById('inc_id').value = data.id;
        document.getElementById('inc_status').value = data.resolved_at ? 'resolved' : 'open';
        document.getElementById('inc_notes').value = data.resolution_notes || '';
    }
    document.getElementById(modalId).classList.remove('hidden');
    document.getElementById(modalId).classList.add('flex');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
    document.getElementById(modalId).classList.remove('flex');
}

function deleteRecord(id, type) {
    if(confirm('Are you sure you want to delete this record?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
            <input type="hidden" name="type" value="${type}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.classList.add('hidden');
        event.target.classList.remove('flex');
    }
}
</script>
</body>
</html>