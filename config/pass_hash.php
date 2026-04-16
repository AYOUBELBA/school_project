<?php
require_once("connect.php");

if ($_SERVER['REQUEST_METHOD'] == "POST"){
    $name = $_POST['name'];
    $email = $_POST['em'];
    $password = $_POST['pass'];
    $role = "client"; 
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $sql = "INSERT INTO users (email, password_hash, role, last_login,name) 
            VALUES (:email, :password_hash, :role, NULL,:name)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password_hash', $hashed_password);
    $stmt->bindParam(':role', $role);
    
    if ($stmt->execute()) {
        header("Location: ../signin.html");
        exit();
    } else {
        echo "Error creating user";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>