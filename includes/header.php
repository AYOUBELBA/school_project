<?php
$role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';
?>
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
                    'tracking.php' => 'Live Tracking',
                    'maintenance.php' => 'Maintenance & Incidents',
                    'fuel.php' => 'Fuel Tracking',
                    'reports.php' => 'Reports & Analytics',
                    'users.php' => 'User Management',
                    'company_settings.php' => 'Company Settings'
                ];
                $current = basename($_SERVER['PHP_SELF']);
                echo $page_titles[$current] ?? 'Fleet Management';
                ?>
            </h2>
            <p class="text-sm text-gray-500">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!</p>
        </div>
        <div class="flex items-center space-x-4">
            <!-- Admin Quick Actions Dropdown (for super_admin and owner) -->
            <?php if($role == 'super_admin' || $role == 'owner'): ?>
            <div class="relative">
                <button onclick="toggleAdminMenu()" class="flex items-center gap-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                    <i class="ri-shield-star-line text-gray-600"></i>
                    <span class="text-sm font-medium text-gray-700">Admin</span>
                    <i class="ri-arrow-down-s-line text-gray-600"></i>
                </button>
                <div id="adminMenu" class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 hidden z-50">
                    <div class="p-2">
                        <div class="text-xs text-gray-500 px-3 py-2 border-b">Admin Quick Access</div>
                        <?php if($role == 'super_admin'): ?>
                        <a href="super_admin/dashboard.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <i class="ri-dashboard-line text-purple-600"></i>
                            <span>Super Dashboard</span>
                        </a>
                        <a href="super_admin/companies.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <i class="ri-building-line text-blue-600"></i>
                            <span>All Companies</span>
                        </a>
                        <a href="super_admin/users.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <i class="ri-user-settings-line text-green-600"></i>
                            <span>System Users</span>
                        </a>
                        <a href="super_admin/activity_logs.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <i class="ri-history-line text-orange-600"></i>
                            <span>Activity Logs</span>
                        </a>
                        <div class="border-t my-1"></div>
                        <?php endif; ?>
                        <a href="users.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <i class="ri-user-settings-line text-green-600"></i>
                            <span>Team Members</span>
                        </a>
                        <a href="company_settings.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <i class="ri-settings-4-line text-purple-600"></i>
                            <span>Company Settings</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Notifications -->
            <div class="relative">
                <button onclick="toggleNotifications()" class="relative">
                    <i class="ri-notification-3-line text-gray-600 text-xl"></i>
                    <span id="notificationBadge" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center hidden">0</span>
                </button>
                <div id="notificationsDropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 hidden z-50">
                    <div class="p-3 border-b border-gray-200">Notifications</div>
                    <div id="notificationsList" class="max-h-96 overflow-y-auto"></div>
                </div>
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
                        <a href="profile.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg">
                            <i class="ri-user-line"></i>
                            <span>My Profile</span>
                        </a>
                        <div class="border-t my-1"></div>
                        <a href="../config/logout.php" class="flex items-center gap-3 px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg">
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

function toggleNotifications() {
    const dropdown = document.getElementById('notificationsDropdown');
    dropdown.classList.toggle('hidden');
    loadNotifications();
}

function loadNotifications() {
    fetch('../api/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                const badge = document.getElementById('notificationBadge');
                if(data.unread_count > 0) {
                    badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
                const list = document.getElementById('notificationsList');
                if(data.notifications.length === 0) {
                    list.innerHTML = '<div class="p-4 text-center text-gray-500">No notifications</div>';
                } else {
                    list.innerHTML = data.notifications.map(n => `
                        <div class="p-3 hover:bg-gray-50 border-b cursor-pointer">
                            <p class="text-sm font-medium">${n.title}</p>
                            <p class="text-xs text-gray-500">${n.message.substring(0, 80)}</p>
                        </div>
                    `).join('');
                }
            }
        });
}

document.addEventListener('click', function(event) {
    if (!event.target.closest('[onclick="toggleAdminMenu()"]')) {
        document.getElementById('adminMenu')?.classList.add('hidden');
    }
    if (!event.target.closest('[onclick="toggleUserMenu()"]')) {
        document.getElementById('userMenu')?.classList.add('hidden');
    }
    if (!event.target.closest('[onclick="toggleNotifications()"]')) {
        document.getElementById('notificationsDropdown')?.classList.add('hidden');
    }
});

loadNotifications();
setInterval(loadNotifications, 30000);
</script>