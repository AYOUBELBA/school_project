<?php
header('Content-Type: application/json');
session_start();

require_once '../../config/database.php';
require_once '../../config/auth_saas.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Only super admin can access
if($_SESSION['user_role'] !== 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$company_id = $data['company_id'] ?? 0;
$status = $data['status'] ?? '';

if(!$company_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Company ID and status required']);
    exit();
}

try {
    $sql = "UPDATE companies SET subscription_status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :company_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    
    // Log activity
    $logSql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, created_at) 
               VALUES (:user_id, 'Toggled company status', 'companies', :company_id, :ip, CURRENT_TIMESTAMP)";
    $logStmt = $db->prepare($logSql);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $logStmt->bindParam(':user_id', $_SESSION['user_id']);
    $logStmt->bindParam(':company_id', $company_id);
    $logStmt->bindParam(':ip', $ip);
    $logStmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Company status updated']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>