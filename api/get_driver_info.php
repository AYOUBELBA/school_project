<?php

// Add CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';


header('Content-Type: application/json');


$database = new Database();
$db = $database->getConnection();

$driver_id = $_GET['driver_id'] ?? 0;

$sql = "SELECT d.*, t.id as truck_id, t.plate_number as truck_plate, t.model as truck_model,
        (SELECT COUNT(*) FROM trips WHERE driver_id = d.id) as total_trips,
        (SELECT COUNT(*) FROM trips WHERE driver_id = d.id AND status = 'completed') as completed_trips
        FROM drivers d
        LEFT JOIN trucks t ON t.current_driver_id = d.id
        WHERE d.id = :driver_id";

$stmt = $db->prepare($sql);
$stmt->bindParam(':driver_id', $driver_id);
$stmt->execute();
$driver = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'driver' => $driver,
    'current_trip' => null // Add trip query here
]);
?>