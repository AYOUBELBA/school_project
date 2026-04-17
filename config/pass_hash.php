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

if ($_SERVER['REQUEST_METHOD'] == "POST"){
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['em'] ?? '');
    $password = $_POST['pass'] ?? '';
    $role = "manager";
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if (!empty($errors)) {
        $error_string = implode(", ", $errors);
        header("Location: ../signup.php?error=" . urlencode($error_string));
        exit();
    }
    
    // Check if email already exists
    try {
        $checkSql = "SELECT id FROM users WHERE email = :email";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            header("Location: ../signup.php?error=email_exists");
            exit();
        }
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // FIXED: Using 'username' instead of 'name'
        $sql = "INSERT INTO users (username, email, password_hash, role, is_active, created_at) 
                VALUES (:username, :email, :password_hash, :role, true, CURRENT_TIMESTAMP) 
                RETURNING id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':username', $name);  // Changed from ':name' to ':username'
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $hashed_password);
        $stmt->bindParam(':role', $role);
        
        if ($stmt->execute()) {
            // Get the new user ID
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $user['id'];
            
            // Auto-login after registration
            session_start();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_email'] = $email;
            $_SESSION['username'] = $name;
            $_SESSION['user_role'] = $role;
            $_SESSION['login_time'] = time();
            
            // Redirect to dashboard
            header("Location: ../pages/dashboard.php");
            exit();
        } else {
            header("Location: ../signup.php?error=registration_failed");
            exit();
        }
        
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        
        if (strpos($e->getMessage(), "duplicate key") !== false) {
            header("Location: ../signup.php?error=email_exists");
        } else {
            header("Location: ../signup.php?error=system_error");
        }
        exit();
    }
} else {
    header("Location: ../signup.php");
    exit();
}
?>