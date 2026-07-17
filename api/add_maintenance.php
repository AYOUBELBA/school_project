<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
session_start();

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

$truck_id = $data['truck_id'] ?? 0;
$maintenance_type = $data['maintenance_type'] ?? '';
$notes = $data['notes'] ?? '';
$reported_by = $data['reported_by'] ?? 0;

if (!$truck_id || !$maintenance_type) {
    echo json_encode(['success' => false, 'message' => 'Truck ID and maintenance type required']);
    exit();
}

// Get company_id from the truck
$truckSql = "SELECT company_id FROM trucks WHERE id = :truck_id";
$truckStmt = $db->prepare($truckSql);
$truckStmt->bindParam(':truck_id', $truck_id, PDO::PARAM_INT);
$truckStmt->execute();
$truck = $truckStmt->fetch(PDO::FETCH_ASSOC);

if (!$truck) {
    echo json_encode(['success' => false, 'message' => 'Truck not found']);
    exit();
}

$company_id = $truck['company_id'];

try {
    $sql = "INSERT INTO truck_maintenance (truck_id, maintenance_type, description, status, maintenance_date, created_at, company_id) 
            VALUES (:truck_id, :maintenance_type, :description, 'scheduled', CURRENT_DATE, CURRENT_TIMESTAMP, :company_id)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':truck_id', $truck_id, PDO::PARAM_INT);
    $stmt->bindParam(':maintenance_type', $maintenance_type, PDO::PARAM_STR);
    $stmt->bindParam(':description', $notes, PDO::PARAM_STR);
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Maintenance request submitted']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>