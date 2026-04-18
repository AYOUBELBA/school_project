<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
session_start();

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get filters
$status_filter = $_GET['status'] ?? 'all';
$trip_filter = $_GET['trip_status'] ?? 'all';
$driver_filter = $_GET['driver'] ?? 'all';
$truck_filter = $_GET['truck'] ?? 'all';

// Build query
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
            (SELECT speed_kmh FROM truck_locations WHERE truck_id = t.id ORDER BY recorded_at DESC LIMIT 1) as speed_kmh
        FROM trucks t
        LEFT JOIN drivers d ON t.current_driver_id = d.id
        LEFT JOIN trips ts ON ts.truck_id = t.id AND ts.status IN ('planned', 'ongoing')
        WHERE 1=1";

$params = [];

if($status_filter !== 'all') {
    $sql .= " AND t.status = :status";
    $params[':status'] = $status_filter;
}

if($trip_filter !== 'all') {
    $sql .= " AND ts.status = :trip_status";
    $params[':trip_status'] = $trip_filter;
}

if($driver_filter !== 'all') {
    $sql .= " AND d.id = :driver_id";
    $params[':driver_id'] = $driver_filter;
}

if($truck_filter !== 'all') {
    $sql .= " AND t.id = :truck_id";
    $params[':truck_id'] = $truck_filter;
}

$sql .= " ORDER BY t.id";

$stmt = $db->prepare($sql);
foreach($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();

$trucks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Format response
foreach($trucks as &$truck) {
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