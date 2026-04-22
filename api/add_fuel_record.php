<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

$truck_id = $data['truck_id'] ?? 0;
$liters = $data['liters'] ?? 0;
$cost_per_liter = $data['cost_per_liter'] ?? 0;
$total_cost = $data['total_cost'] ?? 0;
$fuel_station = $data['fuel_station'] ?? '';
$driver_id = $data['driver_id'] ?? 0;  // This is driver ID from drivers table

if (!$truck_id || !$liters || !$total_cost) {
    echo json_encode(['success' => false, 'message' => 'Truck ID, liters, and total cost required']);
    exit();
}

try {
    // Get the actual user_id from the driver_id
    $userSql = "SELECT user_id FROM drivers WHERE id = :driver_id";
    $userStmt = $db->prepare($userSql);
    $userStmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Driver not linked to a user account']);
        exit();
    }
    
    $user_id = $user['user_id'];
    
    // Insert using the user_id from users table
    $sql = "INSERT INTO fuel_records (truck_id, liters, cost_per_liter, total_cost, fuel_station, fuel_date, created_by, created_at) 
            VALUES (:truck_id, :liters, :cost_per_liter, :total_cost, :fuel_station, CURRENT_DATE, :created_by, CURRENT_TIMESTAMP)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':truck_id', $truck_id, PDO::PARAM_INT);
    $stmt->bindParam(':liters', $liters);
    $stmt->bindParam(':cost_per_liter', $cost_per_liter);
    $stmt->bindParam(':total_cost', $total_cost);
    $stmt->bindParam(':fuel_station', $fuel_station);
    $stmt->bindParam(':created_by', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Fuel record added successfully']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>