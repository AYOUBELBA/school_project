<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

if(!isset($data['truck_id']) || !isset($data['latitude']) || !isset($data['longitude'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$truck_id = $data['truck_id'];
$latitude = $data['latitude'];
$longitude = $data['longitude'];
$speed = $data['speed'] ?? 0;

try {
    // Insert into truck_locations
    $insertSql = "INSERT INTO truck_locations (truck_id, latitude, longitude, speed_kmh, recorded_at) 
                  VALUES (:truck_id, :latitude, :longitude, :speed, CURRENT_TIMESTAMP)";
    $insertStmt = $db->prepare($insertSql);
    $insertStmt->bindParam(':truck_id', $truck_id);
    $insertStmt->bindParam(':latitude', $latitude);
    $insertStmt->bindParam(':longitude', $longitude);
    $insertStmt->bindParam(':speed', $speed);
    $insertStmt->execute();
    
    // Update trucks table
    $updateSql = "UPDATE trucks SET 
                  current_location_lat = :latitude,
                  current_location_lng = :longitude,
                  last_location_update = CURRENT_TIMESTAMP
                  WHERE id = :truck_id";
    $updateStmt = $db->prepare($updateSql);
    $updateStmt->bindParam(':latitude', $latitude);
    $updateStmt->bindParam(':longitude', $longitude);
    $updateStmt->bindParam(':truck_id', $truck_id);
    $updateStmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Location updated']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>