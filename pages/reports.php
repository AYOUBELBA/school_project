<?php
require_once '../includes/check_auth.php';
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/Trip.php';
require_once '../models/Truck.php';
require_once '../models/FuelRecord.php';

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

$tripModel = new Trip($db);
$truckModel = new Truck($db);
$fuelModel = new FuelRecord($db);

$tripStats = $tripModel->getStats();
$truckStats = $truckModel->getStats();
$fuelStats = $fuelModel->getStats();
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
    <style>
        .report-card { transition: all 0.3s ease; }
        .report-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Reports & Analytics</h2>
            <p class="text-gray-500">View fleet performance metrics and analytics</p>
        </div>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="report-card bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-blue-100 text-sm">Total Trips</p>
                        <h3 class="text-3xl font-bold mt-2"><?php echo $tripStats['total'] ?? 0; ?></h3>
                    </div>
                    <i class="ri-map-pin-line text-3xl text-blue-200"></i>
                </div>
                <div class="mt-4 text-blue-100 text-sm">
                    <span><?php echo $tripStats['completed'] ?? 0; ?> completed</span>
                </div>
            </div>
            <div class="report-card bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-green-100 text-sm">Active Fleet</p>
                        <h3 class="text-3xl font-bold mt-2"><?php echo $truckStats['active'] ?? 0; ?></h3>
                    </div>
                    <i class="ri-truck-line text-3xl text-green-200"></i>
                </div>
                <div class="mt-4 text-green-100 text-sm">
                    <span><?php echo $truckStats['total'] ?? 0; ?> total trucks</span>
                </div>
            </div>
            <div class="report-card bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl p-6 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-orange-100 text-sm">Fuel Consumption</p>
                        <h3 class="text-3xl font-bold mt-2"><?php echo number_format($fuelStats['total_liters'] ?? 0, 0); ?> L</h3>
                    </div>
                    <i class="ri-gas-station-line text-3xl text-orange-200"></i>
                </div>
                <div class="mt-4 text-orange-100 text-sm">
                    <span>$<?php echo number_format($fuelStats['total_cost'] ?? 0, 2); ?> this month</span>
                </div>
            </div>
            <div class="report-card bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-purple-100 text-sm">Completion Rate</p>
                        <?php 
                        $rate = ($tripStats['total'] ?? 0) > 0 ? round((($tripStats['completed'] ?? 0) / ($tripStats['total'] ?? 1)) * 100) : 0;
                        ?>
                        <h3 class="text-3xl font-bold mt-2"><?php echo $rate; ?>%</h3>
                    </div>
                    <i class="ri-bar-chart-2-line text-3xl text-purple-200"></i>
                </div>
                <div class="mt-4 text-purple-100 text-sm">
                    <span>Delivery success rate</span>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Trip Statistics</h3>
                <canvas id="tripChart" height="250"></canvas>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Fleet Status</h3>
                <canvas id="fleetChart" height="250"></canvas>
            </div>
        </div>
        
        <!-- Export Options -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Export Reports</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button onclick="exportReport('trips')" class="flex items-center justify-center gap-2 px-4 py-3 border border-blue-600 text-blue-600 rounded-lg hover:bg-blue-50 transition">
                    <i class="ri-file-excel-line"></i> Export Trips Report
                </button>
                <button onclick="exportReport('fuel')" class="flex items-center justify-center gap-2 px-4 py-3 border border-green-600 text-green-600 rounded-lg hover:bg-green-50 transition">
                    <i class="ri-file-excel-line"></i> Export Fuel Report
                </button>
                <button onclick="exportReport('maintenance')" class="flex items-center justify-center gap-2 px-4 py-3 border border-orange-600 text-orange-600 rounded-lg hover:bg-orange-50 transition">
                    <i class="ri-file-pdf-line"></i> Export Maintenance Report
                </button>
            </div>
        </div>
    </div>
</main>

<script>
// Trip Statistics Chart
const tripCtx = document.getElementById('tripChart').getContext('2d');
new Chart(tripCtx, {
    type: 'bar',
    data: {
        labels: ['Planned', 'Ongoing', 'Completed'],
        datasets: [{
            label: 'Number of Trips',
            data: [<?php echo $tripStats['planned'] ?? 0; ?>, <?php echo $tripStats['ongoing'] ?? 0; ?>, <?php echo $tripStats['completed'] ?? 0; ?>],
            backgroundColor: ['#f59e0b', '#3b82f6', '#10b981'],
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        },
        scales: {
            y: { beginAtZero: true, title: { display: true, text: 'Number of Trips' } }
        }
    }
});

// Fleet Status Chart
const fleetCtx = document.getElementById('fleetChart').getContext('2d');
new Chart(fleetCtx, {
    type: 'doughnut',
    data: {
        labels: ['Active', 'Maintenance', 'Inactive'],
        datasets: [{
            data: [<?php echo $truckStats['active'] ?? 0; ?>, <?php echo $truckStats['maintenance'] ?? 0; ?>, <?php echo $truckStats['inactive'] ?? 0; ?>],
            backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});

function exportReport(type) {
    alert(`Exporting ${type} report as CSV...`);
    // Implement actual export functionality
}
</script>
</body>
</html>