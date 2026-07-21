<?php
require_once '../includes/check_auth.php';
require_once '../config/session.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if(!isset($_SESSION['user_id'])) {
    header("Location: ../signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark all as read if requested
if(isset($_GET['mark_all'])) {
    $updateSql = "UPDATE notifications SET is_read = true WHERE user_id = :user_id";
    $updateStmt = $db->prepare($updateSql);
    $updateStmt->bindParam(':user_id', $user_id);
    $updateStmt->execute();
    header("Location: notifications.php");
    exit();
}

// Get all notifications
$sql = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Fleet System</title>
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Notifications</h2>
                <p class="text-gray-500">All your system notifications</p>
            </div>
            <a href="?mark_all=1" class="text-sm text-blue-600 hover:text-blue-800">Mark all as read</a>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <?php if(empty($notifications)): ?>
            <div class="p-12 text-center text-gray-500">
                <i class="ri-notification-off-line text-6xl mb-4 block"></i>
                <p>No notifications yet</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-200">
                <?php foreach($notifications as $notif): ?>
                <div class="p-4 hover:bg-gray-50 transition <?php echo !$notif['is_read'] ? 'bg-blue-50' : ''; ?>">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <?php if(!$notif['is_read']): ?>
                                <span class="w-2 h-2 bg-blue-600 rounded-full"></span>
                                <?php endif; ?>
                                <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($notif['title']); ?></h3>
                                <?php
                                $typeColors = [
                                    'info' => 'bg-blue-100 text-blue-700',
                                    'warning' => 'bg-yellow-100 text-yellow-700',
                                    'alert' => 'bg-red-100 text-red-700',
                                    'success' => 'bg-green-100 text-green-700'
                                ];
                                $color = $typeColors[$notif['type']] ?? $typeColors['info'];
                                ?>
                                <span class="text-xs px-2 py-0.5 rounded-full <?php echo $color; ?>">
                                    <?php echo ucfirst($notif['type']); ?>
                                </span>
                            </div>
                            <p class="text-gray-600 mb-2"><?php echo htmlspecialchars($notif['message']); ?></p>
                            <p class="text-xs text-gray-400"><?php echo date('F j, Y g:i A', strtotime($notif['created_at'])); ?></p>
                        </div>
                        <?php if(!$notif['is_read']): ?>
                        <a href="../api/mark_notification_read.php?notif_id=<?php echo $notif['id']; ?>" class="text-gray-400 hover:text-blue-600">
                            <i class="ri-check-line text-xl"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>