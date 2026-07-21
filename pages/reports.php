<?php
/**
 * @var PDO $db
 */
require_once '../includes/check_auth.php';
require_once '../models/Trip.php';
require_once '../models/Truck.php';
require_once '../models/Driver.php';
require_once '../models/FuelRecord.php';

$tripModel = new Trip($db);
$truckModel = new Truck($db);
$driverModel = new Driver($db);
$fuelModel = new FuelRecord($db);

// Get filters from URL
$date_from = isset($_GET['date_from']) && $_GET['date_from'] !== '' ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$date_to = isset($_GET['date_to']) && $_GET['date_to'] !== '' ? $_GET['date_to'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'summary';

$company_id = $_SESSION['company_id'];

// Get trips with date filter
$tripSql = "SELECT t.*, 
            tr.plate_number as truck_plate,
            d.full_name as driver_name,
            r.name as route_name
            FROM trips t
            LEFT JOIN trucks tr ON t.truck_id = tr.id
            LEFT JOIN drivers d ON t.driver_id = d.id
            LEFT JOIN routes r ON t.route_id = r.id
            WHERE t.company_id = :company_id
            AND DATE(t.created_at) BETWEEN :date_from AND :date_to
            ORDER BY t.created_at DESC";
$tripStmt = $db->prepare($tripSql);
$tripStmt->bindParam(':company_id', $company_id);
$tripStmt->bindParam(':date_from', $date_from);
$tripStmt->bindParam(':date_to', $date_to);
$tripStmt->execute();
$trips = $tripStmt->fetchAll(PDO::FETCH_ASSOC);

// Get fuel records with date filter
$fuelSql = "SELECT f.*, t.plate_number as truck_plate
            FROM fuel_records f
            INNER JOIN trucks t ON f.truck_id = t.id
            WHERE t.company_id = :company_id
            AND DATE(f.fuel_date) BETWEEN :date_from AND :date_to
            ORDER BY f.fuel_date DESC";
$fuelStmt = $db->prepare($fuelSql);
$fuelStmt->bindParam(':company_id', $company_id);
$fuelStmt->bindParam(':date_from', $date_from);
$fuelStmt->bindParam(':date_to', $date_to);
$fuelStmt->execute();
$fuelRecords = $fuelStmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats (also filtered by date)
$tripStats = $tripModel->getStats();
$truckStats = $truckModel->getStats();
$driverStats = $driverModel->getStats();
$fuelStats = $fuelModel->getStats();

// Handle Export
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $export_data = $_GET['data'] ?? 'trips';
    
    if ($export_type === 'csv') {
        exportCSV($export_data, $trips, $fuelRecords);
    } elseif ($export_type === 'html') {
        exportHTML($export_data, $trips, $fuelRecords, $tripStats, $truckStats, $fuelStats);
    }
    exit();
}

function exportCSV(string $data_type, array $trips, array $fuelRecords): void {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $data_type . '_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if ($data_type === 'trips') {
        fputcsv($output, ['Trip #', 'Truck', 'Driver', 'Route', 'Status', 'Priority', 'Start', 'End', 'Client', 'Created']);
        foreach($trips as $trip) {
            fputcsv($output, [
                $trip['trip_number'],
                $trip['truck_plate'] ?? 'N/A',
                $trip['driver_name'] ?? 'N/A',
                $trip['route_name'] ?? 'N/A',
                $trip['status'],
                $trip['priority'],
                $trip['start_time'] ?? 'N/A',
                $trip['end_time'] ?? 'N/A',
                $trip['client_name'] ?? 'N/A',
                date('Y-m-d', strtotime($trip['created_at']))
            ]);
        }
    } elseif ($data_type === 'fuel') {
        fputcsv($output, ['Truck', 'Date', 'Liters', 'Cost/Liter', 'Total Cost', 'Station', 'Invoice']);
        foreach($fuelRecords as $record) {
            fputcsv($output, [
                $record['truck_plate'],
                $record['fuel_date'],
                $record['liters'],
                $record['cost_per_liter'],
                $record['total_cost'],
                $record['fuel_station'] ?? 'N/A',
                $record['invoice_number'] ?? 'N/A'
            ]);
        }
    }
    fclose($output);
}

function exportHTML(string $data_type, array $trips, array $fuelRecords, array $tripStats, array $truckStats, array $fuelStats): void {
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="report_' . date('Y-m-d') . '.html"');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Fleet Report</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            h1 { color: #1f2937; border-bottom: 2px solid #3b82f6; padding-bottom: 10px; }
            h2 { color: #374151; margin-top: 25px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { background: #3758f9; color: white; padding: 10px; text-align: left; }
            td { padding: 8px 10px; border-bottom: 1px solid #e5e7eb; }
            .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 15px 0; }
            .stat-card { background: #f3f4f6; padding: 15px; border-radius: 8px; text-align: center; }
            .stat-number { font-size: 24px; font-weight: bold; color: #3758f9; }
            .stat-label { font-size: 12px; color: #6b7280; }
            .footer { margin-top: 30px; border-top: 1px solid #e5e7eb; padding-top: 10px; text-align: center; color: #9ca3af; font-size: 12px; }
        </style>
    </head>
    <body>
        <h1>Fleet Management Report</h1>
        <p><strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <h2>Summary</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $tripStats['total'] ?? 0; ?></div>
                <div class="stat-label">Total Trips</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $truckStats['total'] ?? 0; ?></div>
                <div class="stat-label">Total Trucks</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $fuelStats['total_liters'] ?? 0; ?></div>
                <div class="stat-label">Total Fuel (L)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($fuelStats['total_cost'] ?? 0, 2); ?></div>
                <div class="stat-label">Total Fuel Cost</div>
            </div>
        </div>
        
        <h2>Trips</h2>
        <table>
            <thead><tr><th>Trip #</th><th>Truck</th><th>Driver</th><th>Status</th><th>Start</th></tr></thead>
            <tbody>
                <?php foreach(array_slice($trips, 0, 50) as $trip): ?>
                <tr>
                    <td><?php echo $trip['trip_number']; ?></td>
                    <td><?php echo $trip['truck_plate'] ?? 'N/A'; ?></td>
                    <td><?php echo $trip['driver_name'] ?? 'N/A'; ?></td>
                    <td><?php echo $trip['status']; ?></td>
                    <td><?php echo $trip['start_time'] ? date('Y-m-d', strtotime($trip['start_time'])) : 'N/A'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="footer">Generated by Fleet Management System &bull; <?php echo date('Y'); ?></div>
    </body>
    </html>
    <?php
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="bg-gray-50">

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Reports & Analytics</h2>
            <p class="text-gray-500">View fleet performance metrics and export reports</p>
        </div>
        
        <!-- Filter Section -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                    <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                    <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Report Type</label>
                    <select name="report_type" class="w-full px-3 py-2 border rounded-lg">
                        <option value="summary" <?php echo $report_type == 'summary' ? 'selected' : ''; ?>>Summary</option>
                        <option value="trips" <?php echo $report_type == 'trips' ? 'selected' : ''; ?>>Trips</option>
                        <option value="fuel" <?php echo $report_type == 'fuel' ? 'selected' : ''; ?>>Fuel</option>
                    </select>
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 w-full">Apply Filters</button>
                </div>
            </form>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm">Total Trips</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-2"><?php echo $tripStats['total'] ?? 0; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="ri-map-pin-line text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm">Completion Rate</p>
                        <?php 
                        $total = $tripStats['total'] ?? 0;
                        $completed = $tripStats['completed'] ?? 0;
                        $rate = $total > 0 ? round(($completed / $total) * 100) : 0;
                        ?>
                        <h3 class="text-2xl font-bold text-gray-800 mt-2"><?php echo $rate; ?>%</h3>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="ri-check-double-line text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm">Total Fuel</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-2"><?php echo number_format($fuelStats['total_liters'] ?? 0, 0); ?> L</h3>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="ri-gas-station-line text-orange-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm">Avg Cost/Liter</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-2">$<?php echo number_format($fuelStats['avg_cost'] ?? 0, 3); ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="ri-bar-chart-2-line text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Trip Status</h3>
                <canvas id="tripChart" height="250"></canvas>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Fleet Status</h3>
                <canvas id="fleetChart" height="250"></canvas>
            </div>
        </div>
        
        <!-- Export Options -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Export Reports</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button onclick="exportReport('trips', 'csv')" class="flex items-center justify-center gap-2 px-4 py-3 border-2 border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition font-medium">
                    <i class="ri-file-excel-line text-xl"></i> Trips (CSV)
                </button>
                <button onclick="exportReport('fuel', 'csv')" class="flex items-center justify-center gap-2 px-4 py-3 border-2 border-green-600 text-green-600 rounded-lg hover:bg-green-50 transition font-medium">
                    <i class="ri-file-excel-line text-xl"></i> Fuel (CSV)
                </button>
                <button onclick="exportReport('all', 'html')" class="flex items-center justify-center gap-2 px-4 py-3 border-2 border-purple-600 text-purple-600 rounded-lg hover:bg-purple-50 transition font-medium">
                    <i class="ri-file-pdf-line text-xl"></i> Full Report (HTML)
                </button>
            </div>
        </div>
        
        <!-- Recent Trips -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-semibold text-gray-800">Recent Trips</h3>
                <span class="text-sm text-gray-500">Showing <?php echo min(count($trips), 10); ?> trips</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trip #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Truck</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if(empty($trips)): ?>
                        <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No trips found for selected date range</td></tr>
                        <?php else: ?>
                        <?php foreach(array_slice($trips, 0, 10) as $trip): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($trip['trip_number']); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($trip['truck_plate'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($trip['driver_name'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4">
                                <span class="status-badge status-<?php echo $trip['status']; ?>">
                                    <?php echo ucfirst($trip['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm"><?php echo $trip['start_time'] ? date('Y-m-d', strtotime($trip['start_time'])) : 'N/A'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
// Trip Chart
new Chart(document.getElementById('tripChart'), {
    type: 'doughnut',
    data: {
        labels: ['Planned', 'Ongoing', 'Completed', 'Cancelled'],
        datasets: [{
            data: [
                <?php echo $tripStats['planned'] ?? 0; ?>,
                <?php echo $tripStats['ongoing'] ?? 0; ?>,
                <?php echo $tripStats['completed'] ?? 0; ?>,
                <?php echo $tripStats['cancelled'] ?? 0; ?>
            ],
            backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#ef4444'],
            borderWidth: 0
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

// Fleet Chart
new Chart(document.getElementById('fleetChart'), {
    type: 'bar',
    data: {
        labels: ['Active', 'Maintenance', 'Inactive'],
        datasets: [{
            label: 'Trucks',
            data: [
                <?php echo $truckStats['active'] ?? 0; ?>,
                <?php echo $truckStats['maintenance'] ?? 0; ?>,
                <?php echo $truckStats['inactive'] ?? 0; ?>
            ],
            backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: { y: { beginAtZero: true } }
    }
});

function exportReport(dataType, format) {
    const dateFrom = document.querySelector('input[name="date_from"]')?.value || '';
    const dateTo = document.querySelector('input[name="date_to"]')?.value || '';
    window.location.href = `?export=${format}&data=${dataType}&date_from=${dateFrom}&date_to=${dateTo}`;
}
</script>

<style>
.status-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
.status-planned { background: #e0e7ff; color: #3730a3; }
.status-ongoing { background: #dbeafe; color: #1e40af; }
.status-completed { background: #d1fae5; color: #065f46; }
.status-cancelled { background: #fee2e2; color: #991b1b; }
</style>
</body>
</html>