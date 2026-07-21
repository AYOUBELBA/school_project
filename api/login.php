<?php
// api/login.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = "SELECT id, username, email, password_hash, role, company_id, is_active 
            FROM users 
            WHERE email = :email OR username = :email";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    if ($user['is_active'] != 1) {
        echo json_encode(['success' => false, 'message' => 'Account is deactivated']);
        exit();
    }
    
    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid password']);
        exit();
    }
    
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['company_id'] = $user['company_id'];
    $_SESSION['username'] = $user['username'];
    
    // Update last login
    $updateSql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id";
    $updateStmt = $db->prepare($updateSql);
    $updateStmt->bindParam(':id', $user['id']);
    $updateStmt->execute();
    
    // Get driver ID if user is a driver
    $driver_id = null;
    if ($user['role'] === 'driver') {
        $driverSql = "SELECT id FROM drivers WHERE user_id = :user_id";
        $driverStmt = $db->prepare($driverSql);
        $driverStmt->bindParam(':user_id', $user['id']);
        $driverStmt->execute();
        $driver = $driverStmt->fetch(PDO::FETCH_ASSOC);
        $driver_id = $driver ? (int)$driver['id'] : null;
        
        // Also get truck info if available
        $truckSql = "SELECT id FROM trucks WHERE current_driver_id = :driver_id";
        $truckStmt = $db->prepare($truckSql);
        $truckStmt->bindParam(':driver_id', $driver_id);
        $truckStmt->execute();
        $truck = $truckStmt->fetch(PDO::FETCH_ASSOC);
        $truck_id = $truck ? (int)$truck['id'] : null;
    }
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    
    // Build response
    $response = [
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'company_id' => (int)$user['company_id']
        ]
    ];
    
    // Add driver_id if user is a driver
    if ($user['role'] === 'driver') {
        $response['user']['driver_id'] = $driver_id;
        $response['user']['truck_id'] = $truck_id ?? null;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>