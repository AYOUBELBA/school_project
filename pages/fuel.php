<?php
require_once '../includes/check_auth.php';

$company_id = $_SESSION['company_id'] ?? 0;

// Get fuel records ONLY for trucks in this company
$fuelSql = "SELECT f.*, t.plate_number as truck_plate
            FROM fuel_records f
            INNER JOIN trucks t ON f.truck_id = t.id
            WHERE t.company_id = :company_id
            ORDER BY f.fuel_date DESC, f.fuel_time DESC";
$fuelStmt = $db->prepare($fuelSql);
$fuelStmt->bindParam(':company_id', $company_id);
$fuelStmt->execute();
$fuelRecords = $fuelStmt->fetchAll(PDO::FETCH_ASSOC);

// Get fuel stats for this company
$statsSql = "SELECT 
                SUM(f.liters) as total_liters,
                SUM(f.total_cost) as total_cost,
                AVG(f.cost_per_liter) as avg_cost
             FROM fuel_records f
             INNER JOIN trucks t ON f.truck_id = t.id
             WHERE t.company_id = :company_id
             AND f.fuel_date >= DATE_TRUNC('month', CURRENT_DATE)";
$statsStmt = $db->prepare($statsSql);
$statsStmt->bindParam(':company_id', $company_id);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get trucks for this company for dropdown
$truckSql = "SELECT id, plate_number FROM trucks 
             WHERE status = 'active' AND company_id = :company_id 
             ORDER BY plate_number";
$truckStmt = $db->prepare($truckSql);
$truckStmt->bindParam(':company_id', $company_id);
$truckStmt->execute();
$trucks = $truckStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fuel Tracking | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Fuel Tracking</h2>
                <p class="text-gray-500">Monitor fuel consumption and costs</p>
            </div>
            <button onclick="openModal('addFuelModal')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <i class="ri-add-line"></i> Add Fuel Record
            </button>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Fuel (This Month)</p>
                        <h3 class="text-2xl font-bold mt-2"><?php echo number_format($stats['total_liters'] ?? 0, 0); ?> L</h3>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="ri-gas-station-line text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Cost (This Month)</p>
                        <h3 class="text-2xl font-bold mt-2">$<?php echo number_format($stats['total_cost'] ?? 0, 2); ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="ri-money-dollar-circle-line text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Average Cost/Liter</p>
                        <h3 class="text-2xl font-bold mt-2">$<?php echo number_format($stats['avg_cost'] ?? 0, 3); ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="ri-bar-chart-2-line text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Fuel Records Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Truck</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Liters</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cost/Liter</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Cost</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Odometer (km)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Station</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($fuelRecords as $record): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm"><?php echo $record['id']; ?></td>
                            <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($record['truck_plate']); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo number_format($record['liters'], 2); ?> L</td>
                            <td class="px-6 py-4 text-sm">$<?php echo number_format($record['cost_per_liter'], 3); ?></td>
                            <td class="px-6 py-4 text-sm">$<?php echo number_format($record['total_cost'], 2); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo number_format($record['odometer_km'], 0); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($record['fuel_station']); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo date('Y-m-d', strtotime($record['fuel_date'])); ?></td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="deleteFuel(<?php echo $record['id']; ?>)" class="text-red-600 hover:text-red-800">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                             </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Add Fuel Modal -->
<div id="addFuelModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Add Fuel Record</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="space-y-4">
                <select name="truck_id" required class="w-full px-3 py-2 border rounded-lg">
                    <option value="">Select Truck</option>
                    <?php foreach($trucks as $truck): ?>
                    <option value="<?php echo $truck['id']; ?>"><?php echo $truck['plate_number']; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" step="0.01" name="liters" placeholder="Liters" required class="w-full px-3 py-2 border rounded-lg">
                <input type="number" step="0.001" name="cost_per_liter" placeholder="Cost per Liter ($)" class="w-full px-3 py-2 border rounded-lg">
                <input type="number" step="0.01" name="total_cost" placeholder="Total Cost ($)" class="w-full px-3 py-2 border rounded-lg">
                <input type="number" name="odometer_km" placeholder="Odometer (km)" class="w-full px-3 py-2 border rounded-lg">
                <input type="text" name="fuel_station" placeholder="Fuel Station" class="w-full px-3 py-2 border rounded-lg">
                <input type="date" name="fuel_date" required class="w-full px-3 py-2 border rounded-lg">
                <input type="time" name="fuel_time" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('addFuelModal')" class="px-4 py-2 border rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
}

function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).classList.remove('flex');
}

function deleteFuel(id) {
    if(confirm('Are you sure you want to delete this fuel record?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
</body>
</html>