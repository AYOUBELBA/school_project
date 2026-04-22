<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);
$incident_id = $data['incident_id'] ?? 0;

if (!$incident_id) {
    echo json_encode(['success' => false, 'message' => 'Incident ID required']);
    exit();
}

try {
    $sql = "UPDATE incidents SET resolved_at = CURRENT_TIMESTAMP, resolution_notes = 'Resolved by admin' WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $incident_id, PDO::PARAM_INT);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Incident resolved']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>