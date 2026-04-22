<?php
require_once '../config/session.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if(!isset($_SESSION['user_id'])) {
    header("Location: ../signin.php");
    exit();
}

$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
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
        
        .clickable-row {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .clickable-row:hover {
            background-color: #f3f4f6;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .detail-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-label {
            width: 120px;
            font-weight: 500;
            color: #6b7280;
        }
        .detail-value {
            flex: 1;
            color: #1f2937;
        }
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
                <li class="mr-2">
                    <button onclick="showTab('reports')" id="tabReportsBtn" class="inline-block p-4 border-b-2 border-transparent hover:text-gray-600 hover:border-gray-300">Driver Reports</button>
                </li>
            </ul>
        </div>
        
        <!-- Maintenance Tab -->
        <div id="maintenanceTab">
            <?php
            $mainSql = "SELECT m.*, t.plate_number as truck_plate, t.model as truck_model,
                        d.full_name as driver_name
                        FROM truck_maintenance m
                        LEFT JOIN trucks t ON m.truck_id = t.id
                        LEFT JOIN drivers d ON t.current_driver_id = d.id
                        ORDER BY m.maintenance_date DESC";
            $mainStmt = $db->prepare($mainSql);
            $mainStmt->execute();
            $maintenances = $mainStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
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
                            <tr class="clickable-row" onclick='showMaintenanceDetail(<?php echo json_encode($item); ?>)'>
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
        </div>
        
        <!-- Incidents Tab -->
        <div id="incidentsTab" class="hidden">
            <?php
            $incSql = "SELECT i.*, t.plate_number as truck_plate, d.full_name as driver_name
                       FROM incidents i
                       LEFT JOIN trucks t ON i.truck_id = t.id
                       LEFT JOIN drivers d ON i.driver_id = d.id
                       ORDER BY i.incident_date DESC";
            $incStmt = $db->prepare($incSql);
            $incStmt->execute();
            $incidents = $incStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
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
                            <tr class="clickable-row" onclick='showIncidentDetail(<?php echo json_encode($incident); ?>)'>
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
        </div>
        
        <!-- Driver Reports Tab -->
        <div id="reportsTab" class="hidden">
            <?php
            $reportSql = "SELECT i.*, t.plate_number as truck_plate, d.full_name as driver_name,
                          u.username as reported_by_name
                          FROM incidents i
                          LEFT JOIN trucks t ON i.truck_id = t.id
                          LEFT JOIN drivers d ON i.driver_id = d.id
                          LEFT JOIN users u ON i.reported_by = u.id
                          WHERE i.severity = 'low' OR i.description LIKE '%issue%'
                          ORDER BY i.created_at DESC
                          LIMIT 50";
            $reportStmt = $db->prepare($reportSql);
            $reportStmt->execute();
            $reports = $reportStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="p-4 bg-yellow-50 border-b border-yellow-200">
                    <p class="text-sm text-yellow-800">📢 Reports submitted by drivers via mobile app</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Driver</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Truck</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Report</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach($reports as $report): ?>
                            <tr class="clickable-row" onclick='showReportDetail(<?php echo json_encode($report); ?>)'>
                                <td class="px-6 py-4 text-sm"><?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?></td>
                                <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($report['driver_name']); ?></td>
                                <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($report['truck_plate']); ?></td>
                                <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars(substr($report['description'], 0, 80)); ?></td>
                                <td class="px-6 py-4">
                                    <span class="status-badge status-scheduled">Pending Review</span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($reports)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">No driver reports yet</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Maintenance Detail Modal -->
<div id="maintenanceModal" class="modal">
    <div class="modal-content">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Maintenance Details</h3>
                <button onclick="closeModal('maintenanceModal')" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div id="maintenanceDetailContent"></div>
            <div class="flex justify-end mt-6">
                <button onclick="closeModal('maintenanceModal')" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Incident Detail Modal -->
<div id="incidentModal" class="modal">
    <div class="modal-content">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Incident Details</h3>
                <button onclick="closeModal('incidentModal')" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div id="incidentDetailContent"></div>
            <div class="flex justify-end mt-6">
                <button onclick="closeModal('incidentModal')" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Report Detail Modal -->
<div id="reportModal" class="modal">
    <div class="modal-content">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Driver Report Details</h3>
                <button onclick="closeModal('reportModal')" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div id="reportDetailContent"></div>
            <div class="flex justify-end gap-3 mt-6">
                <button onclick="closeModal('reportModal')" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Close</button>
                <button onclick="markResolvedFromModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Mark Resolved</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentReportId = null;

function showTab(tab) {
    if(tab === 'maintenance') {
        document.getElementById('maintenanceTab').classList.remove('hidden');
        document.getElementById('incidentsTab').classList.add('hidden');
        document.getElementById('reportsTab').classList.add('hidden');
        document.getElementById('tabMaintenanceBtn').classList.add('border-blue-600', 'text-blue-600');
        document.getElementById('tabIncidentsBtn').classList.remove('border-blue-600', 'text-blue-600');
        document.getElementById('tabReportsBtn').classList.remove('border-blue-600', 'text-blue-600');
    } else if(tab === 'incidents') {
        document.getElementById('maintenanceTab').classList.add('hidden');
        document.getElementById('incidentsTab').classList.remove('hidden');
        document.getElementById('reportsTab').classList.add('hidden');
        document.getElementById('tabIncidentsBtn').classList.add('border-blue-600', 'text-blue-600');
        document.getElementById('tabMaintenanceBtn').classList.remove('border-blue-600', 'text-blue-600');
        document.getElementById('tabReportsBtn').classList.remove('border-blue-600', 'text-blue-600');
    } else {
        document.getElementById('maintenanceTab').classList.add('hidden');
        document.getElementById('incidentsTab').classList.add('hidden');
        document.getElementById('reportsTab').classList.remove('hidden');
        document.getElementById('tabReportsBtn').classList.add('border-blue-600', 'text-blue-600');
        document.getElementById('tabMaintenanceBtn').classList.remove('border-blue-600', 'text-blue-600');
        document.getElementById('tabIncidentsBtn').classList.remove('border-blue-600', 'text-blue-600');
    }
}

function showMaintenanceDetail(maintenance) {
    const content = `
        <div class="detail-row">
            <div class="detail-label">ID:</div>
            <div class="detail-value">#${maintenance.id}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Truck:</div>
            <div class="detail-value">${maintenance.truck_plate} (${maintenance.truck_model || 'N/A'})</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Maintenance Type:</div>
            <div class="detail-value">${maintenance.maintenance_type}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Description:</div>
            <div class="detail-value">${maintenance.description || 'No description'}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Cost:</div>
            <div class="detail-value">$${parseFloat(maintenance.cost).toFixed(2)}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Maintenance Date:</div>
            <div class="detail-value">${maintenance.maintenance_date}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Next Due Date:</div>
            <div class="detail-value">${maintenance.next_due_date || 'Not set'}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Status:</div>
            <div class="detail-value"><span class="status-badge status-${maintenance.status}">${maintenance.status.replace('_', ' ')}</span></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Performed By:</div>
            <div class="detail-value">${maintenance.performed_by || 'Not assigned'}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Notes:</div>
            <div class="detail-value">${maintenance.notes || 'No notes'}</div>
        </div>
    `;
    document.getElementById('maintenanceDetailContent').innerHTML = content;
    document.getElementById('maintenanceModal').style.display = 'flex';
}

function showIncidentDetail(incident) {
    const severityColors = {
        low: 'severity-low',
        medium: 'severity-medium',
        high: 'severity-high',
        critical: 'severity-critical'
    };
    const content = `
        <div class="detail-row">
            <div class="detail-label">Incident #:</div>
            <div class="detail-value">${incident.incident_number}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Truck:</div>
            <div class="detail-value">${incident.truck_plate}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Driver:</div>
            <div class="detail-value">${incident.driver_name || 'Unknown'}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Location:</div>
            <div class="detail-value">${incident.location || 'Not specified'}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Severity:</div>
            <div class="detail-value"><span class="status-badge ${severityColors[incident.severity]}">${incident.severity.toUpperCase()}</span></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Description:</div>
            <div class="detail-value">${incident.description}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Incident Date:</div>
            <div class="detail-value">${new Date(incident.incident_date).toLocaleString()}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Reported By:</div>
            <div class="detail-value">${incident.reported_by_name || 'System'}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Resolution Notes:</div>
            <div class="detail-value">${incident.resolution_notes || 'Not resolved yet'}</div>
        </div>
    `;
    document.getElementById('incidentDetailContent').innerHTML = content;
    document.getElementById('incidentModal').style.display = 'flex';
}

function showReportDetail(report) {
    currentReportId = report.id;
    const content = `
        <div class="detail-row">
            <div class="detail-label">Report ID:</div>
            <div class="detail-value">#${report.id}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Driver:</div>
            <div class="detail-value">${report.driver_name}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Truck:</div>
            <div class="detail-value">${report.truck_plate}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Report Date:</div>
            <div class="detail-value">${new Date(report.created_at).toLocaleString()}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Description:</div>
            <div class="detail-value">${report.description}</div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Status:</div>
            <div class="detail-value"><span class="status-badge status-scheduled">Pending Review</span></div>
        </div>
    `;
    document.getElementById('reportDetailContent').innerHTML = content;
    document.getElementById('reportModal').style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function markResolvedFromModal() {
    if(confirm('Mark this report as resolved?')) {
        fetch('../api/resolve_incident.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ incident_id: currentReportId })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Report marked as resolved');
                closeModal('reportModal');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>
</body>
</html>