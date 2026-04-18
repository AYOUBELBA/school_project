<?php
require_once('../config/session.php');
require_once('../config/database.php');
require_once('../models/Driver.php');

if(!isset($_SESSION['user_id'])) {
    header("Location: ../signin.php");
    exit();
}
if($_SESSION['user_role'] == "driver"){
    header("Location: ../signin.php");
    exit();
}


$database = new Database();
$db = $database->getConnection();
$driverModel = new Driver($db);

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $full_name = $_POST['full_name'];
                $phone = $_POST['phone'];
                $license_number = $_POST['license_number'];
                $license_expiry = $_POST['license_expiry'];
                $status = $_POST['status'];
                $hire_date = $_POST['hire_date'];
                $address = $_POST['address'];
                $assigned_truck_id = !empty($_POST['truck_id']) ? $_POST['truck_id'] : null;
                
                // CHECK IF LICENSE NUMBER ALREADY EXISTS
                $checkLicenseSql = "SELECT id FROM drivers WHERE license_number = :license_number";
                $checkLicenseStmt = $db->prepare($checkLicenseSql);
                $checkLicenseStmt->bindParam(':license_number', $license_number);
                $checkLicenseStmt->execute();
                
                if($checkLicenseStmt->rowCount() > 0) {
                    $_SESSION['flash_error'] = "License number '$license_number' already exists in the system. Please use a different license number.";
                    header("Location: drivers.php");
                    exit();
                }
                
                // First, create a user account for this driver
                $email = strtolower(str_replace(' ', '.', $full_name)) . '@driver.com';
                $username = strtolower(str_replace(' ', '_', $full_name)) . '_' . rand(100, 999);
                $password = 'Driver123!'; // Default password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Check if email already exists
                $checkSql = "SELECT id FROM users WHERE email = :email";
                $checkStmt = $db->prepare($checkSql);
                $checkStmt->bindParam(':email', $email);
                $checkStmt->execute();
                
                if($checkStmt->rowCount() == 0) {
                    // Create user account
                    $userSql = "INSERT INTO users (username, email, password_hash, role, is_active, created_at) 
                                VALUES (:username, :email, :password_hash, 'driver', true, CURRENT_TIMESTAMP) 
                                RETURNING id";
                    $userStmt = $db->prepare($userSql);
                    $userStmt->bindParam(':username', $username);
                    $userStmt->bindParam(':email', $email);
                    $userStmt->bindParam(':password_hash', $hashed_password);
                    $userStmt->execute();
                    $user_id = $userStmt->fetch(PDO::FETCH_ASSOC)['id'];
                    
                    // Create driver profile linked to user account
                    $data = [
                        'user_id' => $user_id,
                        'full_name' => $full_name,
                        'phone' => $phone,
                        'license_number' => $license_number,
                        'license_expiry' => $license_expiry,
                        'status' => $status,
                        'hire_date' => $hire_date,
                        'address' => $address
                    ];
                    $driverModel->create($data);
                    $_SESSION['flash_success'] = "Driver added successfully! Login credentials: Email: $email | Password: $password";
                } else {
                    // If email exists, create alternative email
                    $email = strtolower(str_replace(' ', '.', $full_name)) . rand(1, 999) . '@driver.com';
                    $userSql = "INSERT INTO users (username, email, password_hash, role, is_active, created_at) 
                                VALUES (:username, :email, :password_hash, 'driver', true, CURRENT_TIMESTAMP) 
                                RETURNING id";
                    $userStmt = $db->prepare($userSql);
                    $userStmt->bindParam(':username', $username);
                    $userStmt->bindParam(':email', $email);
                    $userStmt->bindParam(':password_hash', $hashed_password);
                    $userStmt->execute();
                    $user_id = $userStmt->fetch(PDO::FETCH_ASSOC)['id'];
                    
                    $data = [
                        'user_id' => $user_id,
                        'full_name' => $full_name,
                        'phone' => $phone,
                        'license_number' => $license_number,
                        'license_expiry' => $license_expiry,
                        'status' => $status,
                        'hire_date' => $hire_date,
                        'address' => $address
                    ];
                    $driverModel->create($data);
                    $_SESSION['flash_success'] = "Driver added successfully! Login credentials: Email: $email | Password: $password";
                }

                if($assigned_truck_id) {
                    $assignTruckSql = "UPDATE trucks SET current_driver_id = :driver_id WHERE id = :truck_id";
                    $assignTruckStmt = $db->prepare($assignTruckSql);
                    $assignTruckStmt->bindParam(':driver_id', $user_id);
                    $assignTruckStmt->bindParam(':truck_id', $assigned_truck_id);
                    $assignTruckStmt->execute();
                }
                break;
                
            case 'update':
                $id = $_POST['id'];
                $full_name = $_POST['full_name'];
                $phone = $_POST['phone'];
                $license_number = $_POST['license_number'];
                $license_expiry = $_POST['license_expiry'];
                $status = $_POST['status'];
                $hire_date = $_POST['hire_date'];
                $address = $_POST['address'];
                $assigned_truck_id = !empty($_POST['truck_id']) ? $_POST['truck_id'] : null;
                
                // CHECK IF LICENSE NUMBER ALREADY EXISTS FOR ANOTHER DRIVER
                $checkLicenseSql = "SELECT id FROM drivers WHERE license_number = :license_number AND id != :id";
                $checkLicenseStmt = $db->prepare($checkLicenseSql);
                $checkLicenseStmt->bindParam(':license_number', $license_number);
                $checkLicenseStmt->bindParam(':id', $id);
                $checkLicenseStmt->execute();
                
                if($checkLicenseStmt->rowCount() > 0) {
                    $_SESSION['flash_error'] = "License number '$license_number' is already used by another driver. Please use a different license number.";
                    header("Location: drivers.php");
                    exit();
                }
                
                $data = [
                    'full_name' => $full_name,
                    'phone' => $phone,
                    'license_number' => $license_number,
                    'license_expiry' => $license_expiry,
                    'status' => $status,
                    'hire_date' => $hire_date,
                    'address' => $address
                ];
                $driverModel->update($id, $data);
                
                // Also update the user's username if full_name changed
                $userSql = "SELECT u.id FROM users u 
                            JOIN drivers d ON d.user_id = u.id 
                            WHERE d.id = :driver_id";
                $userStmt = $db->prepare($userSql);
                $userStmt->bindParam(':driver_id', $id);
                $userStmt->execute();
                $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                
                if($user) {
                    $new_username = strtolower(str_replace(' ', '_', $full_name)) . '_' . $id;
                    $updateUserSql = "UPDATE users SET username = :username WHERE id = :user_id";
                    $updateUserStmt = $db->prepare($updateUserSql);
                    $updateUserStmt->bindParam(':username', $new_username);
                    $updateUserStmt->bindParam(':user_id', $user['id']);
                    $updateUserStmt->execute();
                }
                
                $_SESSION['flash_success'] = "Driver updated successfully!";
                // Remove driver from old truck
                $removeOldSql = "UPDATE trucks SET current_driver_id = NULL WHERE current_driver_id = :driver_id";
                $removeOldStmt = $db->prepare($removeOldSql);
                $removeOldStmt->bindParam(':driver_id', $id);
                $removeOldStmt->execute();
                
                // Then assign new truck if selected
                if($assigned_truck_id) {
                    $assignTruckSql = "UPDATE trucks SET current_driver_id = :driver_id WHERE id = :truck_id";
                    $assignTruckStmt = $db->prepare($assignTruckSql);
                    $assignTruckStmt->bindParam(':driver_id', $id);
                    $assignTruckStmt->bindParam(':truck_id', $assigned_truck_id);
                    $assignTruckStmt->execute();
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // Unassign truck from this driver first
                $unassignSql = "UPDATE trucks SET current_driver_id = NULL WHERE current_driver_id = :driver_id";
                $unassignStmt = $db->prepare($unassignSql);
                $unassignStmt->bindParam(':driver_id', $id);
                $unassignStmt->execute();
                
                // Then delete driver
                $deleteSql = "DELETE FROM drivers WHERE id = :id";
                $deleteStmt = $db->prepare($deleteSql);
                $deleteStmt->bindParam(':id', $id);
                $deleteStmt->execute();
                break;
        }
        header("Location: drivers.php");
        exit();
    }

}

// Get flash messages
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$truckSql = "SELECT id, plate_number, model FROM trucks 
             WHERE status = 'active' AND current_driver_id IS NULL 
             ORDER BY plate_number";
$truckStmt = $db->prepare($truckSql);
$truckStmt->execute();
$availableTrucks = $truckStmt->fetchAll(PDO::FETCH_ASSOC);

$drivers = $driverModel->getAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drivers Management | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .status-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .status-available { background: #d1fae5; color: #065f46; }
        .status-on_trip { background: #dbeafe; color: #1e40af; }
        .status-off { background: #fee2e2; color: #991b1b; }
        .modal { transition: all 0.3s ease; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Drivers Management</h2>
                <p class="text-gray-500">Manage your fleet drivers</p>
            </div>
            <button onclick="openModal('addDriverModal')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <i class="ri-add-line"></i> Add Driver
            </button>
        </div>
        
        <?php if($flash_success): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg whitespace-pre-line">
            <?php echo nl2br(htmlspecialchars($flash_success)); ?>
        </div>
        <?php endif; ?>
        
        <?php if($flash_error): ?>
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
            <?php echo htmlspecialchars($flash_error); ?>
        </div>
        <?php endif; ?>
        
        <!-- Drivers Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Full Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">License #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">License Expiry</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assigned Truck</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hire Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($drivers as $driver): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm"><?php echo $driver['id']; ?></td>
                            <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($driver['full_name']); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($driver['phone']); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($driver['license_number']); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo date('Y-m-d', strtotime($driver['license_expiry'])); ?></td>
                            <td class="px-6 py-4">
                                <span class="status-badge status-<?php echo $driver['status']; ?>">
                                    <?php echo str_replace('_', ' ', ucfirst($driver['status'])); ?>
                                </span>
                            </td>

                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($driver['truck_plate'] ?? 'Not assigned'); ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo date('Y-m-d', strtotime($driver['hire_date'])); ?></td>
                            <td class="px-6 py-4 text-sm">
                                <button onclick='editDriver(<?php echo json_encode($driver); ?>)' class="text-blue-600 hover:text-blue-800 mr-2">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button onclick="deleteDriver(<?php echo $driver['id']; ?>, '<?php echo addslashes($driver['full_name']); ?>')" class="text-red-600 hover:text-red-800">
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

<!-- Add Driver Modal -->
<div id="addDriverModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Add New Driver</h3>
        <p class="text-sm text-gray-600 mb-4">A user account will be automatically created for this driver.</p>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="space-y-4">
                <input type="text" name="full_name" placeholder="Full Name" required class="w-full px-3 py-2 border rounded-lg">
                <input type="tel" name="phone" placeholder="Phone Number" required class="w-full px-3 py-2 border rounded-lg">
                <input type="text" name="license_number" placeholder="License Number" required class="w-full px-3 py-2 border rounded-lg">
                <input type="date" name="license_expiry" required class="w-full px-3 py-2 border rounded-lg">
                <input type="date" name="hire_date" required class="w-full px-3 py-2 border rounded-lg">
                <select name="status" class="w-full px-3 py-2 border rounded-lg">
                    <option value="available">Available</option>
                    <option value="on_trip">On Trip</option>
                    <option value="off">Off Duty</option>
                </select>
                <textarea name="address" placeholder="Address" rows="2" class="w-full px-3 py-2 border rounded-lg"></textarea>
            </div>
            <!-- select truck for a driver -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Assign Truck (Optional)</label>
                <select name="truck_id" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- No Truck Assigned --</option>
                    <?php foreach($availableTrucks as $truck): ?>
                    <option value="<?php echo $truck['id']; ?>"><?php echo htmlspecialchars($truck['plate_number'] . ' - ' . $truck['model']); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">Assign a truck to this driver (optional)</p>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('addDriverModal')" class="px-4 py-2 border rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Driver Modal -->
<div id="editDriverModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Edit Driver</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="space-y-4">
                <input type="text" name="full_name" id="edit_full_name" placeholder="Full Name" required class="w-full px-3 py-2 border rounded-lg">
                <input type="tel" name="phone" id="edit_phone" placeholder="Phone Number" required class="w-full px-3 py-2 border rounded-lg">
                <input type="text" name="license_number" id="edit_license_number" placeholder="License Number" required class="w-full px-3 py-2 border rounded-lg">
                <input type="date" name="license_expiry" id="edit_license_expiry" required class="w-full px-3 py-2 border rounded-lg">
                <input type="date" name="hire_date" id="edit_hire_date" required class="w-full px-3 py-2 border rounded-lg">
                <select name="status" id="edit_status" class="w-full px-3 py-2 border rounded-lg">
                    <option value="available">Available</option>
                    <option value="on_trip">On Trip</option>
                    <option value="off">Off Duty</option>
                </select>
                <textarea name="address" id="edit_address" placeholder="Address" rows="2" class="w-full px-3 py-2 border rounded-lg"></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Assign Truck (Optional)</label>
                <select name="truck_id" id="edit_truck_id" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- No Truck Assigned --</option>
                    <?php foreach($availableTrucks as $truck): ?>
                    <option value="<?php echo $truck['id']; ?>"><?php echo htmlspecialchars($truck['plate_number'] . ' - ' . $truck['model']); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">Assign a truck to this driver (optional)</p>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('editDriverModal')" class="px-4 py-2 border rounded-lg">Cancel</button>
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

function editDriver(driver) {
    document.getElementById('edit_id').value = driver.id;
    document.getElementById('edit_full_name').value = driver.full_name;
    document.getElementById('edit_phone').value = driver.phone;
    document.getElementById('edit_license_number').value = driver.license_number;
    document.getElementById('edit_license_expiry').value = driver.license_expiry;
    document.getElementById('edit_hire_date').value = driver.hire_date;
    document.getElementById('edit_status').value = driver.status;
    document.getElementById('edit_address').value = driver.address || '';
    

    const truckSelect = document.getElementById('edit_truck_id');
    if(truckSelect && driver.truck_id) {
        truckSelect.value = driver.truck_id;
    }
    
    openModal('editDriverModal');
}
function checkLicenseNumber(licenseNumber, currentId = null) {
    fetch('check_license.php?license=' + encodeURIComponent(licenseNumber) + '&id=' + (currentId || ''))
        .then(response => response.json())
        .then(data => {
            const licenseInput = document.querySelector('input[name="license_number"]');
            const submitBtn = document.querySelector('#addDriverModal button[type="submit"], #editDriverModal button[type="submit"]');
            
            if(data.exists) {
                licenseInput.classList.add('border-red-500');
                if(submitBtn) submitBtn.disabled = true;
                // Show error message
                let errorDiv = document.getElementById('licenseError');
                if(!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.id = 'licenseError';
                    errorDiv.className = 'text-red-500 text-xs mt-1';
                    licenseInput.parentNode.appendChild(errorDiv);
                }
                errorDiv.innerHTML = 'License number already exists!';
            } else {
                licenseInput.classList.remove('border-red-500');
                if(submitBtn) submitBtn.disabled = false;
                const errorDiv = document.getElementById('licenseError');
                if(errorDiv) errorDiv.remove();
            }
        });
}

function deleteDriver(id, name) {
    if(confirm(`Are you sure you want to delete driver "${name}"? This will also delete their user account.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('fixed')) {
        event.target.classList.add('hidden');
        event.target.classList.remove('flex');
    }
}
</script>
</body>
</html>