<?php
// login_process.php

require_once("connect.php");

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    
    // Get and sanitize input data
    $email = trim($_POST['em'] ?? '');
    $password = $_POST['pass'] ?? '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        die("Email and password are required");
    }
    
    try {
        // Prepare SQL to get user by email - using prepared statement for security
        $sql = "SELECT id, email, password_hash, role, last_login 
                FROM users 
                WHERE email = :email";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        // Check if user exists
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password (assuming passwords are hashed with password_hash())
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
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['last_login'] = $user['last_login'];
                
                // Redirect based on role or to dashboard
                header("Location: dashboard.php");
                exit();
                
            } else {
                // Wrong password
                die("Invalid email or password");
            }
        } else {
            // User not found
            die("Invalid email or password");
        }
        
    } catch (PDOException $e) {
        die("Login error: " . $e->getMessage());
    }
} else {
    // If someone tries to access this file directly without POST
    header("Location: login.php");
    exit();
}
?>