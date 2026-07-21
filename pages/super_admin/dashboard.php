<?php
/**
 * @var PDO $db
 */
require_once '../../includes/check_auth.php';

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
             (SELECT COUNT(*) FROM trips WHERE status = 'completed') as completed_trips,
             (SELECT COUNT(*) FROM users WHERE created_at >= DATE_TRUNC('month', CURRENT_DATE)) as new_users_month,
             (SELECT COUNT(*) FROM companies WHERE created_at >= DATE_TRUNC('month', CURRENT_DATE)) as new_companies_month";
$statsStmt = $db->prepare($statsSql);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get subscription distribution
$planSql = "SELECT subscription_plan, COUNT(*) as count FROM companies GROUP BY subscription_plan";
$planStmt = $db->prepare($planSql);
$planStmt->execute();
$plans = $planStmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent activity
$activitySql = "SELECT al.*, u.username, c.name as company_name
                FROM activity_logs al
                LEFT JOIN users u ON al.user_id = u.id
                LEFT JOIN companies c ON u.company_id = c.id
                ORDER BY al.created_at DESC 
                LIMIT 15";
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
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
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
        <div class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 text-sm">Total Companies</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-2"><?php echo $stats['total_companies']; ?></h3>
                    <p class="text-green-600 text-xs mt-2">+<?php echo $stats['new_companies_month']; ?> this month</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-building-line text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 text-sm">Total Users</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-2"><?php echo $stats['total_users']; ?></h3>
                    <p class="text-green-600 text-xs mt-2">+<?php echo $stats['new_users_month']; ?> this month</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="ri-user-line text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition">
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
        <div class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-gray-500 text-sm">Active Trips</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-2"><?php echo $stats['active_trips']; ?></h3>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="ri-map-pin-line text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Subscription Distribution</h3>
            <canvas id="planChart" height="200"></canvas>
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
                        <p class="text-xs text-gray-400">
                            <?php echo htmlspecialchars($activity['username'] ?? 'System'); ?> 
                            <?php echo $activity['company_name'] ? '(' . htmlspecialchars($activity['company_name']) . ')' : ''; ?>
                            • <?php echo date('M d, H:i', strtotime($activity['created_at'])); ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <a href="companies.php" class="bg-blue-50 rounded-xl p-4 hover:bg-blue-100 transition text-center">
            <i class="ri-building-line text-2xl text-blue-600 mb-2 block"></i>
            <span class="text-blue-700 font-medium">Manage Companies</span>
        </a>
        <a href="users.php" class="bg-green-50 rounded-xl p-4 hover:bg-green-100 transition text-center">
            <i class="ri-user-settings-line text-2xl text-green-600 mb-2 block"></i>
            <span class="text-green-700 font-medium">Manage Users</span>
        </a>
        <a href="activity_logs.php" class="bg-purple-50 rounded-xl p-4 hover:bg-purple-100 transition text-center">
            <i class="ri-history-line text-2xl text-purple-600 mb-2 block"></i>
            <span class="text-purple-700 font-medium">Activity Logs</span>
        </a>
        <a href="system_settings.php" class="bg-orange-50 rounded-xl p-4 hover:bg-orange-100 transition text-center">
            <i class="ri-settings-4-line text-2xl text-orange-600 mb-2 block"></i>
            <span class="text-orange-700 font-medium">System Settings</span>
        </a>
    </div>
</main>

<script>
const planCtx = document.getElementById('planChart').getContext('2d');
const planData = {
    labels: [<?php echo "'" . implode("','", array_column($plans, 'subscription_plan')) . "'"; ?>],
    datasets: [{
        data: [<?php echo implode(",", array_column($plans, 'count')); ?>],
        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
        borderWidth: 0
    }]
};
new Chart(planCtx, { type: 'doughnut', data: planData, options: { responsive: true, plugins: { legend: { position: 'bottom' } } } });
</script>
</body>
</html>