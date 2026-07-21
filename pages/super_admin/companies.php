<?php
/**
 * @var PDO $db
 */
require_once '../../includes/check_auth.php';

if($_SESSION['user_role'] !== 'super_admin') {
    header("Location: ../dashboard.php");
    exit();
}

// Handle company creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if($_POST['action'] === 'create_company') {
        $name = $_POST['name'];
        $plan = $_POST['plan'];
        
        $max_drivers = $plan === 'standard' ? 5 : ($plan === 'premium' ? 20 : 999999);
        $max_trucks = $plan === 'standard' ? 5 : ($plan === 'premium' ? 20 : 999999);
        $max_managers = $plan === 'standard' ? 2 : ($plan === 'premium' ? 5 : 999999);
        
        $sql = "INSERT INTO companies (name, subscription_plan, max_drivers, max_trucks, max_managers, subscription_status, created_at) 
                VALUES (:name, :plan, :max_drivers, :max_trucks, :max_managers, 'active', CURRENT_TIMESTAMP)";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':plan', $plan);
        $stmt->bindParam(':max_drivers', $max_drivers);
        $stmt->bindParam(':max_trucks', $max_trucks);
        $stmt->bindParam(':max_managers', $max_managers);
        $stmt->execute();
        
        $_SESSION['flash_success'] = "Company created successfully";
        header("Location: companies.php");
        exit();
    }
}

// Get flash messages
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Get all companies
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM users WHERE company_id = c.id) as user_count,
        (SELECT COUNT(*) FROM trucks WHERE company_id = c.id) as truck_count,
        (SELECT COUNT(*) FROM drivers WHERE company_id = c.id) as driver_count,
        u.username as owner_name
        FROM companies c
        LEFT JOIN users u ON c.owner_id = u.id
        ORDER BY c.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute();
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Companies | Super Admin</title>
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<?php include '../../includes/super_admin_sidebar.php'; ?>

<main class="ml-72 p-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">All Companies</h1>
            <p class="text-gray-500">Manage all companies on the platform</p>
        </div>
        <button onclick="openModal('addCompanyModal')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            + Add Company
        </button>
    </div>
    
    <?php if($flash_success): ?>
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg"><?php echo $flash_success; ?></div>
    <?php endif; ?>
    
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Company Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Owner</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Plan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Usage</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Joined</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach($companies as $company): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm"><?php echo $company['id']; ?></td>
                    <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($company['name']); ?></td>
                    <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($company['owner_name'] ?? 'N/A'); ?></td>
                    <td class="px-6 py-4 text-sm capitalize"><?php echo $company['subscription_plan']; ?></td>
                    <td class="px-6 py-4 text-sm">
                        👤 <?php echo $company['user_count']; ?> | 
                        🚛 <?php echo $company['truck_count']; ?> | 
                        👨 <?php echo $company['driver_count']; ?>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full <?php echo $company['subscription_status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo ucfirst($company['subscription_status']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm"><?php echo date('Y-m-d', strtotime($company['created_at'])); ?></td>
                    <td class="px-6 py-4 text-sm">
                        <button onclick="editCompany(<?php echo htmlspecialchars(json_encode($company)); ?>)" class="text-blue-600 hover:text-blue-800 mr-2">Edit</button>
                        <button onclick="toggleCompany(<?php echo $company['id']; ?>, '<?php echo $company['subscription_status']; ?>')" class="text-orange-600 hover:text-orange-800 mr-2">Toggle</button>
                        <button onclick="deleteCompany(<?php echo $company['id']; ?>, '<?php echo addslashes($company['name']); ?>')" class="text-red-600 hover:text-red-800">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Add Company Modal -->
<div id="addCompanyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Add New Company</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create_company">
            <div class="space-y-4">
                <input type="text" name="name" placeholder="Company Name" required class="w-full px-3 py-2 border rounded-lg">
                <select name="plan" class="w-full px-3 py-2 border rounded-lg">
                    <option value="standard">Standard - $49/mo (5 drivers, 5 trucks)</option>
                    <option value="premium">Premium - $99/mo (20 drivers, 20 trucks)</option>
                    <option value="extra">Extra - $199/mo (Unlimited)</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('addCompanyModal')" class="px-4 py-2 border rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Company Modal -->
<div id="editCompanyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Edit Company</h3>
        <form id="editCompanyForm">
            <input type="hidden" id="edit_company_id">
            <div class="space-y-4">
                <input type="text" id="edit_company_name" class="w-full px-3 py-2 border rounded-lg bg-gray-100" readonly>
                <select id="edit_company_plan" class="w-full px-3 py-2 border rounded-lg">
                    <option value="standard">Standard - $49/mo (5 drivers, 5 trucks)</option>
                    <option value="premium">Premium - $99/mo (20 drivers, 20 trucks)</option>
                    <option value="extra">Extra - $199/mo (Unlimited)</option>
                </select>
                <input type="number" id="edit_max_drivers" placeholder="Max Drivers" class="w-full px-3 py-2 border rounded-lg">
                <input type="number" id="edit_max_trucks" placeholder="Max Trucks" class="w-full px-3 py-2 border rounded-lg">
                <input type="number" id="edit_max_managers" placeholder="Max Managers" class="w-full px-3 py-2 border rounded-lg">
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('editCompanyModal')" class="px-4 py-2 border rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Update</button>
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

function editCompany(company) {
    document.getElementById('edit_company_id').value = company.id;
    document.getElementById('edit_company_name').value = company.name;
    document.getElementById('edit_company_plan').value = company.subscription_plan;
    document.getElementById('edit_max_drivers').value = company.max_drivers;
    document.getElementById('edit_max_trucks').value = company.max_trucks;
    document.getElementById('edit_max_managers').value = company.max_managers;
    openModal('editCompanyModal');
}

document.getElementById('editCompanyForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const companyId = document.getElementById('edit_company_id').value;
    const plan = document.getElementById('edit_company_plan').value;
    const maxDrivers = document.getElementById('edit_max_drivers').value;
    const maxTrucks = document.getElementById('edit_max_trucks').value;
    const maxManagers = document.getElementById('edit_max_managers').value;
    
    const response = await fetch('../../api/super_admin/update_company_plan.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ company_id: companyId, plan, max_drivers: maxDrivers, max_trucks: maxTrucks, max_managers: maxManagers })
    });
    const data = await response.json();
    if(data.success) {
        alert('Company updated successfully');
        location.reload();
    } else {
        alert('Error: ' + data.message);
    }
});

function toggleCompany(id, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'suspended' : 'active';
    if(confirm(`Are you sure you want to ${newStatus} this company?`)) {
        fetch('../../api/super_admin/toggle_company.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ company_id: id, status: newStatus })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) location.reload();
            else alert('Error: ' + data.message);
        });
    }
}

function deleteCompany(id, name) {
    if(confirm(`⚠️ WARNING: This will delete ALL data for "${name}" including users, trucks, drivers, trips.\n\nThis action CANNOT be undone!\n\nAre you absolutely sure?`)) {
        fetch('../../api/super_admin/delete_company.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ company_id: id })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('Company deleted successfully');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    }
}
</script>
</body>
</html>