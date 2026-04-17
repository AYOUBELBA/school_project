<?php
// api/dashboard_stats.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
session_start();

require_once '../config/database.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get stats
$stats = [];

// Total trucks
$query = "SELECT COUNT(*) as total FROM trucks";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_trucks'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Active drivers
$query = "SELECT COUNT(*) as total FROM drivers WHERE status = 'available'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['active_drivers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Ongoing trips
$query = "SELECT COUNT(*) as total FROM trips WHERE status = 'ongoing'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['ongoing_trips'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent trips
$query = "SELECT t.trip_number, t.status, 
          TO_CHAR(t.start_time, 'YYYY-MM-DD HH24:MI') as start_time,
          d.full_name as driver_name,
          tr.model as truck_model
          FROM trips t
          LEFT JOIN drivers d ON t.driver_id = d.id
          LEFT JOIN trucks tr ON t.truck_id = tr.id
          ORDER BY t.created_at DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['recent_trips'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($stats);
?>