<?php
header('Content-Type: application/json');
session_start();

require_once '../../config/database.php';
require_once '../../config/auth_saas.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if($_SESSION['user_role'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? 0;

if(!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit();
}

if($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
    exit();
}

try {
    $sql = "DELETE FROM users WHERE id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>