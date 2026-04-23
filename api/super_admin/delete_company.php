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
$company_id = $data['company_id'] ?? 0;

if(!$company_id) {
    echo json_encode(['success' => false, 'message' => 'Company ID required']);
    exit();
}

try {
    // First, get company name for logging
    $nameSql = "SELECT name FROM companies WHERE id = :id";
    $nameStmt = $db->prepare($nameSql);
    $nameStmt->bindParam(':id', $company_id);
    $nameStmt->execute();
    $company = $nameStmt->fetch(PDO::FETCH_ASSOC);
    
    // Delete company (cascade will delete related data)
    $sql = "DELETE FROM companies WHERE id = :company_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Company deleted successfully']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>