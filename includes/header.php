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
                    'reports.php' => 'Reports & Analytics'
                ];
                $current = basename($_SERVER['PHP_SELF']);
                echo $page_titles[$current] ?? 'Fleet Management';
                ?>
            </h2>
            <p class="text-sm text-gray-500">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</p>
        </div>
        <div class="flex items-center space-x-4">
            <div class="relative">
                <i class="ri-search-line text-gray-400 text-xl cursor-pointer"></i>
            </div>
            <div class="relative">
                <i class="ri-notification-3-line text-gray-600 text-xl cursor-pointer"></i>
                <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">3</span>
            </div>
        </div>
    </div>
</header>