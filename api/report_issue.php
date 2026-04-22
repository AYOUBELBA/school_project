<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

$driver_id = $data['driver_id'] ?? 0;
$truck_id = $data['truck_id'] ?? 0;
$description = $data['description'] ?? '';
$type = $data['type'] ?? 'issue';

if (!$driver_id || !$description) {
    echo json_encode(['success' => false, 'message' => 'Driver ID and description required']);
    exit();
}

try {
    // Get the actual user_id from the driver_id
    $userSql = "SELECT user_id FROM drivers WHERE id = :driver_id";
    $userStmt = $db->prepare($userSql);
    $userStmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !$user['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Driver not linked to a user account']);
        exit();
    }
    
    $user_id = $user['user_id'];
    
    $sql = "INSERT INTO incidents (incident_number, truck_id, driver_id, description, severity, reported_by, incident_date, created_at) 
            VALUES (:incident_number, :truck_id, :driver_id, :description, 'low', :reported_by, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
    
    $incident_number = 'ISS-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':incident_number', $incident_number);
    $stmt->bindParam(':truck_id', $truck_id, PDO::PARAM_INT);
    $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':reported_by', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Create notification for managers
    $notifSql = "INSERT INTO notifications (user_id, title, message, type, created_at) 
                 SELECT id, 'New Issue Reported', :message, 'warning', CURRENT_TIMESTAMP 
                 FROM users WHERE role IN ('admin', 'manager')";
    $notifStmt = $db->prepare($notifSql);
    $notifStmt->bindParam(':message', $description);
    $notifStmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Issue reported successfully', 'incident_number' => $incident_number]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>