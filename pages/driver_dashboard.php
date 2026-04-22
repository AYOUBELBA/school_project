<?php
require_once('../config/session.php');
require_once('../config/database.php');

$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../signin.php");
    exit();
}

// Check if user is a driver (not admin/manager)
$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
if($role !== 'driver') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Driver';

// Get driver information
$driverSql = "SELECT d.*, u.email 
              FROM drivers d 
              JOIN users u ON d.user_id = u.id 
              WHERE u.id = :user_id";
$driverStmt = $db->prepare($driverSql);
$driverStmt->bindParam(':user_id', $user_id);
$driverStmt->execute();
$driver = $driverStmt->fetch(PDO::FETCH_ASSOC);

// Get current/upcoming trips for this driver
$tripsSql = "SELECT t.*, tr.plate_number as truck_plate, tr.model as truck_model,
                    r.name as route_name, r.start_point, r.end_point
             FROM trips t
             LEFT JOIN trucks tr ON t.truck_id = tr.id
             LEFT JOIN routes r ON t.route_id = r.id
             WHERE t.driver_id = :driver_id 
             AND t.status IN ('planned', 'ongoing')
             ORDER BY t.start_time ASC
             LIMIT 5";
$tripsStmt = $db->prepare($tripsSql);
$tripsStmt->bindParam(':driver_id', $driver['id']);
$tripsStmt->execute();
$upcomingTrips = $tripsStmt->fetchAll(PDO::FETCH_ASSOC);

// Get completed trips count
$completedSql = "SELECT COUNT(*) as count FROM trips WHERE driver_id = :driver_id AND status = 'completed'";
$completedStmt = $db->prepare($completedSql);
$completedStmt->bindParam(':driver_id', $driver['id']);
$completedStmt->execute();
$completedCount = $completedStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get notifications for driver
$notifSql = "SELECT * FROM notifications WHERE user_id = :user_id AND is_read = false ORDER BY created_at DESC LIMIT 5";
$notifStmt = $db->prepare($notifSql);
$notifStmt->bindParam(':user_id', $user_id);
$notifStmt->execute();
$notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);
$notifCount = count($notifications);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .status-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .status-ongoing { background: #dbeafe; color: #1e40af; }
        .status-planned { background: #e0e7ff; color: #3730a3; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .card-hover { transition: transform 0.2s, box-shadow 0.2s; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15); }
        .qr-code { background: white; padding: 1rem; border-radius: 1rem; display: inline-block; }
    </style>
</head>
<body class="bg-gray-50">


<!-- Simple Navbar for Driver -->
<nav class="bg-white shadow-sm sticky top-0 z-50">
    <div class="container mx-auto px-4 py-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="ri-truck-line text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">FleetDriver</h1>
                    <p class="text-xs text-gray-500">Driver Portal</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative">
                    <i class="ri-notification-3-line text-gray-600 text-xl cursor-pointer"></i>
                    <?php if($notifCount > 0): ?>
                    <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"><?php echo $notifCount; ?></span>
                    <?php endif; ?>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                        <button onclick=f1()>
                            <i class="ri-user-line text-gray-600"></i>
                        </button>
                    </div>
                    <span class="text-sm font-medium text-gray-700"><?php echo htmlspecialchars($username); ?></span>
                </div>
                <a href="../config/logout.php" class="text-gray-400 hover:text-red-500">
                    <i class="ri-logout-box-r-line text-xl"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container mx-auto px-4 py-8">
    <!-- Welcome Section -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-6 mb-8 text-white">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold mb-2">Welcome back, <?php echo htmlspecialchars($driver['full_name'] ?? $username); ?>!</h1>
                <p class="text-blue-100">Ready for your next delivery? Check your assigned trips below.</p>
            </div>
            <div class="hidden md:block">
                <i class="ri-truck-line text-6xl text-blue-300"></i>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-xl p-6 shadow-sm card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Upcoming Trips</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-2"><?php echo count($upcomingTrips); ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-map-pin-line text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Completed Trips</p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-2"><?php echo $completedCount; ?></h3>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="ri-check-double-line text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl p-6 shadow-sm card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">License Status</p>
                    <?php
                    $expiry = strtotime($driver['license_expiry']);
                    $now = time();
                    $days_left = round(($expiry - $now) / (60 * 60 * 24));
                    $status_color = $days_left > 30 ? 'text-green-600' : ($days_left > 7 ? 'text-orange-600' : 'text-red-600');
                    ?>
                    <h3 class="text-2xl font-bold <?php echo $status_color; ?> mt-2"><?php echo $days_left; ?> days</h3>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="ri-id-card-line text-orange-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile App Download Section -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-700 rounded-2xl p-6 mb-8 text-white">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-3">
                    <i class="ri-smartphone-line text-2xl"></i>
                    <h2 class="text-xl font-bold">Get the Mobile App</h2>
                </div>
                <p class="text-purple-100 mb-4">Track your trips, update status, and get real-time notifications on the go.</p>
                <div class="flex flex-wrap gap-3">
                    <a href="#" class="flex items-center gap-2 bg-black/20 backdrop-blur-sm px-4 py-2 rounded-lg hover:bg-black/30 transition">
                        <i class="ri-apple-line text-2xl"></i>
                        <div>
                            <p class="text-xs">Download on</p>
                            <p class="text-sm font-semibold">App Store</p>
                        </div>
                    </a>
                    <a href="#" class="flex items-center gap-2 bg-black/20 backdrop-blur-sm px-4 py-2 rounded-lg hover:bg-black/30 transition">
                        <i class="ri-android-line text-2xl"></i>
                        <div>
                            <p class="text-xs">Get it on</p>
                            <p class="text-sm font-semibold">Google Play</p>
                        </div>
                    </a>
                </div>
            </div>
            <div class="text-center">
                <div class="qr-code bg-white p-3 rounded-xl inline-block">
                    <svg width="120" height="120" viewBox="0 0 100 100" class="mx-auto">
                        <rect width="100" height="100" fill="white"/>
                        <path d="M20,20 h15 v15 h-15 z M40,20 h15 v15 h-15 z M60,20 h15 v15 h-15 z M20,40 h15 v15 h-15 z M40,40 h15 v15 h-15 z M60,40 h15 v15 h-15 z M20,60 h15 v15 h-15 z M40,60 h15 v15 h-15 z M60,60 h15 v15 h-15 z" fill="black"/>
                        <path d="M80,20 h5 v5 h-5 z M80,40 h5 v5 h-5 z M80,60 h5 v5 h-5 z M20,80 h5 v5 h-5 z M40,80 h5 v5 h-5 z M60,80 h5 v5 h-5 z M80,80 h15 v15 h-15 z" fill="black"/>
                    </svg>
                </div>
                <p class="text-xs text-purple-200 mt-2">Scan to download</p>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Trips -->
    <div class="bg-white rounded-xl shadow-sm mb-8">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Your Upcoming Trips</h3>
            <p class="text-sm text-gray-500">Trips assigned to you</p>
        </div>
        <div class="divide-y divide-gray-200">
            <?php if(empty($upcomingTrips)): ?>
            <div class="p-8 text-center text-gray-500">
                <i class="ri-roadster-line text-5xl mb-3 block"></i>
                <p>No upcoming trips assigned</p>
                <p class="text-sm mt-1">Check back later for new assignments</p>
            </div>
            <?php else: ?>
                <?php foreach($upcomingTrips as $trip): ?>
                <div class="p-6 hover:bg-gray-50 transition">
                    <div class="flex flex-col md:flex-row justify-between items-start gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="status-badge status-<?php echo $trip['status']; ?>">
                                    <?php echo ucfirst($trip['status']); ?>
                                </span>
                                <span class="text-xs text-gray-500">Trip #<?php echo htmlspecialchars($trip['trip_number']); ?></span>
                            </div>
                            <h4 class="font-semibold text-gray-800 mb-2">
                                <?php echo htmlspecialchars($trip['route_name'] ?? $trip['start_location'] . ' → ' . $trip['end_location']); ?>
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm text-gray-600">
                                <div class="flex items-center gap-2">
                                    <i class="ri-truck-line text-gray-400"></i>
                                    <span>Truck: <?php echo htmlspecialchars($trip['truck_plate'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="ri-calendar-line text-gray-400"></i>
                                    <span>Start: <?php echo date('M d, Y H:i', strtotime($trip['start_time'])); ?></span>
                                </div>
                                <?php if($trip['client_name']): ?>
                                <div class="flex items-center gap-2">
                                    <i class="ri-user-line text-gray-400"></i>
                                    <span>Client: <?php echo htmlspecialchars($trip['client_name']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <?php if($trip['status'] == 'planned'): ?>
                            <button class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition">
                                Start Trip
                            </button>
                            <?php elseif($trip['status'] == 'ongoing'): ?>
                            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition">
                                Update Status
                            </button>
                            <?php endif; ?>
                            <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Actions & Support -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-2 gap-3">
                <a href="#" class="flex flex-col items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                    <i class="ri-phone-line text-2xl text-blue-600 mb-2"></i>
                    <span class="text-sm font-medium">Contact Dispatch</span>
                </a>
                <a href="#" class="flex flex-col items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                    <i class="ri-tools-line text-2xl text-orange-600 mb-2"></i>
                    <span class="text-sm font-medium">Report Issue</span>
                </a>
                <a href="#" class="flex flex-col items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                    <i class="ri-file-text-line text-2xl text-green-600 mb-2"></i>
                    <span class="text-sm font-medium">View Documents</span>
                </a>
                <a href="#" class="flex flex-col items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                    <i class="ri-question-line text-2xl text-purple-600 mb-2"></i>
                    <span class="text-sm font-medium">Help Center</span>
                </a>
            </div>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Recent Notifications</h3>
                <a href="#" class="text-xs text-blue-600 hover:underline">View all</a>
            </div>
            <div class="space-y-3">
                <?php if(empty($notifications)): ?>
                <p class="text-sm text-gray-500 text-center py-4">No new notifications</p>
                <?php else: ?>
                    <?php foreach($notifications as $notif): ?>
                    <div class="flex items-start gap-3 p-2 hover:bg-gray-50 rounded-lg transition">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="ri-information-line text-blue-600 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-800"><?php echo htmlspecialchars($notif['message']); ?></p>
                            <p class="text-xs text-gray-400 mt-1"><?php echo date('M d, H:i', strtotime($notif['created_at'])); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Driver Info Footer -->
    <div class="mt-8 bg-white rounded-xl shadow-sm p-6">
        <div class="flex flex-col md:flex-row justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500">Your Driver ID</p>
                <p class="font-mono text-sm">#<?php echo $driver['id']; ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">License Number</p>
                <p class="font-mono text-sm"><?php echo htmlspecialchars($driver['license_number']); ?></p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Contact Support</p>
                <p class="text-sm">dispatch@fleet.com | +1 234 567 890</p>
            </div>
        </div>
    </div>
</div>

<!-- Simple Footer -->
<footer class="bg-white border-t border-gray-200 mt-8 py-4 text-center text-sm text-gray-500">
    <div class="container mx-auto px-4">
        © <?php echo date('Y'); ?> Fleet Management System. All rights reserved.
    </div>
</footer>

<script>
// Simple notification click handler
document.querySelector('.ri-notification-3-line')?.addEventListener('click', function() {
    alert('You have <?php echo $notifCount; ?> unread notifications');
});
function f1(){
    window.location.href = "../pages/driver_profile.php";
}
</script>
</body>
</html>