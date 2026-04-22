<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$truck_id = $_GET['truck_id'] ?? 0;

if (!$truck_id) {
    echo json_encode(['success' => false, 'message' => 'Truck ID required']);
    exit();
}

$sql = "SELECT id, plate_number, model, brand, year, capacity_kg, fuel_type, status, odometer_km 
        FROM trucks WHERE id = :truck_id";

$stmt = $db->prepare($sql);
$stmt->bindParam(':truck_id', $truck_id, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $truck = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => true,
        'truck' => $truck
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Truck not found']);
}
?>