<?php
// config/debug_register.php - Debug version
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once("database.php");

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Check if connection is successful
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

echo "Database connected successfully!<br>";

if ($_SERVER['REQUEST_METHOD'] == "POST"){
    echo "Form submitted!<br>";
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['em'] ?? '');
    $password = $_POST['pass'] ?? '';
    $role = "manager";
    
    echo "Name: $name<br>";
    echo "Email: $email<br>";
    echo "Password length: " . strlen($password) . "<br>";
    
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
        echo "Validation errors: " . implode(", ", $errors);
        exit();
    }
    
    // Check if email already exists
    try {
        echo "Checking if email exists...<br>";
        $checkSql = "SELECT id FROM users WHERE email = :email";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            echo "Email already exists!";
            exit();
        }
        
        echo "Email is unique. Inserting user...<br>";
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // First, let's check what columns your users table actually has
        $columnsSql = "SELECT column_name FROM information_schema.columns WHERE table_name = 'users'";
        $columnsStmt = $conn->prepare($columnsSql);
        $columnsStmt->execute();
        $columns = $columnsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Your users table has these columns:<br>";
        foreach ($columns as $col) {
            echo "- " . $col['column_name'] . "<br>";
        }
        
        // Try a simpler insert first to see what works
        $sql = "INSERT INTO users (name, email, password_hash, role, is_active, created_at) 
                VALUES (:name, :email, :password_hash, :role, true, CURRENT_TIMESTAMP) 
                RETURNING id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $hashed_password);
        $stmt->bindParam(':role', $role);
        
        if ($stmt->execute()) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "User created successfully! ID: " . $user['id'];
        } else {
            echo "Failed to insert user.";
        }
        
    } catch (PDOException $e) {
        echo "ERROR: " . $e->getMessage();
    }
} else {
    echo "No POST request. Please submit the form.";
}
?>