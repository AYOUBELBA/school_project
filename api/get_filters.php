<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get drivers
$driverSql = "SELECT id, full_name FROM drivers ORDER BY full_name";
$driverStmt = $db->prepare($driverSql);
$driverStmt->execute();
$drivers = $driverStmt->fetchAll(PDO::FETCH_ASSOC);

// Get trucks
$truckSql = "SELECT id, plate_number, model FROM trucks ORDER BY plate_number";
$truckStmt = $db->prepare($truckSql);
$truckStmt->execute();
$trucks = $truckStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'drivers' => $drivers,
    'trucks' => $trucks
]);
?>