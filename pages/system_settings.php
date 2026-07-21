<?php
require_once '../includes/check_auth.php';
require_once '../config/session.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if(!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? $_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$success = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add your system settings logic here
    $success = "System settings updated successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings | Fleet System</title>
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">System Settings</h2>
            <p class="text-gray-500">Configure system-wide settings</p>
        </div>
        
        <?php if($success): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- General Settings -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">General Settings</h3>
                <form method="POST">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">System Name</label>
                            <input type="text" value="Fleet Management System" class="w-full px-3 py-2 border rounded-lg bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Time Zone</label>
                            <select class="w-full px-3 py-2 border rounded-lg bg-gray-50">
                                <option>UTC</option>
                                <option>America/New_York</option>
                                <option>Europe/London</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date Format</label>
                            <select class="w-full px-3 py-2 border rounded-lg bg-gray-50">
                                <option>Y-m-d</option>
                                <option>d/m/Y</option>
                                <option>m/d/Y</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- System Info -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">System Information</h3>
                <div class="space-y-3">
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
                        <span class="text-gray-600">Last Backup</span>
                        <span class="font-medium">Never</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>