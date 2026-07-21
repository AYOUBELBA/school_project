<?php

require_once '../../includes/check_auth.php';

// Only super_admin can access this page
if ($_SESSION['user_role'] !== 'super_admin') {
    header('Location: ../dashboard.php');
    exit();
}

// Load maintenance config
$maintenanceConfigFile = '../../config/maintenance.php';
if (file_exists($maintenanceConfigFile)) {
    $maintenanceConfig = include $maintenanceConfigFile;
} else {
    $maintenanceConfig = [
        'enabled' => false,
        'message' => 'System is currently under maintenance. Please check back later.',
        'allowed_ips' => ['127.0.0.1'],
        'allowed_roles' => ['super_admin', 'owner'],
        'estimated_end' => null
    ];
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // Clear Cache
        if ($_POST['action'] === 'clear_cache') {
            // Clear template cache
            $cacheDirs = ['../../cache/', '../../compiled/', '../../tmp/'];
            $clearedCount = 0;
            
            foreach ($cacheDirs as $dir) {
                if (is_dir($dir)) {
                    $files = glob($dir . '*');
                    foreach ($files as $file) {
                        if (is_file($file) && !str_contains($file, '.htaccess')) {
                            if (unlink($file)) {
                                $clearedCount++;
                            }
                        }
                    }
                }
            }
            
            // Clear OPcache
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            
            // Clear APC cache
            if (function_exists('apc_clear_cache')) {
                apc_clear_cache();
            }
            
            // Clear session cache
            unset($_SESSION['cache']);
            unset($_SESSION['templates']);
            
            $_SESSION['flash_success'] = "All cache cleared successfully! ($clearedCount files removed)";
            header("Location: system_settings.php");
            exit();
        }
        
        // Maintenance Mode
        if ($_POST['action'] === 'maintenance_mode') {
            $mode = $_POST['maintenance_mode'] ?? 'off';
            $message = $_POST['maintenance_message'] ?? 'System is currently under maintenance. Please check back later.';
            $estimated_end = $_POST['estimated_end'] ?? null;
            
            // Update config
            $config = [
                'enabled' => ($mode === 'on'),
                'message' => $message,
                'allowed_ips' => ['127.0.0.1', '::1'],
                'allowed_roles' => ['super_admin', 'owner'],
                'started_at' => ($mode === 'on') ? date('Y-m-d H:i:s') : null,
                'estimated_end' => $estimated_end
            ];
            
            // Save config
            $content = "<?php\nreturn " . var_export($config, true) . ";\n?>";
            if (file_put_contents($maintenanceConfigFile, $content)) {
                $_SESSION['flash_success'] = "Maintenance mode " . ($mode === 'on' ? 'enabled' : 'disabled');
            } else {
                $_SESSION['flash_error'] = "Failed to update maintenance settings. Check file permissions.";
            }
            
            header("Location: system_settings.php");
            exit();
        }
    }
}

// Get flash messages
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Get current maintenance status for display
$maintenance_status = ($maintenanceConfig['enabled'] ?? false) ? 'Enabled' : 'Disabled';
$maintenance_badge_color = ($maintenanceConfig['enabled'] ?? false) ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | Super Admin</title>
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<?php include '../../includes/super_admin_sidebar.php'; ?>

<main class="ml-72 p-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">System Settings</h1>
            <p class="text-gray-500">Manage system-wide configurations and maintenance</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm text-gray-500">Maintenance:</span>
            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $maintenance_badge_color; ?>">
                <?php echo $maintenance_status; ?>
            </span>
        </div>
    </div>
    
    <?php if($flash_success): ?>
    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center gap-2">
        <i class="ri-checkbox-circle-line text-xl"></i>
        <?php echo htmlspecialchars($flash_success); ?>
    </div>
    <?php endif; ?>
    
    <?php if($flash_error): ?>
    <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center gap-2">
        <i class="ri-error-warning-line text-xl"></i>
        <?php echo htmlspecialchars($flash_error); ?>
    </div>
    <?php endif; ?>
    
    <!-- Maintenance Mode Card -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold flex items-center gap-2">
                    <i class="ri-tools-line text-yellow-600"></i>
                    Maintenance Mode
                </h3>
                <p class="text-sm text-gray-500 mt-1">When enabled, all users except admins will see the maintenance page.</p>
            </div>
            <div class="text-sm">
                <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $maintenance_badge_color; ?>">
                    <i class="ri-circle-fill text-xs mr-1"></i>
                    <?php echo $maintenance_status; ?>
                </span>
            </div>
        </div>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="maintenance_mode">
            
            <div class="flex items-center gap-4">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="maintenance_mode" value="on" 
                           <?php echo ($maintenanceConfig['enabled'] ?? false) ? 'checked' : ''; ?>
                           class="sr-only peer" onchange="toggleMaintenanceFields(this.checked)">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer 
                                peer-checked:after:translate-x-full peer-checked:after:border-white 
                                after:content-[''] after:absolute after:top-[2px] after:left-[2px] 
                                after:bg-white after:border-gray-300 after:border after:rounded-full 
                                after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600">
                    </div>
                    <span class="ml-3 text-sm font-medium text-gray-700">
                        <?php echo ($maintenanceConfig['enabled'] ?? false) ? 'Maintenance is ENABLED' : 'Maintenance is DISABLED'; ?>
                    </span>
                </label>
            </div>
            
            <div id="maintenanceFields" class="<?php echo ($maintenanceConfig['enabled'] ?? false) ? '' : 'hidden'; ?> space-y-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Maintenance Message</label>
                    <textarea name="maintenance_message" rows="3" 
                              class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    ><?php echo htmlspecialchars($maintenanceConfig['message'] ?? 'System is currently under maintenance. Please check back later.'); ?></textarea>
                    <p class="text-xs text-gray-400 mt-1">This message will be shown to all users during maintenance.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Completion</label>
                    <input type="datetime-local" name="estimated_end" 
                           value="<?php echo $maintenanceConfig['estimated_end'] ?? ''; ?>"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-400 mt-1">Optional: When do you expect maintenance to complete?</p>
                </div>
                <div class="text-xs text-gray-500 bg-blue-50 p-3 rounded-lg">
                    <i class="ri-information-line"></i>
                    <strong>Who can bypass maintenance:</strong>
                    <ul class="list-disc list-inside mt-1">
                        <li>Super Admin (you)</li>
                        <li>Company Owners</li>
                        <li>Localhost (127.0.0.1)</li>
                    </ul>
                </div>
            </div>
            
            <button type="submit" class="px-4 py-2 <?php echo ($maintenanceConfig['enabled'] ?? false) ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'; ?> text-white rounded-lg transition flex items-center gap-2">
                <i class="ri-<?php echo ($maintenanceConfig['enabled'] ?? false) ? 'close-circle' : 'check-circle'; ?>-line"></i>
                <?php echo ($maintenanceConfig['enabled'] ?? false) ? 'Disable' : 'Enable'; ?> Maintenance
            </button>
        </form>
    </div>
    
    <!-- Cache Management Card -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
            <i class="ri-refresh-line text-purple-600"></i>
            Cache Management
        </h3>
        <p class="text-sm text-gray-500 mb-4">Clear all system caches to resolve issues or apply changes immediately.</p>
        
        <form method="POST">
            <input type="hidden" name="action" value="clear_cache">
            
            <div class="flex items-center gap-4 flex-wrap">
                <button type="submit" onclick="return confirm('Clear all caches? This may temporarily slow down the system.')" 
                        class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition flex items-center gap-2">
                    <i class="ri-refresh-line"></i> Clear All Cache
                </button>
                <span class="text-sm text-gray-500">Clears template, session, OPcache, and APC caches</span>
            </div>
        </form>
    </div>
    
    <!-- System Info Card -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
            <i class="ri-information-line text-blue-600"></i>
            System Information
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">PHP Version</p>
                <p class="font-medium"><?php echo phpversion(); ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Server Software</p>
                <p class="font-medium"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Document Root</p>
                <p class="font-medium text-sm"><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-500">Current User</p>
                <p class="font-medium"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Unknown'); ?></p>
            </div>
        </div>
    </div>
</main>

<script>
function toggleMaintenanceFields(checked) {
    const fields = document.getElementById('maintenanceFields');
    if (fields) {
        fields.classList.toggle('hidden', !checked);
    }
    const label = document.querySelector('span.ml-3');
    if (label) {
        label.textContent = checked ? 'Maintenance is ENABLED' : 'Maintenance is DISABLED';
    }
    // Update button text
    const button = document.querySelector('button[type="submit"]');
    if (button) {
        if (checked) {
            button.innerHTML = '<i class="ri-close-circle-line"></i> Disable Maintenance';
            button.className = 'px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition flex items-center gap-2';
        } else {
            button.innerHTML = '<i class="ri-check-circle-line"></i> Enable Maintenance';
            button.className = 'px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition flex items-center gap-2';
        }
    }
}
</script>
</body>
</html>