<?php
require_once '../includes/check_auth.php';
$db = db();
$company_id = $_SESSION['company_id'];

// Get sort parameters
$sort = $_GET['sort'] ?? 'performance_score';
$order = $_GET['order'] ?? 'DESC';

// Allowed sort columns (prevent SQL injection)
$allowedSorts = ['full_name', 'total_trips', 'completed_trips', 'completion_rate', 'avg_trip_minutes', 'performance_score'];
$sort = in_array($sort, $allowedSorts) ? $sort : 'performance_score';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

// Get driver performance metrics
$sql = "SELECT 
    d.id,
    d.full_name,
    d.phone,
    d.status,
    d.hire_date,
    COUNT(DISTINCT t.id) as total_trips,
    COUNT(CASE WHEN t.status = 'completed' THEN 1 END) as completed_trips,
    COUNT(CASE WHEN t.status = 'cancelled' THEN 1 END) as cancelled_trips,
    ROUND(AVG(EXTRACT(EPOCH FROM (COALESCE(t.end_time, CURRENT_TIMESTAMP) - t.start_time)) / 60)::numeric, 0) as avg_trip_minutes,
    COALESCE(SUM(f.total_cost), 0) as total_fuel_cost,
    ROUND(AVG(f.liters), 2) as avg_fuel_liters,
    ROUND(
        (COUNT(CASE WHEN t.status = 'completed' THEN 1 END)::decimal / 
        NULLIF(COUNT(DISTINCT t.id), 0) * 100), 0
    ) as completion_rate,
    ROUND(
        (COUNT(DISTINCT t.id)::decimal / 
        NULLIF((CURRENT_DATE - d.hire_date)::decimal, 0) * 30), 2
    ) as trips_per_month,
    ROUND(
        CASE 
            WHEN COUNT(DISTINCT t.id) > 0 AND COUNT(CASE WHEN t.status = 'completed' THEN 1 END) > 0 
            THEN (COUNT(CASE WHEN t.status = 'completed' THEN 1 END)::decimal / NULLIF(COUNT(DISTINCT t.id), 0)) * 70 +
                 (CASE WHEN AVG(EXTRACT(EPOCH FROM (COALESCE(t.end_time, CURRENT_TIMESTAMP) - t.start_time)) / 60) < 120 THEN 30 ELSE 0 END)
            ELSE 0
        END, 0
    ) as performance_score
FROM drivers d
LEFT JOIN trips t ON t.driver_id = d.id
LEFT JOIN fuel_records f ON f.truck_id = t.truck_id
WHERE d.company_id = :company_id
GROUP BY d.id, d.full_name, d.phone, d.status, d.hire_date
ORDER BY $sort $order";

$stmt = $db->prepare($sql);
$stmt->bindParam(':company_id', $company_id);
$stmt->execute();
$drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get column names for sorting
$columns = [
    'full_name' => 'Driver',
    'total_trips' => 'Trips',
    'completed_trips' => 'Completed',
    'completion_rate' => 'Rate',
    'avg_trip_minutes' => 'Avg Trip',
    'performance_score' => 'Score'
];

// Helper function to get sort icon
function getSortIcon($column, $currentSort, $currentOrder) {
    if ($column !== $currentSort) {
        return '↕';
    }
    return $currentOrder === 'ASC' ? '↑' : '↓';
}

// Helper function to get next order
function getNextOrder($column, $currentSort, $currentOrder) {
    if ($column !== $currentSort) {
        return 'ASC';
    }
    return $currentOrder === 'ASC' ? 'DESC' : 'ASC';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Performance | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .score-bar { height: 8px; border-radius: 4px; background: #e5e7eb; overflow: hidden; }
        .score-fill { height: 100%; border-radius: 4px; }
        .sortable { cursor: pointer; transition: color 0.2s; }
        .sortable:hover { color: #3758f9; }
        .rank-number { 
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-weight: 600;
            font-size: 13px;
            background: #f3f4f6;
            color: #6b7280;
        }
        .rank-number.top1 { background: #fbbf24; color: #78350f; }
        .rank-number.top2 { background: #9ca3af; color: #1f2937; }
        .rank-number.top3 { background: #d97706; color: #fef3c7; }
        tr:hover .rank-number { background: #dbeafe; }
        tr:hover .rank-number.top1 { background: #f59e0b; }
        tr:hover .rank-number.top2 { background: #8b8b8b; }
        tr:hover .rank-number.top3 { background: #b45309; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Driver Performance</h2>
                <p class="text-gray-500">Driver efficiency and performance metrics</p>
            </div>
            <button onclick="window.print()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                <i class="ri-printer-line"></i> Print Report
            </button>
        </div>
        
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                            <?php foreach($columns as $col => $label): ?>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <a href="?sort=<?php echo $col; ?>&order=<?php echo getNextOrder($col, $sort, $order); ?>" class="sortable flex items-center gap-1 hover:text-blue-600">
                                    <?php echo $label; ?>
                                    <span class="text-gray-400 text-sm"><?php echo getSortIcon($col, $sort, $order); ?></span>
                                </a>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if(empty($drivers)): ?>
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">
                                <i class="ri-user-line text-4xl mb-2 block"></i>
                                No driver data available
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($drivers as $index => $driver): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-150">
                            <td class="px-4 py-3">
                                <?php if($index == 0): ?>
                                <span class="rank-number top1">1</span>
                                <?php elseif($index == 1): ?>
                                <span class="rank-number top2">2</span>
                                <?php elseif($index == 2): ?>
                                <span class="rank-number top3">3</span>
                                <?php else: ?>
                                <span class="rank-number"><?php echo $index + 1; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800"><?php echo htmlspecialchars($driver['full_name']); ?></div>
                                <div class="text-xs text-gray-500">
                                    <?php echo htmlspecialchars($driver['phone']); ?>
                                    <span class="ml-2 px-2 py-0.5 text-xs rounded-full <?php 
                                        echo $driver['status'] === 'available' ? 'bg-green-100 text-green-700' : 
                                            ($driver['status'] === 'on_trip' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700'); ?>">
                                        <?php echo str_replace('_', ' ', $driver['status']); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium"><?php echo $driver['total_trips']; ?></td>
                            <td class="px-4 py-3 text-sm font-medium"><?php echo $driver['completed_trips']; ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php 
                                    echo $driver['completion_rate'] >= 90 ? 'bg-green-100 text-green-700' : 
                                        ($driver['completion_rate'] >= 70 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'); ?>">
                                    <?php echo $driver['completion_rate']; ?>%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm"><?php echo $driver['avg_trip_minutes']; ?> min</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="score-bar flex-1 min-w-[60px]">
                                        <div class="score-fill <?php 
                                            echo $driver['performance_score'] >= 80 ? 'bg-green-500' : 
                                                ($driver['performance_score'] >= 60 ? 'bg-yellow-500' : 'bg-red-500'); ?>" 
                                            style="width: <?php echo min($driver['performance_score'], 100); ?>%;">
                                        </div>
                                    </div>
                                    <span class="text-sm font-bold w-12 text-right <?php 
                                        echo $driver['performance_score'] >= 80 ? 'text-green-600' : 
                                            ($driver['performance_score'] >= 60 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                        <?php echo round($driver['performance_score']); ?>%
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
            <?php 
            $totalDrivers = count($drivers);
            $avgCompletion = $totalDrivers > 0 ? round(array_sum(array_column($drivers, 'completion_rate')) / $totalDrivers) : 0;
            $avgScore = $totalDrivers > 0 ? round(array_sum(array_column($drivers, 'performance_score')) / $totalDrivers) : 0;
            $totalTrips = array_sum(array_column($drivers, 'total_trips'));
            ?>
            <div class="bg-white rounded-xl p-4 shadow-sm text-center">
                <div class="text-2xl font-bold text-blue-600"><?php echo $totalDrivers; ?></div>
                <div class="text-xs text-gray-500">Total Drivers</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm text-center">
                <div class="text-2xl font-bold text-green-600"><?php echo $avgCompletion; ?>%</div>
                <div class="text-xs text-gray-500">Avg Completion Rate</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm text-center">
                <div class="text-2xl font-bold text-purple-600"><?php echo round($avgScore); ?>%</div>
                <div class="text-xs text-gray-500">Avg Performance Score</div>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm text-center">
                <div class="text-2xl font-bold text-orange-600"><?php echo $totalTrips; ?></div>
                <div class="text-xs text-gray-500">Total Trips Completed</div>
            </div>
        </div>
    </div>
</main>
</body>
</html>