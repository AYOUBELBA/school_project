<?php
// CORS headers
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

$database = new Database();
$db = $database->getConnection();

// Get driver_id from query parameter
$driver_id = $_GET['driver_id'] ?? 0;

if (!$driver_id) {
    echo json_encode(['success' => false, 'message' => 'Driver ID is required']);
    exit();
}

try {
    // Get current active trip for this driver
    $sql = "SELECT 
                t.id,
                t.trip_number,
                t.status,
                t.start_location,
                t.end_location,
                t.start_time,
                t.end_time,
                t.priority,
                t.client_name,
                t.client_phone,
                t.notes,
                tr.id as truck_id,
                tr.plate_number as truck_plate,
                tr.model as truck_model,
                r.id as route_id,
                r.name as route_name,
                r.start_point,
                r.end_point,
                r.estimated_distance_km,
                r.estimated_duration_min
            FROM trips t
            LEFT JOIN trucks tr ON t.truck_id = tr.id
            LEFT JOIN routes r ON t.route_id = r.id
            WHERE t.driver_id = :driver_id 
            AND t.status IN ('planned', 'ongoing')
            ORDER BY 
                CASE WHEN t.status = 'ongoing' THEN 0 ELSE 1 END,
                t.start_time ASC
            LIMIT 1";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Format the response
        $response = [
            'success' => true,
            'current_trip' => [
                'id' => $trip['id'],
                'trip_number' => $trip['trip_number'],
                'status' => $trip['status'],
                'start_location' => $trip['start_location'],
                'end_location' => $trip['end_location'],
                'start_time' => $trip['start_time'],
                'end_time' => $trip['end_time'],
                'priority' => $trip['priority'],
                'client_name' => $trip['client_name'],
                'client_phone' => $trip['client_phone'],
                'notes' => $trip['notes'],
                'truck_id' => $trip['truck_id'],
                'truck_plate' => $trip['truck_plate'],
                'truck_model' => $trip['truck_model'],
                'route_id' => $trip['route_id'],
                'route_name' => $trip['route_name'],
                'route_start' => $trip['start_point'],
                'route_end' => $trip['end_point'],
                'estimated_distance_km' => $trip['estimated_distance_km'],
                'estimated_duration_min' => $trip['estimated_duration_min']
            ]
        ];
        
        echo json_encode($response);
    } else {
        // No active trip found
        echo json_encode([
            'success' => true,
            'current_trip' => null,
            'message' => 'No active trip assigned'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Get current trip error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>