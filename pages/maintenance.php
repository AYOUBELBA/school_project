<?php
require_once '../includes/check_auth.php';

$company_id = $_SESSION['company_id'] ?? 0;

// Get maintenance records ONLY for trucks in THIS company
$mainSql = "SELECT m.*, t.plate_number as truck_plate, t.model as truck_model,
            d.full_name as driver_name
            FROM truck_maintenance m
            INNER JOIN trucks t ON m.truck_id = t.id
            LEFT JOIN drivers d ON t.current_driver_id = d.id
            WHERE t.company_id = :company_id
            ORDER BY m.maintenance_date DESC";
$mainStmt = $db->prepare($mainSql);
$mainStmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
$mainStmt->execute();
$maintenances = $mainStmt->fetchAll(PDO::FETCH_ASSOC);

// Get incidents for THIS company
$incSql = "SELECT i.*, t.plate_number as truck_plate, d.full_name as driver_name
           FROM incidents i
           INNER JOIN trucks t ON i.truck_id = t.id
           LEFT JOIN drivers d ON i.driver_id = d.id
           WHERE t.company_id = :company_id
           ORDER BY i.incident_date DESC";
$incStmt = $db->prepare($incSql);
$incStmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
$incStmt->execute();
$incidents = $incStmt->fetchAll(PDO::FETCH_ASSOC);
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
        <div id="maintenanceTab">
            <?php if(empty($maintenances)): ?>
                <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
                    <i class="ri-tools-line text-5xl mb-3 block"></i>
                    <p>No maintenance records found for your company</p>
                    <p class="text-sm mt-2">Add a truck first to see maintenance records</p>
                </div>
            <?php else: ?>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach($maintenances as $item): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm"><?php echo $item['id']; ?></td>
                                <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($item['truck_plate']); ?></td>
                                <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($item['maintenance_type']); ?></td>
                                <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars(substr($item['description'], 0, 50)); ?>...</td>
                                <td class="px-6 py-4 text-sm">$<?php echo number_format($item['cost'], 2); ?></td>
                                <td class="px-6 py-4 text-sm"><?php echo date('Y-m-d', strtotime($item['maintenance_date'])); ?></td>
                                <td class="px-6 py-4">
                                    <span class="status-badge status-<?php echo $item['status']; ?>">
                                        <?php echo str_replace('_', ' ', ucfirst($item['status'])); ?>
                                    </span>
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
                    <p>No incidents found for your company</p>
                    <p class="text-sm mt-2">Incidents will appear here when reported</p>
                </div>
            <?php else: ?>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Incident #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Truck</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Driver</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Severity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach($incidents as $incident): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($incident['incident_number']); ?></td>
                                <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($incident['truck_plate']); ?></td>
                                <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($incident['driver_name']); ?></td>
                                <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars(substr($incident['description'], 0, 50)); ?>...</td>
                                <td class="px-6 py-4">
                                    <span class="status-badge severity-<?php echo $incident['severity']; ?>">
                                        <?php echo ucfirst($incident['severity']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm"><?php echo date('Y-m-d H:i', strtotime($incident['incident_date'])); ?></td>
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
</script>
</body>
</html>