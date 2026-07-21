<?php
/**
 * @var PDO $db
 */
require_once '../../includes/check_auth.php';

if($_SESSION['user_role'] !== 'super_admin') {
    header("Location: ../dashboard.php");
    exit();
}

// Get all activity logs
$sql = "SELECT al.*, u.username, u.email, c.name as company_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        LEFT JOIN companies c ON u.company_id = c.id
        ORDER BY al.created_at DESC
        LIMIT 200";
$stmt = $db->prepare($sql);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs | Super Admin</title>
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<?php include '../../includes/super_admin_sidebar.php'; ?>

<main class="ml-72 p-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Activity Logs</h1>
        <p class="text-gray-500">Track all system activities across all companies</p>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Company</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Entity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach($logs as $log): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm whitespace-nowrap"><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($log['username'] ?? 'System'); ?><br><span class="text-xs text-gray-400"><?php echo htmlspecialchars($log['email'] ?? ''); ?></span></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($log['company_name'] ?? 'N/A'); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($log['action']); ?></td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($log['entity_type']); ?> #<?php echo $log['entity_id']; ?></td>
                        <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($log['ip_address'] ?: '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html>