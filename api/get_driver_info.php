<?php
// api/get_driver_info.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
    
    // Get driver info with truck details
    $sql = "SELECT 
                d.id,
                d.full_name,
                d.phone,
                d.license_number,
                d.license_expiry,
                d.status,
                d.hire_date,
                d.address,
                d.is_in_truck,
                d.location_source,
                d.last_status_update,
                d.created_at,
                d.updated_at,
                d.company_id,
                t.id as truck_id,
                t.plate_number as truck_plate,
                t.model as truck_model,
                t.brand as truck_brand,
                u.id as user_id,
                u.username,
                u.email
            FROM drivers d
            LEFT JOIN trucks t ON d.id = t.current_driver_id
            LEFT JOIN users u ON d.user_id = u.id
            WHERE d.id = :driver_id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $driver = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$driver) {
        echo json_encode(['success' => false, 'message' => 'Driver not found']);
        exit();
    }
    
    // Get trip stats for this driver
    $statsSql = "SELECT 
                    COUNT(*) as total_trips,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_trips
                FROM trips 
                WHERE driver_id = :driver_id";
    
    $statsStmt = $db->prepare($statsSql);
    $statsStmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'driver' => [
            'id' => (int)$driver['id'],
            'full_name' => $driver['full_name'],
            'phone' => $driver['phone'],
            'license_number' => $driver['license_number'],
            'license_expiry' => $driver['license_expiry'],
            'status' => $driver['status'],
            'hire_date' => $driver['hire_date'],
            'address' => $driver['address'],
            'is_in_truck' => (bool)$driver['is_in_truck'],
            'location_source' => $driver['location_source'] ?? 'unknown',
            'last_status_update' => $driver['last_status_update'],
            'created_at' => $driver['created_at'],
            'updated_at' => $driver['updated_at'],
            'company_id' => (int)$driver['company_id'],
            'truck_id' => $driver['truck_id'] ? (int)$driver['truck_id'] : null,
            'truck_plate' => $driver['truck_plate'],
            'truck_model' => $driver['truck_model'],
            'truck_brand' => $driver['truck_brand'],
            'user_id' => $driver['user_id'] ? (int)$driver['user_id'] : null,
            'username' => $driver['username'],
            'email' => $driver['email'],
            'total_trips' => (int)($stats['total_trips'] ?? 0),
            'completed_trips' => (int)($stats['completed_trips'] ?? 0)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>