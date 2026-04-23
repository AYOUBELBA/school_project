<?php
// includes/check_auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required classes in correct order
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_saas.php';  // Add this line

// Create database connection
$database = new Database();
$db = $database->getConnection();

// Create auth instance
$auth = new Auth($db);

// Now check if user is logged in
if(!isset($_SESSION['user_id'])) {
    $current_page = basename($_SERVER['PHP_SELF']);
    $allowed_pages = ['signin.php', 'signup.php', 'register_company.php', 'register_invite.php', 'index.php', 'signin.html', 'signup.html'];
    if(!in_array($current_page, $allowed_pages)) {
        header("Location: ../signin.php");
        exit();
    }
}

// If logged in, ensure company_id is set
if(isset($_SESSION['user_id']) && !isset($_SESSION['company_id'])) {
    try {
        $sql = "SELECT company_id FROM users WHERE id = :user_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if($user && $user['company_id']) {
            $_SESSION['company_id'] = $user['company_id'];
        }
    } catch (Exception $e) {
        // Silently fail - company_id will be set later
    }
}

// Set consistent session variables
if(isset($_SESSION['username']) && !isset($_SESSION['user_email'])) {
    $_SESSION['user_email'] = $_SESSION['username'];
}
if(isset($_SESSION['role']) && !isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = $_SESSION['role'];
}
if(isset($_SESSION['user_role']) && !isset($_SESSION['role'])) {
    $_SESSION['role'] = $_SESSION['user_role'];
}
?>