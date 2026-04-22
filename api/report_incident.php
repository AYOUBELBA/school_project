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
$driver_id = $data['driver_id'] ?? 0;
$description = $data['description'] ?? '';
$severity = $data['severity'] ?? 'medium';
$location = $data['location'] ?? '';

if (!$truck_id || !$driver_id || !$description) {
    echo json_encode(['success' => false, 'message' => 'Truck ID, driver ID, and description required']);
    exit();
}

try {
    // Get the actual user_id from the driver_id
    $userSql = "SELECT user_id FROM drivers WHERE id = :driver_id";
    $userStmt = $db->prepare($userSql);
    $userStmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    $user_id = $user['user_id'] ?? null;
    
    $sql = "INSERT INTO incidents (incident_number, truck_id, driver_id, description, severity, location, incident_date, reported_by, created_at) 
            VALUES (:incident_number, :truck_id, :driver_id, :description, :severity, :location, CURRENT_TIMESTAMP, :reported_by, CURRENT_TIMESTAMP)";
    
    $incident_number = 'INC-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':incident_number', $incident_number);
    $stmt->bindParam(':truck_id', $truck_id, PDO::PARAM_INT);
    $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':severity', $severity);
    $stmt->bindParam(':location', $location);
    $stmt->bindParam(':reported_by', $user_id);
    $stmt->execute();
    
    // Get truck info
    $truckSql = "SELECT plate_number FROM trucks WHERE id = :truck_id";
    $truckStmt = $db->prepare($truckSql);
    $truckStmt->bindParam(':truck_id', $truck_id);
    $truckStmt->execute();
    $truck = $truckStmt->fetch(PDO::FETCH_ASSOC);
    
    // Get driver name
    $driverSql = "SELECT full_name FROM drivers WHERE id = :driver_id";
    $driverStmt = $db->prepare($driverSql);
    $driverStmt->bindParam(':driver_id', $driver_id);
    $driverStmt->execute();
    $driver = $driverStmt->fetch(PDO::FETCH_ASSOC);
    
    // Determine notification type based on severity
    $title = $severity == 'critical' ? "🚨 CRITICAL INCIDENT" : ($severity == 'high' ? "⚠️ High Severity Incident" : "📢 Incident Reported");
    $notificationType = $severity == 'critical' ? 'alert' : ($severity == 'high' ? 'warning' : 'info');
    
    $message = "Driver " . ($driver['full_name'] ?? 'Unknown') . " reported an incident for truck " . ($truck['plate_number'] ?? 'Unknown') . ": " . substr($description, 0, 100);
    
    $notifSql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                 SELECT id, :title, :message, :type, CURRENT_TIMESTAMP 
                 FROM users WHERE role IN ('admin', 'manager')";
    $notifStmt = $db->prepare($notifSql);
    $notifStmt->bindParam(':title', $title);
    $notifStmt->bindParam(':message', $message);
    $notifStmt->bindParam(':type', $notificationType);
    $notifStmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Incident reported successfully', 'incident_number' => $incident_number]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>