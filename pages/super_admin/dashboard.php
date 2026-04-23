<?php
require_once '../../includes/check_auth.php';

// Only super admin can access
if($_SESSION['user_role'] !== 'super_admin') {
    header("Location: ../dashboard.php");
    exit();
}

// Get system stats
$statsSql = "SELECT 
             (SELECT COUNT(*) FROM users) as total_users,
             (SELECT COUNT(*) FROM companies) as total_companies,
             (SELECT COUNT(*) FROM trucks) as total_trucks,
             (SELECT COUNT(*) FROM drivers) as total_drivers,
             (SELECT COUNT(*) FROM trips WHERE status = 'ongoing') as active_trips,
             (SELECT COUNT(*) FROM trips WHERE status = 'completed') as completed_trips";
$statsStmt = $db->prepare($statsSql);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get recent activity
$activitySql = "SELECT al.*, u.username 
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC 
                LIMIT 10";
$activityStmt = $db->prepare($activitySql);
$activityStmt->execute();
$activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">

<?php include '../../includes/super_admin_sidebar.php'; ?>

<main class="ml-72 p-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Super Admin Dashboard</h1>
        <p class="text-gray-500">Platform overview and system management</p>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 text-sm">Total Companies</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-2"><?php echo $stats['total_companies']; ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-building-line text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 text-sm">Total Users</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-2"><?php echo $stats['total_users']; ?></h3>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="ri-user-line text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 text-sm">Active Trucks</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-2"><?php echo $stats['total_trucks']; ?></h3>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="ri-truck-line text-orange-600 text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 text-sm">Active Drivers</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-2"><?php echo $stats['total_drivers']; ?></h3>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="ri-driver-line text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">System Overview</h3>
            <canvas id="systemChart" height="200"></canvas>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Activity</h3>
            <div class="space-y-3 max-h-80 overflow-y-auto">
                <?php foreach($activities as $activity): ?>
                <div class="flex items-start gap-3 p-2 hover:bg-gray-50 rounded">
                    <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                        <i class="ri-history-line text-gray-500"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-800"><?php echo htmlspecialchars($activity['action']); ?></p>
                        <p class="text-xs text-gray-400"><?php echo date('Y-m-d H:i', strtotime($activity['created_at'])); ?> by <?php echo htmlspecialchars($activity['username'] ?? 'System'); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<script>
const ctx = document.getElementById('systemChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Companies', 'Users', 'Trucks', 'Drivers'],
        datasets: [{
            label: 'Count',
            data: [<?php echo $stats['total_companies']; ?>, <?php echo $stats['total_users']; ?>, <?php echo $stats['total_trucks']; ?>, <?php echo $stats['total_drivers']; ?>],
            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'],
            borderRadius: 8
        }]
    },
    options: { responsive: true, maintainAspectRatio: true }
});
</script>
</body>
</html>