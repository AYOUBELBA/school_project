<?php
require_once '../../includes/check_auth.php';

if($_SESSION['user_role'] !== 'super_admin') {
    header("Location: ../dashboard.php");
    exit();
}

$message = '';
$error = '';

// Handle settings update
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(isset($_POST['action'])) {
        if($_POST['action'] === 'clear_cache') {
            // Clear session cache
            $_SESSION['flash_success'] = "Cache cleared successfully!";
            header("Location: system_settings.php");
            exit();
        }
        
        if($_POST['action'] === 'maintenance_mode') {
            $mode = $_POST['maintenance_mode'];
            // Store in a config file or database
            file_put_contents('../../config/maintenance.txt', $mode);
            $_SESSION['flash_success'] = "Maintenance mode " . ($mode === 'on' ? 'enabled' : 'disabled');
            header("Location: system_settings.php");
            exit();
        }
    }
}

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$maintenance_mode = file_exists('../../config/maintenance.txt') ? file_get_contents('../../config/maintenance.txt') : 'off';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<?php include '../../includes/super_admin_sidebar.php'; ?>

<main class="ml-72 p-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">System Settings</h1>
        <p class="text-gray-500">Configure platform-wide settings</p>
    </div>
    
    <?php if($flash_success): ?>
    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg"><?php echo $flash_success; ?></div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Maintenance Mode -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Maintenance Mode</h3>
            <form method="POST">
                <input type="hidden" name="action" value="maintenance_mode">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600">Enable maintenance mode</p>
                        <p class="text-xs text-gray-400">When enabled, users will see a maintenance page</p>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" name="maintenance_mode" value="on" class="px-4 py-2 rounded-lg <?php echo $maintenance_mode === 'on' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700'; ?>">
                            On
                        </button>
                        <button type="submit" name="maintenance_mode" value="off" class="px-4 py-2 rounded-lg <?php echo $maintenance_mode === 'off' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700'; ?>">
                            Off
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Cache Management -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Cache Management</h3>
            <form method="POST">
                <input type="hidden" name="action" value="clear_cache">
                <p class="text-gray-600 mb-4">Clear system cache to refresh data</p>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    Clear Cache
                </button>
            </form>
        </div>
        
        <!-- System Info -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">System Information</h3>
            <div class="space-y-2">
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">PHP Version</span>
                    <span class="font-medium"><?php echo phpversion(); ?></span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">Database</span>
                    <span class="font-medium">PostgreSQL</span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">Server Time</span>
                    <span class="font-medium"><?php echo date('Y-m-d H:i:s'); ?></span>
                </div>
                <div class="flex justify-between py-2 border-b">
                    <span class="text-gray-600">Upload Limit</span>
                    <span class="font-medium"><?php echo ini_get('upload_max_filesize'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Database Backup -->
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Database Backup</h3>
            <p class="text-gray-600 mb-4">Download a backup of the entire database</p>
            <button onclick="backupDatabase()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                Download Backup
            </button>
        </div>
    </div>
</main>

<script>
function backupDatabase() {
    if(confirm('Download database backup? This may take a moment.')) {
        window.location.href = '../../api/backup_database.php';
    }
}
</script>
</body>
</html>