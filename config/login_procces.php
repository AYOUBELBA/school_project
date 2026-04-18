<?php

require_once('../config/session.php');
require_once("database.php");

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Check if connection is successful
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}
// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    
    // Get and sanitize input data
    $email = trim($_POST['em'] ?? '');
    $password = $_POST['pass'] ?? '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        header("Location: ../signup.php?error=empty_fields");
        exit();
    }
    
    try {
        // FIXED: Using 'username' instead of 'name'
        $sql = "SELECT id, email, password_hash, role, last_login, username 
                FROM users 
                WHERE email = :email AND is_active = true";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        // Check if user exists
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                
                // Update last_login timestamp
                $updateSql = "UPDATE users 
                             SET last_login = CURRENT_TIMESTAMP 
                             WHERE id = :id";
                
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bindParam(':id', $user['id']);
                $updateStmt->execute();
                
                // Start session and store user data
                session_start();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['role'] = $user['role'];  // ADD THIS LINE for consistency
                $_SESSION['last_login'] = $user['last_login'];
                $_SESSION['login_time'] = time();
                
                // Redirect based on role
                if ($user['role'] == 'driver') {
                    header("Location: ../pages/driver_dashboard.php");
                } else {
                    header("Location: ../pages/dashboard.php");
                }
                exit();
                
            } else {
                // Wrong password
                header("Location: ../signup.php?error=invalid_credentials");
                exit();
            }
        } else {
            // User not found
            header("Location: ../signup.php?error=invalid_credentials");
            exit();
        }
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        header("Location: ../signup.php?error=system_error");
        exit();
    }
} else {
    // If someone tries to access this file directly without POST
    header("Location: ../signup.php");
    exit();
}
?>