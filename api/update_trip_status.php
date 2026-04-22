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

$data = json_decode(file_get_contents('php://input'), true);

$trip_id = $data['trip_id'] ?? 0;
$status = $data['status'] ?? '';

if (!$trip_id || !$status) {
    echo json_encode(['success' => false, 'message' => 'Trip ID and status are required']);
    exit();
}

// Valid statuses
$valid_statuses = ['planned', 'ongoing', 'completed', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    $sql = "UPDATE trips SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :trip_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':trip_id', $trip_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // If trip is completed, update driver status back to available
    if ($status == 'completed') {
        $getDriverSql = "SELECT driver_id FROM trips WHERE id = :trip_id";
        $getDriverStmt = $db->prepare($getDriverSql);
        $getDriverStmt->bindParam(':trip_id', $trip_id, PDO::PARAM_INT);
        $getDriverStmt->execute();
        $trip = $getDriverStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($trip) {
            $updateDriverSql = "UPDATE drivers SET status = 'available' WHERE id = :driver_id";
            $updateDriverStmt = $db->prepare($updateDriverSql);
            $updateDriverStmt->bindParam(':driver_id', $trip['driver_id'], PDO::PARAM_INT);
            $updateDriverStmt->execute();
        }
    }
    
    // If trip is ongoing, update driver status to on_trip
    if ($status == 'ongoing') {
        $getDriverSql = "SELECT driver_id FROM trips WHERE id = :trip_id";
        $getDriverStmt = $db->prepare($getDriverSql);
        $getDriverStmt->bindParam(':trip_id', $trip_id, PDO::PARAM_INT);
        $getDriverStmt->execute();
        $trip = $getDriverStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($trip) {
            $updateDriverSql = "UPDATE drivers SET status = 'on_trip' WHERE id = :driver_id";
            $updateDriverStmt = $db->prepare($updateDriverSql);
            $updateDriverStmt->bindParam(':driver_id', $trip['driver_id'], PDO::PARAM_INT);
            $updateDriverStmt->execute();
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Trip status updated successfully']);
    
} catch (PDOException $e) {
    error_log("Update trip status error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>