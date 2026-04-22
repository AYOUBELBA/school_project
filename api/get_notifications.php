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

$user_id = $_SESSION['user_id'];
$limit = $_GET['limit'] ?? 10;

// Get unread count
$countSql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = :user_id AND is_read = false";
$countStmt = $db->prepare($countSql);
$countStmt->bindParam(':user_id', $user_id);
$countStmt->execute();
$unreadCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get recent notifications
$notifSql = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit";
$notifStmt = $db->prepare($notifSql);
$notifStmt->bindParam(':user_id', $user_id);
$notifStmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$notifStmt->execute();
$notifications = $notifStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'unread_count' => $unreadCount,
    'notifications' => $notifications
]);
?>