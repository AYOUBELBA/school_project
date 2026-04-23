<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? 'driver';
?>
<aside class="sidebar w-72 bg-white shadow-lg fixed h-full overflow-y-auto z-10">
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                <i class="ri-truck-line text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-800">FleetDriver</h1>
                <p class="text-xs text-gray-500">Driver Portal</p>
            </div>
        </div>
    </div>
    
    <nav class="p-4 space-y-1">
        <a href="driver_dashboard.php" class="sidebar-item <?php echo $current_page == 'driver_dashboard.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="ri-dashboard-line text-xl"></i>
            <span class="font-medium">Dashboard</span>
        </a>
        <a href="my_trips.php" class="sidebar-item <?php echo $current_page == 'my_trips.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="ri-map-pin-line text-xl"></i>
            <span class="font-medium">My Trips</span>
        </a>
        <a href="profile.php" class="sidebar-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="ri-user-line text-xl"></i>
            <span class="font-medium">My Profile</span>
        </a>
    </nav>
    
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 bg-white">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                <i class="ri-user-line text-gray-600"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Driver'); ?></p>
                <p class="text-xs text-gray-500 capitalize"><?php echo $role; ?></p>
            </div>
            <a href="../config/config/logout.php" class="text-gray-400 hover:text-red-500">
                <i class="ri-logout-box-r-line text-xl"></i>
            </a>
        </div>
    </div>
</aside>

<style>
.sidebar-item { transition: all 0.2s ease; border-left: 3px solid transparent; }
.sidebar-item:hover, .sidebar-item.active { background: rgba(59, 130, 246, 0.1); border-left-color: #3b82f6; }
.sidebar-item.active { background: linear-gradient(90deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0) 100%); }
</style>