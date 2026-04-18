<header class="bg-white shadow-sm sticky top-0 z-20">
    <div class="px-8 py-4 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">
                <?php
                $page_titles = [
                    'dashboard.php' => 'Dashboard',
                    'trucks.php' => 'Trucks Management',
                    'drivers.php' => 'Drivers Management',
                    'trips.php' => 'Trips & Assignments',
                    'shipments.php' => 'Shipments Management',
                    'maintenance.php' => 'Maintenance & Incidents',
                    'fuel.php' => 'Fuel Tracking',
                    'routes.php' => 'Routes Management',
                    'reports.php' => 'Reports & Analytics',
                    'users.php' => 'User Management',
                    'activity_logs.php' => 'Activity Logs',
                    'system_settings.php' => 'System Settings'
                ];
                $current = basename($_SERVER['PHP_SELF']);
                echo $page_titles[$current] ?? 'Fleet Management';
                ?>
            </h2>
            <p class="text-sm text-gray-500">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</p>
        </div>
        <div class="flex items-center space-x-4">
            <!-- Admin Quick Actions Dropdown -->
            <?php 
            $role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
            if($role == 'admin'): 
            ?>
            <div class="relative">
                <button onclick="toggleAdminMenu()" class="flex items-center gap-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                    <i class="ri-shield-star-line text-gray-600"></i>
                    <span class="text-sm font-medium text-gray-700">Admin</span>
                    <i class="ri-arrow-down-s-line text-gray-600"></i>
                </button>
                <div id="adminMenu" class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 hidden z-50">
                    <div class="p-2">
                        <div class="text-xs text-gray-500 px-3 py-2 border-b">Admin Quick Access</div>
                        <a href="activity_logs.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <i class="ri-history-line text-blue-600"></i>
                            <span>Activity Logs</span>
                        </a>
                        <a href="system_settings.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <i class="ri-settings-4-line text-purple-600"></i>
                            <span>System Settings</span>
                        </a>
                        <a href="users.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <i class="ri-user-settings-line text-green-600"></i>
                            <span>User Management</span>
                        </a>
                        <div class="border-t my-1"></div>
                        <a href="backup.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <i class="ri-database-2-line text-orange-600"></i>
                            <span>Database Backup</span>
                        </a>
                        <a href="cache.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <i class="ri-flashlight-line text-yellow-600"></i>
                            <span>Clear Cache</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Search -->
            <div class="relative">
                <i class="ri-search-line text-gray-400 text-xl cursor-pointer"></i>
            </div>
            
            <!-- Notifications -->
            <div class="relative">
                <i class="ri-notification-3-line text-gray-600 text-xl cursor-pointer"></i>
                <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">3</span>
            </div>
            
            <!-- User Menu -->
            <div class="relative">
                <button onclick="toggleUserMenu()" class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                        <i class="ri-user-line text-gray-600"></i>
                    </div>
                    <i class="ri-arrow-down-s-line text-gray-400 text-sm"></i>
                </button>
                <div id="userMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 hidden z-50">
                    <div class="p-2">
                        <a href="profile.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <i class="ri-user-line"></i>
                            <span>My Profile</span>
                        </a>
                        <a href="settings.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <i class="ri-settings-3-line"></i>
                            <span>Settings</span>
                        </a>
                        <div class="border-t my-1"></div>
                        <a href="../config/logout.php" class="flex items-center gap-3 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition">
                            <i class="ri-logout-box-r-line"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
function toggleAdminMenu() {
    const menu = document.getElementById('adminMenu');
    menu.classList.toggle('hidden');
}

function toggleUserMenu() {
    const menu = document.getElementById('userMenu');
    menu.classList.toggle('hidden');
}

// Close menus when clicking outside
document.addEventListener('click', function(event) {
    const adminMenu = document.getElementById('adminMenu');
    const adminButton = event.target.closest('[onclick="toggleAdminMenu()"]');
    if (!adminButton && adminMenu && !adminMenu.contains(event.target)) {
        adminMenu.classList.add('hidden');
    }
    
    const userMenu = document.getElementById('userMenu');
    const userButton = event.target.closest('[onclick="toggleUserMenu()"]');
    if (!userButton && userMenu && !userMenu.contains(event.target)) {
        userMenu.classList.add('hidden');
    }
});
</script>