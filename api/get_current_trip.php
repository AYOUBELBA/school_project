<?php
// api/get_current_trip.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;

if ($driver_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid driver ID']);
    exit();
}

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = "SELECT 
                t.id,
                t.trip_number,
                t.start_location,
                t.end_location,
                t.status,
                t.start_time,
                t.end_time,
                t.created_at,
                r.name as route_name
            FROM trips t
            LEFT JOIN routes r ON t.route_id = r.id
            WHERE t.driver_id = :driver_id 
            AND t.status IN ('planned', 'ongoing')
            ORDER BY t.created_at DESC
            LIMIT 1";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($trip) {
        echo json_encode([
            'success' => true,
            'current_trip' => $trip
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'current_trip' => null
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>