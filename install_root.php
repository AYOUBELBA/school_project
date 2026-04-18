<?php
// install_root.php - Safe root user creation
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Configuration - CHANGE THESE VALUES!
$root_config = [
    'id' => 1,
    'username' => 'root',
    'email' => 'root@root.com',
    'password' => 'root', // CHANGE THIS!
    'role' => 'admin'
];

// Check if root already exists
$check = "SELECT id FROM users WHERE role = 'admin' AND (username = :username OR email = :email)";
$checkStmt = $db->prepare($check);
$checkStmt->execute([
    ':username' => $root_config['username'],
    ':email' => $root_config['email']
]);

if($checkStmt->rowCount() > 0) {
    echo "⚠️  Root admin user already exists!\n";
    exit();
}

// Hash the password
$hashed_password = password_hash($root_config['password'], PASSWORD_DEFAULT);

// Insert root user
$sql = "INSERT INTO users (id,username, email, password_hash, role, is_active, created_at) 
        VALUES (:id,:username, :email, :password_hash, :role, true, CURRENT_TIMESTAMP)";

$stmt = $db->prepare($sql);
$stmt->execute([
    ':id'=> $root_config['id'],
    ':username' => $root_config['username'],
    ':email' => $root_config['email'],
    ':password_hash' => $hashed_password,
    ':role' => $root_config['role']
]);

echo "✅ Root user created successfully!\n\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🔐 LOGIN CREDENTIALS:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Username: " . $root_config['username'] . "\n";
echo "Email:    " . $root_config['email'] . "\n";
echo "Password: " . $root_config['password'] . "\n";
echo "Role:     " . $root_config['role'] . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
echo "⚠️  IMPORTANT SECURITY NOTICES:\n";
echo "1. Delete this file immediately after use!\n";
echo "2. Store the password in a secure password manager\n";
echo "3. Change the password after first login\n";
echo "4. Never commit this file to version control\n";
?>