<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$driver_id = $_GET['driver_id'] ?? 0;

if (!$driver_id) {
    echo json_encode(['success' => false, 'message' => 'Driver ID required']);
    exit();
}

try {
    // Get current active trip
    $currentSql = "SELECT t.*, tr.plate_number as truck_plate, tr.model as truck_model,
                   r.name as route_name, r.start_point, r.end_point
                   FROM trips t
                   LEFT JOIN trucks tr ON t.truck_id = tr.id
                   LEFT JOIN routes r ON t.route_id = r.id
                   WHERE t.driver_id = :driver_id AND t.status IN ('planned', 'ongoing')
                   ORDER BY t.start_time ASC LIMIT 1";
    
    $currentStmt = $db->prepare($currentSql);
    $currentStmt->bindParam(':driver_id', $driver_id);
    $currentStmt->execute();
    $current_trip = $currentStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get past trips
    $pastSql = "SELECT t.*, tr.plate_number as truck_plate
                FROM trips t
                LEFT JOIN trucks tr ON t.truck_id = tr.id
                WHERE t.driver_id = :driver_id AND t.status IN ('completed', 'cancelled')
                ORDER BY t.created_at DESC LIMIT 10";
    
    $pastStmt = $db->prepare($pastSql);
    $pastStmt->bindParam(':driver_id', $driver_id);
    $pastStmt->execute();
    $past_trips = $pastStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'current_trip' => $current_trip,
        'past_trips' => $past_trips
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>