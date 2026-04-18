<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../models/Notification.php';

$database = new Database();
$db = $database->getConnection();

if(!isset($_SESSION['user_id'])) {
    header("Location: ../signin.php");
    exit();
}

if($_SESSION['user_role'] == "driver"){
    header("Location: ../signin.php");
    exit();
}

$notificationModel = new Notification($db);
$userId = $_SESSION['user_id'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'mark_read':
                $notificationModel->markAsRead($_POST['id']);
                break;
            case 'mark_all_read':
                $notificationModel->markAllAsRead($userId);
                break;
            case 'delete':
                $notificationModel->delete($_POST['id']);
                break;
        }
        header("Location: notifications.php");
        exit();
    }
}

$notifications = $notificationModel->getAll($userId);
$unreadCount = $notificationModel->getUnreadCount($userId);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                <p class="text-gray-500">Stay updated with system alerts</p>
            </div>
            <?php if($unreadCount > 0): ?>
            <form method="POST" class="inline">
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="text-blue-600 hover:text-blue-700 text-sm">
                    Mark all as read
                </button>
            </form>
            <?php endif; ?>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <?php if(empty($notifications)): ?>
            <div class="p-12 text-center text-gray-500">
                <i class="ri-notification-off-line text-6xl mb-4"></i>
                <p>No notifications yet</p>
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-200">
                <?php foreach($notifications as $notification): ?>
                <div class="p-6 hover:bg-gray-50 transition <?php echo !$notification['is_read'] ? 'bg-blue-50' : ''; ?>">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <?php if(!$notification['is_read']): ?>
                                <span class="w-2 h-2 bg-blue-600 rounded-full"></span>
                                <?php endif; ?>
                                <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($notification['title']); ?></h3>
                                <?php
                                $typeColors = [
                                    'info' => 'bg-blue-100 text-blue-700',
                                    'warning' => 'bg-yellow-100 text-yellow-700',
                                    'alert' => 'bg-red-100 text-red-700',
                                    'success' => 'bg-green-100 text-green-700'
                                ];
                                $color = $typeColors[$notification['type']] ?? $typeColors['info'];
                                ?>
                                <span class="text-xs px-2 py-0.5 rounded-full <?php echo $color; ?>">
                                    <?php echo ucfirst($notification['type']); ?>
                                </span>
                            </div>
                            <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <p class="text-xs text-gray-400"><?php echo date('Y-m-d H:i', strtotime($notification['created_at'])); ?></p>
                        </div>
                        <div class="flex gap-2">
                            <?php if(!$notification['is_read']): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="mark_read">
                                <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                                <button type="submit" class="text-gray-400 hover:text-blue-600" title="Mark as read">
                                    <i class="ri-check-line"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                                <button type="submit" class="text-gray-400 hover:text-red-600" title="Delete">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </form>
                        </div>
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