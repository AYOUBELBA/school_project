<?php
require_once '../includes/check_auth.php';
require_once '../config/session.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../signin.php");
    exit();
}

// Check if user is admin
$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
if($role !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Get filters
$search = $_GET['search'] ?? '';
$action_filter = $_GET['action'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$query = "SELECT al.*, u.username, u.email 
          FROM activity_logs al
          LEFT JOIN users u ON al.user_id = u.id
          WHERE 1=1";
$params = [];

if(!empty($search)) {
    $query .= " AND (u.username ILIKE :search OR al.action ILIKE :search OR al.entity_type ILIKE :search)";
    $params[':search'] = "%$search%";
}

if(!empty($action_filter)) {
    $query .= " AND al.action = :action";
    $params[':action'] = $action_filter;
}

if(!empty($date_from)) {
    $query .= " AND DATE(al.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if(!empty($date_to)) {
    $query .= " AND DATE(al.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

$query .= " ORDER BY al.created_at DESC LIMIT 200";

$stmt = $db->prepare($query);
foreach($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique actions for filter dropdown
$actionQuery = "SELECT DISTINCT action FROM activity_logs ORDER BY action";
$actionStmt = $db->prepare($actionQuery);
$actionStmt->execute();
$actions = $actionStmt->fetchAll(PDO::FETCH_COLUMN);

// Get stats
$statsQuery = "SELECT 
                COUNT(*) as total,
                COUNT(DISTINCT user_id) as unique_users,
                DATE_TRUNC('day', created_at) as date
               FROM activity_logs 
               WHERE created_at >= NOW() - INTERVAL '7 days'
               GROUP BY DATE_TRUNC('day', created_at)
               ORDER BY date DESC";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute();
$weekly_stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .action-badge { display: inline-flex; align-items: center; padding: 0.2rem 0.6rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 500; }
        .action-create { background: #d1fae5; color: #065f46; }
        .action-update { background: #dbeafe; color: #1e40af; }
        .action-delete { background: #fee2e2; color: #991b1b; }
        .action-login { background: #e0e7ff; color: #3730a3; }
        .action-logout { background: #fed7aa; color: #92400e; }
        .action-default { background: #f3f4f6; color: #374151; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Activity Logs</h2>
            <p class="text-gray-500">Track all system activities and user actions</p>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Activities</p>
                        <h3 class="text-2xl font-bold mt-2"><?php echo count($logs); ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="ri-history-line text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Unique Users</p>
                        <h3 class="text-2xl font-bold mt-2">
                            <?php echo count(array_unique(array_column($logs, 'user_id'))); ?>
                        </h3>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="ri-user-line text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Last 7 Days</p>
                        <h3 class="text-2xl font-bold mt-2">
                            <?php echo array_sum(array_column($weekly_stats, 'total')); ?>
                        </h3>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="ri-calendar-line text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Most Active</p>
                        <h3 class="text-xl font-bold mt-2 truncate">
                            <?php 
                            $usernames = array_filter(array_column($logs, 'username'), function($value) {
                                return !empty($value) && (is_string($value) || is_int($value));
                            });
                            $user_counts = !empty($usernames) ? array_count_values($usernames) : [];
                            arsort($user_counts);
                            $top_user = !empty($user_counts) ? key($user_counts) : 'N/A';
                            ?>
                        </h3>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="ri-star-line text-orange-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="text" name="search" placeholder="Search logs..." value="<?php echo htmlspecialchars($search); ?>" class="px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <select name="action" class="px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Actions</option>
                    <?php foreach($actions as $action): ?>
                    <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $action_filter == $action ? 'selected' : ''; ?>>
                        <?php echo ucfirst($action); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="From Date" class="px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="To Date" class="px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="md:col-span-4 flex justify-end gap-2">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Apply Filters</button>
                    <a href="activity_logs.php" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Reset</a>
                    <button type="button" onclick="exportLogs()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Export CSV</button>
                </div>
            </form>
        </div>
        
        <!-- Logs Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Entity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User Agent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if(empty($logs)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                <i class="ri-inbox-line text-4xl mb-2 block"></i>
                                No activity logs found
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($logs as $log): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm whitespace-nowrap">
                                    <?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="font-medium"><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($log['email'] ?? ''); ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <?php
                                    $action_class = 'action-default';
                                    if(strpos($log['action'], 'create') !== false) $action_class = 'action-create';
                                    elseif(strpos($log['action'], 'update') !== false) $action_class = 'action-update';
                                    elseif(strpos($log['action'], 'delete') !== false) $action_class = 'action-delete';
                                    elseif(strpos($log['action'], 'login') !== false) $action_class = 'action-login';
                                    elseif(strpos($log['action'], 'logout') !== false) $action_class = 'action-logout';
                                    ?>
                                    <span class="action-badge <?php echo $action_class; ?>">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <?php if($log['entity_type']): ?>
                                    <span class="text-gray-600"><?php echo htmlspecialchars($log['entity_type']); ?></span>
                                    <span class="text-gray-400">#<?php echo $log['entity_id']; ?></span>
                                    <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($log['ip_address'] ?: '-'); ?> </td>
                                <td class="px-6 py-4 text-sm text-gray-500 truncate max-w-xs" title="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                    <?php echo htmlspecialchars(substr($log['user_agent'], 0, 50) . (strlen($log['user_agent']) > 50 ? '...' : '')); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script>
function exportLogs() {
    // Simple CSV export
    const table = document.querySelector('table');
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    for (let row of rows) {
        const cells = row.querySelectorAll('th, td');
        const rowData = Array.from(cells).map(cell => cell.innerText.trim());
        csv.push(rowData.join(','));
    }
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `activity_logs_<?php echo date('Y-m-d'); ?>.csv`;
    a.click();
    URL.revokeObjectURL(url);
}
</script>
</body>
</html>