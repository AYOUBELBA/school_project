<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

$trip_id = isset($data['trip_id']) ? (int)$data['trip_id'] : 0;
$driver_id = isset($data['driver_id']) ? (int)$data['driver_id'] : 0;
$receiver_name = $data['receiver_name'] ?? '';
$delivery_notes = $data['delivery_notes'] ?? '';

if ($trip_id <= 0 || $driver_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid trip or driver ID']);
    exit();
}

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // First, get current notes
    $getNotesSql = "SELECT notes FROM trips WHERE id = :trip_id";
    $getNotesStmt = $db->prepare($getNotesSql);
    $getNotesStmt->bindParam(':trip_id', $trip_id, PDO::PARAM_INT);
    $getNotesStmt->execute();
    $currentNotes = $getNotesStmt->fetch(PDO::FETCH_ASSOC);
    
    $existingNotes = $currentNotes['notes'] ?? '';
    $newNotes = $existingNotes;
    
    // Add delivery notes if provided
    if (!empty($delivery_notes)) {
        $newNotes = $existingNotes ? $existingNotes . ' | Delivery Notes: ' . $delivery_notes : 'Delivery Notes: ' . $delivery_notes;
    }
    
    // Add receiver name if provided
    if (!empty($receiver_name)) {
        $newNotes = $newNotes ? $newNotes . ' | Receiver: ' . $receiver_name : 'Receiver: ' . $receiver_name;
    }
    
    // Update trip status to completed
    $sql = "UPDATE trips 
            SET status = 'completed', 
                end_time = CURRENT_TIMESTAMP,
                notes = :notes
            WHERE id = :trip_id AND driver_id = :driver_id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':trip_id', $trip_id, PDO::PARAM_INT);
    $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $stmt->bindParam(':notes', $newNotes);
    
    if (!$stmt->execute()) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to complete trip']);
        exit();
    }
    
    // Update driver status back to available
    $updateDriverSql = "UPDATE drivers SET status = 'available', is_in_truck = false WHERE id = :driver_id";
    $updateStmt = $db->prepare($updateDriverSql);
    $updateStmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $updateStmt->execute();
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Trip completed successfully',
        'trip_id' => $trip_id
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>