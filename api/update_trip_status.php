<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

$trip_id = $data['trip_id'] ?? 0;
$status = $data['status'] ?? '';
$driver_id = $data['driver_id'] ?? 0;

if (!$trip_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Trip ID and status required']);
    exit();
}

$valid_statuses = ['planned', 'ongoing', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Verify driver is assigned to this trip
    $checkSql = "SELECT driver_id FROM trips WHERE id = :trip_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':trip_id', $trip_id);
    $checkStmt->execute();
    $trip = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($trip && $trip['driver_id'] != $driver_id) {
        echo json_encode(['success' => false, 'message' => 'You are not assigned to this trip']);
        exit();
    }
    
    $sql = "UPDATE trips SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :trip_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':trip_id', $trip_id);
    $stmt->execute();
    
    // Update driver status based on trip status
    if ($status == 'ongoing') {
        $updateDriver = "UPDATE drivers SET status = 'on_trip' WHERE id = :driver_id";
        $driverStmt = $db->prepare($updateDriver);
        $driverStmt->bindParam(':driver_id', $driver_id);
        $driverStmt->execute();
    } elseif ($status == 'completed') {
        $updateDriver = "UPDATE drivers SET status = 'available' WHERE id = :driver_id";
        $driverStmt = $db->prepare($updateDriver);
        $driverStmt->bindParam(':driver_id', $driver_id);
        $driverStmt->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'Trip status updated']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>