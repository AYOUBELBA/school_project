<?php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required classes in correct order
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_saas.php';

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Create auth instance
$auth = new Auth($db);

// Helper function to get database connection
function db() {
    global $db;
    return $db;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $current_page = basename($_SERVER['PHP_SELF']);
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));
    
    // Pages that don't require authentication
    $allowed_pages = [
        'signin.php', 'signup.php', 'register_company.php', 
        'register_invite.php', 'index.php', 'signin.html', 
        'signup.html', 'login.php', 'maintenance_page.php'
    ];
    $allowed_dirs = ['api']; // API endpoints don't need auth check
    
    if (!in_array($current_page, $allowed_pages) && !in_array($current_dir, $allowed_dirs)) {
        // Determine correct redirect path
        $redirect_path = '../signin.php';
        
        // If we're in a subfolder like pages/super_admin/, go back 3 levels
        if (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) {
            $redirect_path = '../../signin.php';
        }
        
        header("Location: " . $redirect_path);
        exit();
    }
}

// Check if user is active
if (isset($_SESSION['is_active']) && $_SESSION['is_active'] != 1) {
    session_destroy();
    header('Location: /school_project/login.php?error=account_disabled');
    exit();
}

// If logged in, ensure company_id is set
if (isset($_SESSION['user_id']) && !isset($_SESSION['company_id'])) {
    try {
        $sql = "SELECT company_id FROM users WHERE id = :user_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && $user['company_id']) {
            $_SESSION['company_id'] = $user['company_id'];
        }
    } catch (Exception $e) {
        // Silently fail - company_id will be set later
    }
}

// Set consistent session variables
if (isset($_SESSION['username']) && !isset($_SESSION['user_email'])) {
    $_SESSION['user_email'] = $_SESSION['username'];
}
if (isset($_SESSION['role']) && !isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = $_SESSION['role'];
}
if (isset($_SESSION['user_role']) && !isset($_SESSION['role'])) {
    $_SESSION['role'] = $_SESSION['user_role'];
}

// Include maintenance check AFTER authentication
// This ensures logged-in users with proper roles can bypass
require_once __DIR__ . '/maintenance_check.php';
?>