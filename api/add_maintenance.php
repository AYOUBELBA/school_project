<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

$truck_id = $data['truck_id'] ?? 0;
$maintenance_type = $data['maintenance_type'] ?? '';
$notes = $data['notes'] ?? '';
$reported_by = $data['reported_by'] ?? 0;

if (!$truck_id || !$maintenance_type) {
    echo json_encode(['success' => false, 'message' => 'Truck ID and maintenance type required']);
    exit();
}

try {
    // Insert maintenance request
    $sql = "INSERT INTO truck_maintenance (truck_id, maintenance_type, description, status, maintenance_date, created_at) 
            VALUES (:truck_id, :maintenance_type, :description, 'scheduled', CURRENT_DATE, CURRENT_TIMESTAMP)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':truck_id', $truck_id, PDO::PARAM_INT);
    $stmt->bindParam(':maintenance_type', $maintenance_type, PDO::PARAM_STR);
    $stmt->bindParam(':description', $notes, PDO::PARAM_STR);
    $stmt->execute();
    
    // Get truck info for notification message
    $truckSql = "SELECT plate_number FROM trucks WHERE id = :truck_id";
    $truckStmt = $db->prepare($truckSql);
    $truckStmt->bindParam(':truck_id', $truck_id);
    $truckStmt->execute();
    $truck = $truckStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get driver name
    $driverSql = "SELECT full_name FROM drivers WHERE id = :reported_by";
    $driverStmt = $db->prepare($driverSql);
    $driverStmt->bindParam(':reported_by', $reported_by);
    $driverStmt->execute();
    $driver = $driverStmt->fetch(PDO::FETCH_ASSOC);
    
    // Create notification for all admins and managers
    $title = "🔧 Maintenance Request";
    $message = "Driver " . ($driver['full_name'] ?? 'Unknown') . " requested maintenance for truck " . ($truck['plate_number'] ?? 'Unknown') . ": " . $maintenance_type;
    
    $notifSql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                 SELECT id, :title, :message, 'warning', CURRENT_TIMESTAMP 
                 FROM users WHERE role IN ('admin', 'manager')";
    $notifStmt = $db->prepare($notifSql);
    $notifStmt->bindParam(':title', $title);
    $notifStmt->bindParam(':message', $message);
    $notifStmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Maintenance request submitted']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>