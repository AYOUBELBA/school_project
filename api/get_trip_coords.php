<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$tripId = $_GET['id'] ?? 0;
$driverId = $_GET['driver_id'] ?? null;  // NEW: Accept driver_id as parameter

$database = new Database();
$db = $database->getConnection();

// First, verify the trip belongs to this driver (optional security check)
if ($driverId) {
    $checkSql = "SELECT t.* FROM trips t 
                 WHERE t.id = :trip_id AND t.driver_id = :driver_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':trip_id', $tripId);
    $checkStmt->bindParam(':driver_id', $driverId);
    $checkStmt->execute();
    $trip = $checkStmt->fetch(PDO::FETCH_ASSOC);
} else {
    // Fallback: just get by ID (less secure)
    $sql = "SELECT * FROM trips WHERE id = :trip_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':trip_id', $tripId);
    $stmt->execute();
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($trip) {
    // Check if coordinates exist
    if (!empty($trip['start_lat']) && !empty($trip['end_lat'])) {
        echo json_encode([
            'success' => true,
            'trip_id' => $trip['id'],
            'trip_number' => $trip['trip_number'],
            'start_location' => $trip['start_location'],
            'start_lat' => $trip['start_lat'],
            'start_lng' => $trip['start_lng'],
            'end_location' => $trip['end_location'],
            'end_lat' => $trip['end_lat'],
            'end_lng' => $trip['end_lng']
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Coordinates not available for this trip'
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Trip not found or access denied']);
}
?>