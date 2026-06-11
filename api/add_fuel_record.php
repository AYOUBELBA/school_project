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
$liters = $data['liters'] ?? 0;
$cost_per_liter = $data['cost_per_liter'] ?? 0;
$total_cost = $data['total_cost'] ?? 0;
$fuel_station = $data['fuel_station'] ?? '';
$driver_id = $data['driver_id'] ?? 0;
$invoice_number = $data['invoice_number'] ?? '';
$odometer_km = $data['odometer_km'] ?? null;

if (!$truck_id || !$liters || !$total_cost) {
    echo json_encode(['success' => false, 'message' => 'Truck ID, liters, and total cost required']);
    exit();
}

// Get company_id from truck
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
    $sql = "INSERT INTO fuel_records (truck_id, liters, cost_per_liter, total_cost, odometer_km, fuel_station, fuel_date, company_id, invoice_number, created_at) 
            VALUES (:truck_id, :liters, :cost_per_liter, :total_cost, :odometer_km, :fuel_station, CURRENT_DATE, :company_id, :invoice_number, CURRENT_TIMESTAMP)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':truck_id', $truck_id, PDO::PARAM_INT);
    $stmt->bindParam(':liters', $liters);
    $stmt->bindParam(':cost_per_liter', $cost_per_liter);
    $stmt->bindParam(':total_cost', $total_cost);
    $stmt->bindParam(':odometer_km', $odometer_km);
    $stmt->bindParam(':fuel_station', $fuel_station);
    $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindParam(':invoice_number', $invoice_number);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Fuel record added successfully']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>