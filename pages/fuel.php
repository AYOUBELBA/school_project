<?php
require_once '../includes/check_auth.php';

$company_id = $_SESSION['company_id'] ?? 0;
$db = db();

// Get selected month from query parameter (default to current month)
$selected_month = $_GET['month'] ?? date('Y-m');
$selected_date = $selected_month . '-01';

// Get available months with fuel records
$monthsSql = "SELECT DISTINCT TO_CHAR(f.fuel_date, 'YYYY-MM') as month_value,
                     TO_CHAR(f.fuel_date, 'FMMonth YYYY') as month_name
              FROM fuel_records f
              INNER JOIN trucks t ON f.truck_id = t.id
              WHERE t.company_id = :company_id
              ORDER BY month_value DESC";
$monthsStmt = $db->prepare($monthsSql);
$monthsStmt->bindParam(':company_id', $company_id);
$monthsStmt->execute();
$availableMonths = $monthsStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $truck_id = $_POST['truck_id'];
                $liters = $_POST['liters'];
                $cost_per_liter = $_POST['cost_per_liter'];
                $odometer_km = $_POST['odometer_km'] ?? null;
                $fuel_station = $_POST['fuel_station'] ?? '';
                $fuel_date = $_POST['fuel_date'];
                $fuel_time = $_POST['fuel_time'] ?? null;
                $invoice_number = trim($_POST['invoice_number'] ?? '');
                $created_by = $_SESSION['user_id'];
                $company_id = $_SESSION['company_id'];
                
                if(empty($invoice_number)) {
                    $_SESSION['flash_error'] = "Invoice number is required.";
                    header("Location: fuel.php?month=" . $selected_month);
                    exit();
                }
                
                // Check if invoice number already exists
                $checkSql = "SELECT id FROM fuel_records WHERE invoice_number = :invoice_number";
                $checkStmt = $db->prepare($checkSql);
                $checkStmt->bindParam(':invoice_number', $invoice_number);
                $checkStmt->execute();
                
                if($checkStmt->rowCount() > 0) {
                    $_SESSION['flash_error'] = "Invoice number '$invoice_number' already exists.";
                    header("Location: fuel.php?month=" . $selected_month);
                    exit();
                }
                
                $total_cost = $liters * $cost_per_liter;
                
                $insertSql = "INSERT INTO fuel_records (truck_id, company_id, liters, cost_per_liter, total_cost, odometer_km, fuel_station, fuel_date, fuel_time, invoice_number, created_by, created_at) 
                              VALUES (:truck_id, :company_id, :liters, :cost_per_liter, :total_cost, :odometer_km, :fuel_station, :fuel_date, :fuel_time, :invoice_number, :created_by, CURRENT_TIMESTAMP)";
                
                $insertStmt = $db->prepare($insertSql);
                $insertStmt->bindParam(':truck_id', $truck_id);
                $insertStmt->bindParam(':company_id', $company_id);
                $insertStmt->bindParam(':liters', $liters);
                $insertStmt->bindParam(':cost_per_liter', $cost_per_liter);
                $insertStmt->bindParam(':total_cost', $total_cost);
                $insertStmt->bindParam(':odometer_km', $odometer_km);
                $insertStmt->bindParam(':fuel_station', $fuel_station);
                $insertStmt->bindParam(':fuel_date', $fuel_date);
                $insertStmt->bindParam(':fuel_time', $fuel_time);
                $insertStmt->bindParam(':invoice_number', $invoice_number);
                $insertStmt->bindParam(':created_by', $created_by);
                $insertStmt->execute();
                
                $_SESSION['flash_success'] = "Fuel record added successfully!";
                header("Location: fuel.php?month=" . $selected_month);
                exit();
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $deleteSql = "DELETE FROM fuel_records WHERE id = :id";
                $deleteStmt = $db->prepare($deleteSql);
                $deleteStmt->bindParam(':id', $id);
                $deleteStmt->execute();
                
                $_SESSION['flash_success'] = "Fuel record deleted successfully!";
                header("Location: fuel.php?month=" . $selected_month);
                exit();
                break;
        }
    }
}

// Get flash messages
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Get fuel records for selected month
$fuelSql = "SELECT f.*, t.plate_number as truck_plate
            FROM fuel_records f
            INNER JOIN trucks t ON f.truck_id = t.id
            WHERE t.company_id = :company_id
            AND DATE_TRUNC('month', f.fuel_date) = DATE_TRUNC('month', :selected_date::date)
            ORDER BY f.fuel_date DESC, f.fuel_time DESC";
$fuelStmt = $db->prepare($fuelSql);
$fuelStmt->bindParam(':company_id', $company_id);
$fuelStmt->bindParam(':selected_date', $selected_date);
$fuelStmt->execute();
$fuelRecords = $fuelStmt->fetchAll(PDO::FETCH_ASSOC);

// Get fuel stats for selected month
$statsSql = "SELECT 
                SUM(f.liters) as total_liters,
                SUM(f.total_cost) as total_cost,
                AVG(f.cost_per_liter) as avg_cost,
                COUNT(*) as total_records,
                COUNT(DISTINCT f.truck_id) as trucks_used
             FROM fuel_records f
             INNER JOIN trucks t ON f.truck_id = t.id
             WHERE t.company_id = :company_id
             AND DATE_TRUNC('month', f.fuel_date) = DATE_TRUNC('month', :selected_date::date)";
$statsStmt = $db->prepare($statsSql);
$statsStmt->bindParam(':company_id', $company_id);
$statsStmt->bindParam(':selected_date', $selected_date);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Get trucks for dropdown
$truckSql = "SELECT id, plate_number FROM trucks 
             WHERE status = 'active' AND company_id = :company_id 
             ORDER BY plate_number";
$truckStmt = $db->prepare($truckSql);
$truckStmt->bindParam(':company_id', $company_id);
$truckStmt->execute();
$trucks = $truckStmt->fetchAll(PDO::FETCH_ASSOC);

// Get monthly trend data for chart
$trendSql = "SELECT 
                TO_CHAR(DATE_TRUNC('month', f.fuel_date), 'YYYY-MM') as month,
                SUM(f.liters) as total_liters,
                SUM(f.total_cost) as total_cost
             FROM fuel_records f
             INNER JOIN trucks t ON f.truck_id = t.id
             WHERE t.company_id = :company_id
             AND f.fuel_date >= DATE_TRUNC('year', CURRENT_DATE)
             GROUP BY DATE_TRUNC('month', f.fuel_date)
             ORDER BY month ASC";
$trendStmt = $db->prepare($trendSql);
$trendStmt->bindParam(':company_id', $company_id);
$trendStmt->execute();
$trendData = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
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
    <script>
        function calculateTotal() {
            const liters = document.getElementById('liters').value;
            const costPerLiter = document.getElementById('cost_per_liter').value;
            const totalCost = document.getElementById('total_cost');
            
            if(liters && costPerLiter) {
                totalCost.value = (parseFloat(liters) * parseFloat(costPerLiter)).toFixed(2);
            } else {
                totalCost.value = '';
            }
        }
        
        function checkInvoiceNumber() {
            const invoiceInput = document.getElementById('invoice_number');
            const invoiceValue = invoiceInput.value.trim();
            const submitBtn = document.querySelector('#addFuelModal button[type="submit"]');
            const errorDiv = document.getElementById('invoice_error');
            
            if(invoiceValue.length < 3) {
                if(errorDiv) errorDiv.remove();
                if(submitBtn) submitBtn.disabled = false;
                return;
            }
            
            fetch(`../api/check_invoice.php?invoice=${encodeURIComponent(invoiceValue)}`)
                .then(response => response.json())
                .then(data => {
                    if(data.exists) {
                        if(!errorDiv) {
                            const div = document.createElement('div');
                            div.id = 'invoice_error';
                            div.className = 'text-red-500 text-xs mt-1';
                            invoiceInput.parentNode.appendChild(div);
                        }
                        document.getElementById('invoice_error').innerHTML = '⚠️ Invoice number already exists!';
                        if(submitBtn) submitBtn.disabled = true;
                        invoiceInput.classList.add('border-red-500');
                    } else {
                        if(errorDiv) errorDiv.remove();
                        if(submitBtn) submitBtn.disabled = false;
                        invoiceInput.classList.remove('border-red-500');
                    }
                });
        }
        
        function changeMonth() {
            const month = document.getElementById('month_select').value;
            window.location.href = `fuel.php?month=${month}`;
        }
    </script>
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
        
        <?php if($flash_success): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg"><?php echo $flash_success; ?></div>
        <?php endif; ?>
        
        <?php if($flash_error): ?>
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg"><?php echo $flash_error; ?></div>
        <?php endif; ?>
        
        <!-- Month Filter -->
        <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-3">
                    <i class="ri-calendar-line text-xl text-gray-500"></i>
                    <span class="text-gray-600">Filter by Month:</span>
                    <select id="month_select" onchange="changeMonth()" class="px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php foreach($availableMonths as $month): ?>
                        <option value="<?php echo $month['month_value']; ?>" <?php echo $selected_month == $month['month_value'] ? 'selected' : ''; ?>>
                            <?php echo $month['month_name']; ?>
                        </option>
                        <?php endforeach; ?>
                        <?php if(empty($availableMonths)): ?>
                        <option value="<?php echo $selected_month; ?>"><?php echo date('F Y', strtotime($selected_date)); ?></option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="text-sm text-gray-500">
                    Showing data for: <span class="font-semibold text-gray-700"><?php echo date('F Y', strtotime($selected_date)); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Fuel</p>
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
                        <p class="text-gray-500 text-sm">Total Cost</p>
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
                        <p class="text-gray-500 text-sm">Avg Cost/Liter</p>
                        <h3 class="text-2xl font-bold mt-2">$<?php echo number_format($stats['avg_cost'] ?? 0, 3); ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="ri-bar-chart-2-line text-purple-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Fuel Records</p>
                        <h3 class="text-2xl font-bold mt-2"><?php echo $stats['total_records'] ?? 0; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="ri-file-list-line text-orange-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Tanks Filled</p>
                        <h3 class="text-2xl font-bold mt-2"><?php echo $stats['trucks_used'] ?? 0; ?></h3>
                    </div>
                    <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
                        <i class="ri-truck-line text-teal-600 text-2xl"></i>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Odometer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Station</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if(empty($fuelRecords)): ?>
                        <tr>
                            <td colspan="10" class="px-6 py-8 text-center text-gray-500">
                                No fuel records found for <?php echo date('F Y', strtotime($selected_date)); ?>.
                                Click "Add Fuel Record" to get started.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($fuelRecords as $record): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm"><?php echo $record['id']; ?></td>
                            <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($record['truck_plate']); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo number_format($record['liters'], 2); ?> L</td>
                            <td class="px-6 py-4 text-sm">$<?php echo number_format($record['cost_per_liter'], 3); ?></td>
                            <td class="px-6 py-4 text-sm">$<?php echo number_format($record['total_cost'], 2); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo number_format($record['odometer_km'], 0); ?> km</td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($record['fuel_station']); ?></td>
                            <td class="px-6 py-4 text-sm font-mono"><?php echo htmlspecialchars($record['invoice_number'] ?? '-'); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo date('Y-m-d', strtotime($record['fuel_date'])); ?></td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick="deleteFuel(<?php echo $record['id']; ?>)" class="text-red-600 hover:text-red-800">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
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

<!-- Add Fuel Modal (same as before) -->
<div id="addFuelModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Add Fuel Record</h3>
        <p class="text-sm text-gray-500 mb-4">Invoice number helps verify fuel purchases</p>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Truck *</label>
                    <select name="truck_id" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Truck</option>
                        <?php foreach($trucks as $truck): ?>
                        <option value="<?php echo $truck['id']; ?>"><?php echo $truck['plate_number']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Liters *</label>
                    <input type="number" step="0.01" id="liters" name="liters" placeholder="Liters" required oninput="calculateTotal()" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cost per Liter ($) *</label>
                    <input type="number" step="0.001" id="cost_per_liter" name="cost_per_liter" placeholder="Cost per Liter" required oninput="calculateTotal()" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Cost ($)</label>
                    <input type="number" step="0.01" id="total_cost" name="total_cost" readonly class="w-full px-3 py-2 border rounded-lg bg-gray-100 text-gray-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Number *</label>
                    <input type="text" name="invoice_number" id="invoice_number" placeholder="FACT-2024-001" required oninput="checkInvoiceNumber()" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Odometer (km)</label>
                    <input type="number" name="odometer_km" placeholder="Odometer reading" class="w-full px-3 py-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fuel Station</label>
                    <input type="text" name="fuel_station" placeholder="Station name" class="w-full px-3 py-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fuel Date *</label>
                    <input type="date" id="fuel_date" name="fuel_date" required class="w-full px-3 py-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fuel Time</label>
                    <input type="time" name="fuel_time" class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('addFuelModal')" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
    const today = new Date().toISOString().split('T')[0];
    const dateInput = document.getElementById('fuel_date');
    if(dateInput) dateInput.value = today;
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

window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.classList.add('hidden');
        event.target.classList.remove('flex');
    }
}
</script>
</body>
</html>