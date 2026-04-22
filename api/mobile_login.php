<?php
// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';


header('Content-Type: application/json');


$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$sql = "SELECT u.id, u.username, u.email, u.password_hash, u.role, 
        d.id as driver_id, d.full_name, d.license_number, d.phone,
        t.id as truck_id, t.plate_number as truck_plate
        FROM users u
        LEFT JOIN drivers d ON d.user_id = u.id
        LEFT JOIN trucks t ON t.current_driver_id = d.id
        WHERE u.email = :email AND u.role = 'driver' AND u.is_active = true";

$stmt = $db->prepare($sql);
$stmt->bindParam(':email', $email);
$stmt->execute();

if($stmt->rowCount() > 0) {
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(password_verify($password, $user['password_hash'])) {
        // Generate simple token (in production use JWT)
        $token = bin2hex(random_bytes(32));
        
        echo json_encode([
            'success' => true,
            'token' => $token,
            'user_id' => $user['id'],
            'driver_id' => $user['driver_id'],
            'truck_id' => $user['truck_id'],
            'name' => $user['full_name'],
            'truck_plate' => $user['truck_plate']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}
?>