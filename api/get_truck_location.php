<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$truck_id = $_GET['truck_id'] ?? 0;

$sql = "SELECT current_location_lat as latitude, current_location_lng as longitude, 
        last_location_update, (SELECT speed_kmh FROM truck_locations 
        WHERE truck_id = :truck_id ORDER BY recorded_at DESC LIMIT 1) as speed
        FROM trucks WHERE id = :truck_id";

$stmt = $db->prepare($sql);
$stmt->bindParam(':truck_id', $truck_id);
$stmt->execute();

if($stmt->rowCount() > 0) {
    $location = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => true,
        'location' => $location
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Truck not found'
    ]);
}
?>