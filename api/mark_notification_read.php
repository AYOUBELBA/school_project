<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
session_start();

require_once '../config/database.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'] ?? 0;

if(!$notification_id) {
    echo json_encode(['success' => false, 'message' => 'Notification ID required']);
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "UPDATE notifications SET is_read = true WHERE id = :id AND user_id = :user_id";
$stmt = $db->prepare($sql);
$stmt->bindParam(':id', $notification_id);
$stmt->bindParam(':user_id', $user_id);

if($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>