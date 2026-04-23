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
$plan = $data['plan'] ?? '';
$max_drivers = $data['max_drivers'] ?? 0;
$max_trucks = $data['max_trucks'] ?? 0;
$max_managers = $data['max_managers'] ?? 0;

if(!$company_id || !$plan) {
    echo json_encode(['success' => false, 'message' => 'Company ID and plan required']);
    exit();
}

try {
    $sql = "UPDATE companies SET 
            subscription_plan = :plan, 
            max_drivers = :max_drivers,
            max_trucks = :max_trucks,
            max_managers = :max_managers,
            updated_at = CURRENT_TIMESTAMP 
            WHERE id = :company_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':plan', $plan);
    $stmt->bindParam(':max_drivers', $max_drivers);
    $stmt->bindParam(':max_trucks', $max_trucks);
    $stmt->bindParam(':max_managers', $max_managers);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Company plan updated']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>