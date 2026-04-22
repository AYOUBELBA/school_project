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
                    'tracking.php' => 'Live Tracking'
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
                        <a href="users.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <i class="ri-user-settings-line text-green-600"></i>
                            <span>User Management</span>
                        </a>
                        <div class="border-t my-1"></div>
                        <a href="clear_cache.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <i class="ri-flashlight-line text-yellow-600"></i>
                            <span>Clear Cache</span>
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
                
                <!-- Notifications Dropdown -->
                <div id="notificationsDropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 hidden z-50">
                    <div class="p-3 border-b border-gray-200 flex justify-between items-center">
                        <h4 class="font-semibold text-gray-800">Notifications</h4>
                        <button onclick="markAllAsRead()" class="text-xs text-blue-600 hover:text-blue-800">Mark all as read</button>
                    </div>
                    <div id="notificationsList" class="max-h-96 overflow-y-auto">
                        <div class="p-4 text-center text-gray-500">Loading...</div>
                    </div>
                    <div class="p-2 border-t border-gray-200">
                        <a href="notifications.php" class="block text-center text-sm text-blue-600 hover:text-blue-800 py-1">View all notifications</a>
                    </div>
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
                        <a href="profile.php" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
                            <i class="ri-user-line"></i>
                            <span>My Profile</span>
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
// Load notifications on page load
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    // Refresh notifications every 30 seconds
    setInterval(loadNotifications, 30000);
});

function loadNotifications() {
    fetch('../api/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Update badge
                const badge = document.getElementById('notificationBadge');
                if(data.unread_count > 0) {
                    badge.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
                
                // Update dropdown list
                const list = document.getElementById('notificationsList');
                if(data.notifications.length === 0) {
                    list.innerHTML = '<div class="p-4 text-center text-gray-500">No notifications</div>';
                } else {
                    list.innerHTML = data.notifications.map(notif => `
                        <div class="notification-item p-3 hover:bg-gray-50 border-b border-gray-100 cursor-pointer ${!notif.is_read ? 'bg-blue-50' : ''}" onclick="markAsRead(${notif.id})">
                            <div class="flex items-start gap-2">
                                <div class="flex-shrink-0">
                                    ${notif.type === 'alert' ? '<i class="ri-alert-line text-red-500"></i>' : 
                                      notif.type === 'warning' ? '<i class="ri-error-warning-line text-yellow-500"></i>' :
                                      '<i class="ri-information-line text-blue-500"></i>'}
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-800">${notif.title}</p>
                                    <p class="text-xs text-gray-500 mt-1">${notif.message}</p>
                                    <p class="text-xs text-gray-400 mt-1">${new Date(notif.created_at).toLocaleString()}</p>
                                </div>
                                ${!notif.is_read ? '<div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>' : ''}
                            </div>
                        </div>
                    `).join('');
                }
            }
        })
        .catch(error => console.error('Error loading notifications:', error));
}

function markAsRead(notificationId) {
    fetch('../api/mark_notification_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            loadNotifications();
        }
    });
}

function markAllAsRead() {
    // You can implement mark all as read endpoint similarly
    alert('Mark all as read feature coming soon');
}

function toggleNotifications() {
    const dropdown = document.getElementById('notificationsDropdown');
    dropdown.classList.toggle('hidden');
}

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
    
    const notifDropdown = document.getElementById('notificationsDropdown');
    const notifButton = event.target.closest('[onclick="toggleNotifications()"]');
    if (!notifButton && notifDropdown && !notifDropdown.contains(event.target)) {
        notifDropdown.classList.add('hidden');
    }
});
</script>