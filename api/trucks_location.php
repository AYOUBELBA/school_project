<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
session_start();

require_once '../config/database.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$company_id = $_SESSION['company_id'] ?? 0;

// Get trucks ONLY for this company
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
            (SELECT speed_kmh FROM truck_locations WHERE truck_id = t.id ORDER BY recorded_at DESC LIMIT 1) as speed_kmh
        FROM trucks t
        LEFT JOIN drivers d ON t.current_driver_id = d.id
        WHERE t.company_id = :company_id
        ORDER BY t.id";

$stmt = $db->prepare($sql);
$stmt->bindParam(':company_id', $company_id);
$stmt->execute();
$trucks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'trucks' => $trucks,
    'count' => count($trucks),
    'company_id' => $company_id
]);
?>