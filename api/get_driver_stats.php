<?php
// api/get_driver_stats.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get driver_id from request
$driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;

if ($driver_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid driver ID']);
    exit();
}

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Test the connection
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }
    
    // Get total trips for this driver
    $sql = "SELECT 
                COUNT(*) as total_trips,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_trips,
                COUNT(CASE WHEN status = 'ongoing' THEN 1 END) as ongoing_trips
            FROM trips 
            WHERE driver_id = :driver_id";
    
    $stmt = $db->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement']);
        exit();
    }
    
    $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to execute query']);
        exit();
    }
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Ensure we have values
    $result = [
        'success' => true,
        'total_trips' => isset($stats['total_trips']) ? (int)$stats['total_trips'] : 0,
        'completed_trips' => isset($stats['completed_trips']) ? (int)$stats['completed_trips'] : 0,
        'ongoing_trips' => isset($stats['ongoing_trips']) ? (int)$stats['ongoing_trips'] : 0
    ];
    
    echo json_encode($result);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>