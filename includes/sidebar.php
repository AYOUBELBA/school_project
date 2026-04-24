<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'guest';
$username = $_SESSION['username'] ?? 'User';
?>

<aside class="sidebar w-72 bg-white shadow-lg fixed h-full overflow-hidden z-10 flex flex-col">
    <!-- Logo Section -->
    <div class="p-6 border-b border-gray-200 flex-shrink-0">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                <i class="ri-truck-line text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold text-gray-800">FleetManager</h1>
                <p class="text-xs text-gray-500 capitalize"><?php echo $role; ?> Portal</p>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4">
        <div class="px-4 space-y-1">
            <!-- Dashboard - Everyone sees -->
            <a href="dashboard.php" class="sidebar-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                <i class="ri-dashboard-line text-xl"></i>
                <span class="font-medium">Dashboard</span>
            </a>
            
            <!-- Trucks - Manager and Owner -->
            <?php if($role !== 'driver'): ?>
            <a href="trucks.php" class="sidebar-item <?php echo $current_page == 'trucks.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                <i class="ri-truck-line text-xl"></i>
                <span class="font-medium">Trucks Management</span>
            </a>
            <?php endif; ?>
            
            <!-- Drivers - Manager and Owner -->
            <?php if($role !== 'driver'): ?>
            <a href="drivers.php" class="sidebar-item <?php echo $current_page == 'drivers.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                <i class="ri-driver-line text-xl"></i>
                <span class="font-medium">Drivers Management</span>
            </a>
            <?php endif; ?>
            
            <!-- Trips - Manager and Owner -->
            <?php if($role !== 'driver'): ?>
            <a href="trips.php" class="sidebar-item <?php echo $current_page == 'trips.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                <i class="ri-map-pin-line text-xl"></i>
                <span class="font-medium">Trips & Assignments</span>
            </a>
            <?php endif; ?>
            
            <!-- Routes - Manager and Owner -->
            <?php if($role !== 'driver'): ?>
            <a href="routes.php" class="sidebar-item <?php echo $current_page == 'routes.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                <i class="ri-route-line text-xl"></i>
                <span class="font-medium">Routes & Scheduling</span>
            </a>
            <?php endif; ?>
            
            <!-- Shipments - Manager and Owner -->
            <?php if($role !== 'driver'): ?>
            <a href="shipments.php" class="sidebar-item <?php echo $current_page == 'shipments.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                <i class="ri-box-3-line text-xl"></i>
                <span class="font-medium">Shipments / Cargo</span>
            </a>
            <?php endif; ?>
            
            <!-- Maintenance - Manager and Owner -->
            <?php if($role !== 'driver'): ?>
            <a href="maintenance.php" class="sidebar-item <?php echo $current_page == 'maintenance.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                <i class="ri-tools-line text-xl"></i>
                <span class="font-medium">Maintenance & Incidents</span>
            </a>
            <?php endif; ?>
            
            <!-- Fuel Tracking - Manager and Owner -->
            <?php if($role !== 'driver'): ?>
            <a href="fuel.php" class="sidebar-item <?php echo $current_page == 'fuel.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                <i class="ri-gas-station-line text-xl"></i>
                <span class="font-medium">Fuel Tracking</span>
            </a>
            <?php endif; ?>
            
            <!-- Reports - Manager and Owner -->
            <?php if($role !== 'driver'): ?>
            <a href="reports.php" class="sidebar-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                <i class="ri-bar-chart-2-line text-xl"></i>
                <span class="font-medium">Reports & Analytics</span>
            </a>
            <?php endif; ?>
            
            <!-- Live Tracking - Everyone -->
            <a href="tracking.php" class="sidebar-item <?php echo $current_page == 'tracking.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                <i class="ri-map-pin-line text-xl"></i>
                <span class="font-medium">Live Tracking</span>
            </a>
            
            <!-- Team Members - Only Owner (not manager) -->
            <?php if($role === 'owner'): ?>
            <a href="team_members.php" class="sidebar-item <?php echo $current_page == 'team_members.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                <i class="ri-team-line text-xl"></i>
                <span class="font-medium">Team Members</span>
            </a>
            <?php endif; ?>

            <!-- USER MANAGEMENT-->
            <?php if($role === 'super_admin'): ?>
            <a href="users.php" class="sidebar-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                <i class="ri-team-line text-xl"></i>
                <span class="font-medium">Users Manager</span>
            </a>
            <?php endif; ?>
            
            <!-- Company Settings - Only Owner -->
            <?php if($role === 'owner'): ?>
            <a href="company_settings.php" class="sidebar-item <?php echo $current_page == 'company_settings.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                <i class="ri-settings-3-line text-xl"></i>
                <span class="font-medium">Company Settings</span>
            </a>
            <?php endif; ?>
            
            <!-- Profile - Everyone -->
            <a href="profile.php" class="sidebar-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?> flex items-center space-x-3 px-4 py-3 rounded-lg text-gray-700">
                <i class="ri-user-line text-xl"></i>
                <span class="font-medium">My Profile</span>
            </a>
        </div>
    </nav>
    
    <!-- User Profile -->
    <div class="flex-shrink-0 p-4 border-t border-gray-200 bg-white">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                <i class="ri-user-line text-gray-600"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate"><?php echo htmlspecialchars($username); ?></p>
                <p class="text-xs text-gray-500 capitalize"><?php echo $role; ?></p>
            </div>
            <a href="../config/logout.php" class="text-gray-400 hover:text-red-500">
                <i class="ri-logout-box-r-line text-xl"></i>
            </a>
        </div>
    </div>
</aside>

<style>
.sidebar-item { transition: all 0.2s ease; border-left: 3px solid transparent; }
.sidebar-item:hover, .sidebar-item.active { background: rgba(55, 88, 249, 0.1); border-left-color: #3758f9; }
.sidebar-item.active { background: linear-gradient(90deg, rgba(55, 88, 249, 0.15) 0%, rgba(55, 88, 249, 0) 100%); }
.sidebar nav::-webkit-scrollbar { width: 4px; }
.sidebar nav::-webkit-scrollbar-track { background: #f1f1f1; }
.sidebar nav::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
</style>