<?php
// api/dashboard_stats.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    require_once '../config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();

    // Get stats
    $stats = [];

    // Total trucks
    $query = "SELECT COUNT(*) as total FROM trucks WHERE company_id = :company_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':company_id', $_SESSION['company_id']);
    $stmt->execute();
    $stats['total_trucks'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Active drivers
    $query = "SELECT COUNT(*) as total FROM drivers WHERE status = 'available' AND company_id = :company_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':company_id', $_SESSION['company_id']);
    $stmt->execute();
    $stats['active_drivers'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Ongoing trips
    $query = "SELECT COUNT(*) as total FROM trips WHERE status = 'ongoing' AND company_id = :company_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':company_id', $_SESSION['company_id']);
    $stmt->execute();
    $stats['ongoing_trips'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Recent trips
    $query = "SELECT t.trip_number, t.status, 
              TO_CHAR(t.start_time, 'YYYY-MM-DD HH24:MI') as start_time,
              d.full_name as driver_name,
              tr.plate_number as truck_plate
              FROM trips t
              LEFT JOIN drivers d ON t.driver_id = d.id
              LEFT JOIN trucks tr ON t.truck_id = tr.id
              WHERE t.company_id = :company_id
              ORDER BY t.created_at DESC 
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':company_id', $_SESSION['company_id']);
    $stmt->execute();
    $stats['recent_trips'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($stats);

} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>