<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
session_start();

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$company_id = $_SESSION['company_id'] ?? 0;

$sql = "SELECT 
            t.id,
            t.plate_number,
            t.model,
            t.brand,
            t.status as truck_status,
            t.current_location_lat as latitude,
            t.current_location_lng as longitude,
            t.last_location_update,
            d.full_name as driver_name,
            d.id as driver_id,
            ts.status as trip_status,
            ts.trip_number,
            ts.route_id,
            (SELECT speed_kmh FROM truck_locations WHERE truck_id = t.id ORDER BY recorded_at DESC LIMIT 1) as speed_kmh
        FROM trucks t
        LEFT JOIN drivers d ON t.current_driver_id = d.id
        LEFT JOIN trips ts ON ts.truck_id = t.id AND ts.status IN ('planned', 'ongoing')
        WHERE t.company_id = :company_id
        ORDER BY t.id";

$stmt = $db->prepare($sql);
$stmt->bindParam(':company_id', $company_id);
$stmt->execute();
$trucks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get route information for trucks with active trips
foreach($trucks as &$truck) {
    if($truck['trip_status'] == 'ongoing' && $truck['route_id']) {
        $routeSql = "SELECT start_lat, start_lng, end_lat, end_lng, waypoints, 
                            start_point, end_point, estimated_distance_km
                     FROM routes WHERE id = :route_id";
        $routeStmt = $db->prepare($routeSql);
        $routeStmt->bindParam(':route_id', $truck['route_id']);
        $routeStmt->execute();
        $truck['route'] = $routeStmt->fetch(PDO::FETCH_ASSOC);
    }
    
    $truck['latitude'] = $truck['latitude'] ? floatval($truck['latitude']) : null;
    $truck['longitude'] = $truck['longitude'] ? floatval($truck['longitude']) : null;
    $truck['speed_kmh'] = $truck['speed_kmh'] ? floatval($truck['speed_kmh']) : 0;
    $truck['last_update'] = $truck['last_location_update'] ? date('H:i:s', strtotime($truck['last_location_update'])) : 'Never';
}

echo json_encode([
    'success' => true,
    'trucks' => $trucks,
    'count' => count($trucks),
    'timestamp' => date('Y-m-d H:i:s')
]);
?>