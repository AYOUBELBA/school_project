<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$driver_id = $_GET['driver_id'] ?? 0;

$sql = "SELECT t.*, tr.plate_number as truck_plate 
        FROM trips t
        LEFT JOIN trucks tr ON t.truck_id = tr.id
        WHERE t.driver_id = :driver_id
        ORDER BY t.created_at DESC
        LIMIT 20";

$stmt = $db->prepare($sql);
$stmt->bindParam(':driver_id', $driver_id);
$stmt->execute();

$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'trips' => $trips
]);
?>