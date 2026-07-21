<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-72 bg-gray-900 text-white fixed h-full overflow-y-auto">
    <div class="p-6 border-b border-gray-800">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                <i class="ri-truck-line text-white text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold">FleetManager</h1>
                <p class="text-xs text-gray-400">Super Admin</p>
            </div>
        </div>
    </div>
    
    <nav class="p-4 space-y-1">
        <a href="dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition <?php echo $current_page == 'dashboard.php' ? 'bg-gray-800' : ''; ?>">
            <i class="ri-dashboard-line text-xl"></i>
            <span>Super Dashboard</span>
        </a>
        <a href="companies.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition <?php echo $current_page == 'companies.php' ? 'bg-gray-800' : ''; ?>">
            <i class="ri-building-line text-xl"></i>
            <span>All Companies</span>
        </a>
        <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition <?php echo $current_page == 'users.php' ? 'bg-gray-800' : ''; ?>">
            <i class="ri-user-settings-line text-xl"></i>
            <span>System Users</span>
        </a>
        <a href="activity_logs.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition <?php echo $current_page == 'activity_logs.php' ? 'bg-gray-800' : ''; ?>">
            <i class="ri-history-line text-xl"></i>
            <span>Activity Logs</span>
        </a>

        <a href="system_settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition <?php echo $current_page == 'system_settings.php' ? 'bg-gray-800' : ''; ?>">
            <i class="ri-settings-4-line text-xl"></i>
            <span>System Settings</span>
        </a>

        <div class="border-t border-gray-800 my-4"></div>
        <a href="../dashboard.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
            <i class="ri-arrow-left-line text-xl"></i>
            <span>Back to Main</span>
        </a>
        <a href="../../config/logout.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition mt-4 text-red-400">
            <i class="ri-logout-box-r-line text-xl"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>