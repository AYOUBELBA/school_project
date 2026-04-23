<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Set your desired password
$password = 'Ayou2020@';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "\n";
echo "Hash: " . $hash . "\n\n";

// Update super_admin user
$sql = "UPDATE users SET password_hash = :hash WHERE role = 'super_admin'";
$stmt = $db->prepare($sql);
$stmt->bindParam(':hash', $hash);

if($stmt->execute()) {
    echo "Super admin password updated successfully!\n";
    echo "Email: super@admin.com\n";
    echo "Password: " . $password . "\n";
} else {
    echo "Failed to update password\n";
}
?>