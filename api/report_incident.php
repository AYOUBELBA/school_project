<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
session_start();

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
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Get company_id from truck
$truckSql = "SELECT company_id FROM trucks WHERE id = :truck_id";
$truckStmt = $db->prepare($truckSql);
$truckStmt->bindParam(':truck_id', $truck_id, PDO::PARAM_INT);
$truckStmt->execute();
$truck = $truckStmt->fetch(PDO::FETCH_ASSOC);

if (!$truck) {
    echo json_encode(['success' => false, 'message' => 'Truck not found']);
    exit();
}

$company_id = $truck['company_id'];

$incident_number = 'INC-' . date('Ymd') . '-' . rand(1000, 9999);

try {
    // First, check if incidents table has company_id column
    $checkColumn = "SELECT column_name FROM information_schema.columns WHERE table_name = 'incidents' AND column_name = 'company_id'";
    $checkStmt = $db->prepare($checkColumn);
    $checkStmt->execute();
    $hasCompanyId = $checkStmt->rowCount() > 0;
    
    if ($hasCompanyId) {
        $sql = "INSERT INTO incidents (incident_number, truck_id, driver_id, description, severity, location, incident_date, company_id, created_at) 
                VALUES (:incident_number, :truck_id, :driver_id, :description, :severity, :location, CURRENT_TIMESTAMP, :company_id, CURRENT_TIMESTAMP)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
    } else {
        $sql = "INSERT INTO incidents (incident_number, truck_id, driver_id, description, severity, location, incident_date, created_at) 
                VALUES (:incident_number, :truck_id, :driver_id, :description, :severity, :location, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $stmt = $db->prepare($sql);
    }
    
    $stmt->bindParam(':incident_number', $incident_number);
    $stmt->bindParam(':truck_id', $truck_id, PDO::PARAM_INT);
    $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':severity', $severity);
    $stmt->bindParam(':location', $location);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Incident reported', 'incident_number' => $incident_number]);
    
} catch (PDOException $e) {
    error_log("Incident error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>