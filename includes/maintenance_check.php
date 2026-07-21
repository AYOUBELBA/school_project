<?php

/**
 * Check if system is in maintenance mode
 * Returns false if maintenance is disabled or user is allowed to bypass
 * Returns config array if maintenance is active
 */
function isMaintenanceMode() {
    // Only check if the file exists
    $configFile = __DIR__ . '/../config/maintenance.php';
    
    if (!file_exists($configFile)) {
        return false;
    }
    
    // Load config
    $config = include $configFile;
    
    // If config isn't an array or maintenance is disabled
    if (!is_array($config) || empty($config['enabled']) || $config['enabled'] !== true) {
        return false;
    }
    
    // Check if current IP is allowed to bypass
    $userIP = $_SERVER['REMOTE_ADDR'] ?? '';
    if (isset($config['allowed_ips']) && in_array($userIP, $config['allowed_ips'])) {
        return false;
    }
    
    // Check if user role is allowed to bypass
    if (isset($_SESSION['user_role']) && isset($config['allowed_roles'])) {
        if (in_array($_SESSION['user_role'], $config['allowed_roles'])) {
            return false;
        }
    }
    
    // Maintenance is active
    return $config;
}

// Run maintenance check ONLY if user is logged in
if (isset($_SESSION['user_id'])) {
    $maintenance = isMaintenanceMode();
    
    if ($maintenance !== false) {
        // Get current page
        $currentPage = basename($_SERVER['PHP_SELF']);
        $currentDir = basename(dirname($_SERVER['PHP_SELF']));
        
        // Pages that are ALWAYS accessible during maintenance
        $allowedPages = [
            'login.php', 'logout.php', 'signin.php', 'signup.php',
            'maintenance_page.php', 'system_settings.php'
        ];
        $allowedDirs = ['api']; // API endpoints should still work
        
        // Check if current page is allowed
        $isAllowed = in_array($currentPage, $allowedPages) || in_array($currentDir, $allowedDirs);
        
        // Special: Allow super_admin to access everything during maintenance
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin') {
            $isAllowed = true;
        }
        
        if (!$isAllowed) {
            // Always redirect to the maintenance page in the school_project folder
            $maintenancePage = '/school_project/pages/maintenance_page.php';
            
            // If we're already on a maintenance-related page, don't redirect again
            if ($currentPage !== 'maintenance_page.php') {
                header('Location: ' . $maintenancePage);
                exit();
            }
        }
    }
}
?>