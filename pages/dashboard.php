<?php
require_once('../config/session.php');
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../models/Truck.php';
require_once '../models/Driver.php';
require_once '../models/Trip.php';
require_once '../models/Maintenance.php';



// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../signin.php");
    exit();
}

// Get user info from session
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];
$username = $_SESSION['username'];
$role = $_SESSION['user_role'];


$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$auth->requireLogin();

$truckModel = new Truck($db);
$driverModel = new Driver($db);
$tripModel = new Trip($db);
$maintenanceModel = new Maintenance($db);

// Get all stats
$truckStats = $truckModel->getStats();
$driverStats = $driverModel->getStats();
$tripStats = $tripModel->getStats();
$activeTrips = $tripModel->getActiveTrips();
$expiringLicenses = $driverModel->getExpiringLicenses(30);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Fleet Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; background: #f0f2f5; }
        .sidebar { transition: all 0.3s ease; }
        .sidebar-item { transition: all 0.2s ease; border-left: 3px solid transparent; }
        .sidebar-item:hover, .sidebar-item.active { background: rgba(55, 88, 249, 0.1); border-left-color: #3758f9; }
        .stat-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15); }
        .status-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-maintenance { background: #fed7aa; color: #92400e; }
        .status-ongoing { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-planned { background: #e0e7ff; color: #3730a3; }
        .status-available { background: #d1fae5; color: #065f46; }
        .status-on_trip { background: #dbeafe; color: #1e40af; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.3s ease-out; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card bg-white rounded-xl p-6 shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm">Total Trucks</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-2"><?php echo $truckStats['total']; ?></h3>
                        <p class="text-green-600 text-xs mt-2"><?php echo $truckStats['active']; ?> active</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="ri-truck-line text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card bg-white rounded-xl p-6 shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm">Active Drivers</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-2"><?php echo $driverStats['available']; ?></h3>
                        <p class="text-orange-600 text-xs mt-2"><?php echo $driverStats['on_trip']; ?> on trip</p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="ri-driver-line text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card bg-white rounded-xl p-6 shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm">Active Trips</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-2"><?php echo $tripStats['ongoing']; ?></h3>
                        <p class="text-blue-600 text-xs mt-2"><?php echo $tripStats['planned']; ?> planned</p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="ri-map-pin-line text-orange-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="stat-card bg-white rounded-xl p-6 shadow-sm">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm">Completion Rate</p>
                        <h3 class="text-2xl font-bold text-gray-800 mt-2">
                            <?php 
                            $rate = $tripStats['total'] > 0 ? round(($tripStats['completed'] / $tripStats['total']) * 100) : 0;
                            echo $rate . '%';
                            ?>
                        </h3>
                        <p class="text-green-600 text-xs mt-2"><?php echo $tripStats['completed']; ?> completed trips</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="ri-bar-chart-2-line text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Trip Status Distribution</h3>
                <canvas id="tripChart" height="250"></canvas>
            </div>
            
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Fleet Status Overview</h3>
                <canvas id="fleetChart" height="250"></canvas>
            </div>
        </div>
        
        <!-- Active Trips Table -->
        <div class="bg-white rounded-xl shadow-sm mb-8">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">Active Trips</h3>
                <a href="trips.php" class="text-primary text-sm hover:underline">View All →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trip #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Truck</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Driver</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach(array_slice($activeTrips, 0, 5) as $trip): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($trip['trip_number']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($trip['truck_plate']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($trip['driver_name']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($trip['route_name'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4">
                                <span class="status-badge status-<?php echo $trip['status']; ?>">
                                    <?php echo ucfirst($trip['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo date('Y-m-d H:i', strtotime($trip['start_time'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($activeTrips)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">No active trips found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Expiring Licenses Alert -->
        <?php if(!empty($expiringLicenses)): ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-4 mb-8">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="ri-alert-line text-yellow-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Driver Licenses Expiring Soon</h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <ul class="list-disc list-inside">
                            <?php foreach($expiringLicenses as $license): ?>
                            <li><?php echo htmlspecialchars($license['full_name']); ?> - Expires on <?php echo date('Y-m-d', strtotime($license['license_expiry'])); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
// Trip Status Chart
const tripCtx = document.getElementById('tripChart').getContext('2d');
new Chart(tripCtx, {
    type: 'doughnut',
    data: {
        labels: ['Ongoing', 'Planned', 'Completed'],
        datasets: [{
            data: [<?php echo $tripStats['ongoing']; ?>, <?php echo $tripStats['planned']; ?>, <?php echo $tripStats['completed']; ?>],
            backgroundColor: ['#3758f9', '#f59e0b', '#10b981'],
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

// Fleet Status Chart
const fleetCtx = document.getElementById('fleetChart').getContext('2d');
new Chart(fleetCtx, {
    type: 'bar',
    data: {
        labels: ['Active', 'Maintenance', 'Inactive'],
        datasets: [{
            label: 'Trucks',
            data: [<?php echo $truckStats['active']; ?>, <?php echo $truckStats['maintenance']; ?>, <?php echo $truckStats['inactive']; ?>],
            backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
            borderRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        },
        scales: {
            y: { beginAtZero: true, grid: { display: true }, title: { display: true, text: 'Number of Trucks' } }
        }
    }
});
</script>
</body>
</html>