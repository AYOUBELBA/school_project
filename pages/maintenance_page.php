<?php
session_start();

// Load maintenance config
$configFile = '../config/maintenance.php';
$config = file_exists($configFile) ? include $configFile : ['message' => 'System is currently under maintenance.'];

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /school_project/signin.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>System Maintenance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        body { 
            background: #f3f4f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        .logout-btn {
            transition: all 0.2s ease;
        }
        .logout-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
    </style>
</head>
<body>
    <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 max-w-md w-full text-center">
        <!-- Logo/Icon -->
        <div class="w-20 h-20 sm:w-24 sm:h-24 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="ri-tools-line text-4xl sm:text-5xl text-yellow-600"></i>
        </div>
        
        <h1 class="text-2xl font-bold text-gray-800 mb-2">System Maintenance</h1>
        
        <div class="w-16 h-1 bg-yellow-400 mx-auto mb-4"></div>
        
        <p class="text-gray-600 mb-6 text-sm sm:text-base">
            <?php 
            echo htmlspecialchars($config['message'] ?? 'System is currently under maintenance. Please check back later.'); 
            ?>
        </p>
        
        <?php if(isset($config['estimated_end']) && $config['estimated_end']): ?>
        <div class="bg-blue-50 rounded-lg p-3 mb-6">
            <p class="text-sm text-blue-700">
                <i class="ri-time-line"></i> 
                Estimated completion: <?php echo date('F d, Y \a\t H:i', strtotime($config['estimated_end'])); ?>
            </p>
        </div>
        <?php endif; ?>
        
        <!-- Progress bar animation -->
        <div class="mb-6">
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-blue-600 rounded-full w-3/4 animate-pulse"></div>
            </div>
        </div>
        
        <p class="text-xs text-gray-400 mb-6">Please check back soon</p>
        
        <!-- Auto refresh every 30 seconds -->
        <meta http-equiv="refresh" content="30">
        
        <!-- Action Buttons -->
        <div class="space-y-3">
            <!-- Logout Button -->
            <a href="?logout=true" 
               onclick="return confirm('Are you sure you want to logout?')"
               class="logout-btn w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-medium">
                <i class="ri-logout-box-r-line"></i>
                Logout
            </a>
            
            <!-- If user is admin, show bypass option -->
            <?php if(isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['super_admin', 'owner'])): ?>
            <div class="pt-3 border-t border-gray-200">
                <p class="text-xs text-gray-500 mb-2">
                    <i class="ri-shield-user-line"></i> You are logged in as <?php echo htmlspecialchars($_SESSION['user_role']); ?>
                </p>
                <?php if($_SESSION['user_role'] === 'super_admin'): ?>
                <a href="/school_project/pages/super_admin/dashboard.php" 
                   class="text-sm text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                    <i class="ri-arrow-right-line"></i> Return to Dashboard
                </a>
                <?php else: ?>
                <a href="/school_project/pages/dashboard.php" 
                   class="text-sm text-blue-600 hover:text-blue-800 inline-flex items-center gap-1">
                    <i class="ri-arrow-right-line"></i> Return to Dashboard
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>