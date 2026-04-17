<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['user_role'] ?? 'driver';
?>
<aside class="sidebar w-72 bg-white shadow-lg fixed h-full overflow-y-auto z-10">
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                <i class="ri-truck-line text-white text-xl"></i>
            </div>
            <div>
                <?php if($role == 'admin'): ?>
                    <h1 class="text-xl font-bold text-gray-800"><?php echo "Admin Panel"; ?></h1>
                    <p class="text-xs text-gray-500">Truck Tracking System</p>

                <?php elseif($role == 'manager'): ?>
                    <h1 class="text-xl font-bold text-gray-800"><?php echo "FleetManager"; ?></h1>
                    <p class="text-xs text-gray-500">Truck Tracking System</p>
                <?php endif ?>
            </div>
        </div>
    </div>
    
    <nav class="p-4 space-y-1">
        <a href="dashboard.php" class="sidebar-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="ri-dashboard-line text-xl"></i>
            <span class="font-medium">Dashboard</span>
        </a>
        <a href="trucks.php" class="sidebar-item <?php echo $current_page == 'trucks.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="ri-truck-line text-xl"></i>
            <span class="font-medium">Trucks Management</span>
        </a>
        <a href="drivers.php" class="sidebar-item <?php echo $current_page == 'drivers.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="ri-driver-line text-xl"></i>
            <span class="font-medium">Drivers Management</span>
        </a>
        <a href="trips.php" class="sidebar-item <?php echo $current_page == 'trips.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="ri-map-pin-line text-xl"></i>
            <span class="font-medium">Trips & Assignments</span>
        </a>
        <a href="routes.php" class="sidebar-item <?php echo $current_page == 'routes.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="ri-route-line text-xl"></i>
            <span class="font-medium">Routes & Scheduling</span>
        </a>
        <a href="shipments.php" class="sidebar-item <?php echo $current_page == 'shipments.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="ri-box-3-line text-xl"></i>
            <span class="font-medium">Shipments / Cargo</span>
        </a>
        <a href="maintenance.php" class="sidebar-item <?php echo $current_page == 'maintenance.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="ri-tools-line text-xl"></i>
            <span class="font-medium">Maintenance & Incidents</span>
        </a>
        <a href="fuel.php" class="sidebar-item <?php echo $current_page == 'fuel.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="ri-gas-station-line text-xl"></i>
            <span class="font-medium">Fuel Tracking</span>
        </a>
        <a href="reports.php" class="sidebar-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="ri-bar-chart-2-line text-xl"></i>
            <span class="font-medium">Reports & Analytics</span>
        </a>
        <?php if($role == 'admin'): ?>
        <a href="users.php" class="sidebar-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="ri-user-settings-line text-xl"></i>
            <span class="font-medium">Users Management</span>
        </a>
        <?php endif; ?>
        <a href="notifications.php" class="sidebar-item <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="ri-notification-3-line text-xl"></i>
            <span class="font-medium">Notifications</span>
            <span class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">3</span>
        </a>
        <a href="settings.php" class="sidebar-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
            <i class="ri-settings-3-line text-xl"></i>
            <span class="font-medium">Settings</span>
        </a>
    </nav>
    
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 bg-white">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                <i class="ri-user-line text-gray-600"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></p>
                <p class="text-xs text-gray-500 capitalize"><?php echo $role; ?></p>
            </div>
            <a href="../config/logout.php" class="text-gray-400 hover:text-red-500">
                <i class="ri-logout-box-r-line text-xl"></i>
            </a>
        </div>
    </div>
</aside>