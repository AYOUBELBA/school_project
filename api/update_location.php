<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
session_start();

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if(!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

// Validate required fields
if(!isset($input['truck_id']) || !isset($input['latitude']) || !isset($input['longitude'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$truck_id = $input['truck_id'];
$latitude = $input['latitude'];
$longitude = $input['longitude'];
$speed = $input['speed'] ?? 0;
$heading = $input['heading'] ?? 0;
$accuracy = $input['accuracy'] ?? 0;

try {
    // Start transaction
    $db->beginTransaction();
    
    // Insert into truck_locations table
    $insertSql = "INSERT INTO truck_locations (truck_id, latitude, longitude, speed_kmh, heading, accuracy_m, recorded_at) 
                  VALUES (:truck_id, :latitude, :longitude, :speed, :heading, :accuracy, CURRENT_TIMESTAMP)";
    $insertStmt = $db->prepare($insertSql);
    $insertStmt->bindParam(':truck_id', $truck_id);
    $insertStmt->bindParam(':latitude', $latitude);
    $insertStmt->bindParam(':longitude', $longitude);
    $insertStmt->bindParam(':speed', $speed);
    $insertStmt->bindParam(':heading', $heading);
    $insertStmt->bindParam(':accuracy', $accuracy);
    $insertStmt->execute();
    
    // Update trucks table with current location
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
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Location updated',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch(PDOException $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>