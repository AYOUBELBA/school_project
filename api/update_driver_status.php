<?php
// api/update_driver_status.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

$driver_id = isset($data['driver_id']) ? (int)$data['driver_id'] : 0;
$is_in_truck = isset($data['is_in_truck']) ? (bool)$data['is_in_truck'] : false;
$location_source = isset($data['location_source']) ? $data['location_source'] : 'phone_gps';

// Log the received data for debugging
error_log("Update driver status - driver_id: $driver_id, is_in_truck: " . ($is_in_truck ? 'true' : 'false'));

if ($driver_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid driver ID: ' . $driver_id]);
    exit();
}

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if driver exists first
    $checkSql = "SELECT id, is_in_truck FROM drivers WHERE id = :driver_id";
    $checkStmt = $db->prepare($checkSql);
    $checkStmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $checkStmt->execute();
    $driver = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$driver) {
        echo json_encode(['success' => false, 'message' => 'Driver not found with ID: ' . $driver_id]);
        exit();
    }
    
    // Update driver status
    $sql = "UPDATE drivers 
            SET is_in_truck = :is_in_truck, 
                location_source = :location_source,
                last_status_update = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :driver_id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':is_in_truck', $is_in_truck, PDO::PARAM_BOOL);
    $stmt->bindParam(':location_source', $location_source);
    $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Driver status updated successfully',
            'driver_id' => $driver_id,
            'is_in_truck' => $is_in_truck
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>