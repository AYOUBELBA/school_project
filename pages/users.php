<?php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../includes/check_auth.php';
$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../signin.php");
    exit();
}

// Check if user is admin
$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
if($role !== 'super_admin') {
    header("Location: dashboard.php");
    exit();
}

$current_admin_id = $_SESSION['user_id'];

// Check if current user is SUPER ADMIN
$checkSuperSql = "SELECT is_super_admin FROM users WHERE id = :id";
$checkSuperStmt = $db->prepare($checkSuperSql);
$checkSuperStmt->bindParam(':id', $current_admin_id, PDO::PARAM_INT);
$checkSuperStmt->execute();
$userData = $checkSuperStmt->fetch(PDO::FETCH_ASSOC);
$is_super_admin = $userData ? (bool)$userData['is_super_admin'] : false;

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $password = $_POST['password'];
                $new_role = $_POST['role'];
                $make_super = isset($_POST['make_super']) ? true : false;
                
                // Validation
                $errors = [];
                if(empty($username)) $errors[] = "Username is required";
                if(empty($email)) $errors[] = "Email is required";
                if(empty($password)) $errors[] = "Password is required";
                if(strlen($password) < 6) $errors[] = "Password must be at least 6 characters";
                
                // SECURITY: Only super admins can create admin accounts
                if($new_role == 'admin' && !$is_super_admin) {
                    $errors[] = "Only super administrators can create admin accounts";
                }
                
                // Check if email exists
                $checkSql = "SELECT id FROM users WHERE email = :email";
                $checkStmt = $db->prepare($checkSql);
                $checkStmt->bindParam(':email', $email);
                $checkStmt->execute();
                
                if($checkStmt->rowCount() > 0) {
                    $errors[] = "Email already exists";
                }
                
                if(empty($errors)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $super_admin_value = $make_super ? 'true' : 'false';
                    
                    // Insert into users table
                    $sql = "INSERT INTO users (username, email, password_hash, role, is_active, is_super_admin, created_at) 
                            VALUES (:username, :email, :password_hash, :role, true, :is_super_admin, CURRENT_TIMESTAMP) 
                            RETURNING id";
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':password_hash', $hashed_password);
                    $stmt->bindParam(':role', $new_role);
                    $stmt->bindParam(':is_super_admin', $super_admin_value, PDO::PARAM_BOOL);
                    $stmt->execute();
                    
                    $new_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
                    
                    // If role is driver, also create record in drivers table
                    if($new_role == 'driver') {
                        $driverSql = "INSERT INTO drivers (user_id, full_name, phone, license_number, license_expiry, status, hire_date) 
                                      VALUES (:user_id, :full_name, :phone, :license_number, :license_expiry, 'available', CURRENT_DATE)";
                        $driverStmt = $db->prepare($driverSql);
                        $driverStmt->bindParam(':user_id', $new_id);
                        $driverStmt->bindParam(':full_name', $username);
                        
                        // Default values for driver
                        $default_phone = 'Not provided';
                        $default_license = 'PENDING_' . date('YmdHis');
                        $default_expiry = date('Y-m-d', strtotime('+1 year'));
                        
                        $driverStmt->bindParam(':phone', $default_phone);
                        $driverStmt->bindParam(':license_number', $default_license);
                        $driverStmt->bindParam(':license_expiry', $default_expiry);
                        $driverStmt->execute();
                    }
                    
                    $super_text = $make_super ? " (Super Admin)" : "";
                    logActivity($_SESSION['user_id'], "Created new user: $username with role: $new_role$super_text", 'users', $new_id);
                    $_SESSION['flash_success'] = "User created successfully!";
                    header("Location: users.php");
                    exit();
                } else {
                    $_SESSION['flash_error'] = implode(", ", $errors);
                    header("Location: users.php");
                    exit();
                }
                break;
                
            case 'update':
                $id = $_POST['id'];
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $new_role = $_POST['role'];
                $is_active = isset($_POST['is_active']) ? true : false;
                $make_super = isset($_POST['make_super']) ? true : false;
                
                // Get target user's current data
                $targetSql = "SELECT role, is_super_admin FROM users WHERE id = :id";
                $targetStmt = $db->prepare($targetSql);
                $targetStmt->bindParam(':id', $id, PDO::PARAM_INT);
                $targetStmt->execute();
                $target_user = $targetStmt->fetch(PDO::FETCH_ASSOC);
                
                $error = null;
                
                if($id == $current_admin_id && $target_user['is_super_admin'] && !$make_super) {
                    $error = "You cannot remove your own super administrator status!";
                }
                elseif($id == $current_admin_id && $new_role != 'admin') {
                    $error = "You cannot change your own role from admin!";
                }
                elseif(!$is_super_admin && ($target_user['role'] == 'admin' || $target_user['is_super_admin'])) {
                    $error = "You do not have permission to modify admin accounts";
                }
                elseif(!$is_super_admin && ($new_role == 'admin' || $make_super)) {
                    $error = "Only super administrators can create or modify admin accounts";
                }
                else {
                    $active_value = $is_active ? 'true' : 'false';
                    $super_admin_value = $make_super ? 'true' : 'false';
                    
                    $sql = "UPDATE users SET username = :username, email = :email, role = :role, is_active = :is_active, is_super_admin = :is_super_admin WHERE id = :id";
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':role', $new_role);
                    $stmt->bindParam(':is_active', $active_value, PDO::PARAM_BOOL);
                    $stmt->bindParam(':is_super_admin', $super_admin_value, PDO::PARAM_BOOL);
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    $super_text = $make_super ? " (as Super Admin)" : "";
                    logActivity($_SESSION['user_id'], "Updated user: $username with role: $new_role$super_text", 'users', $id);
                    $_SESSION['flash_success'] = "User updated successfully!";
                    header("Location: users.php");
                    exit();
                }
                
                if($error) {
                    $_SESSION['flash_error'] = $error;
                    header("Location: users.php");
                    exit();
                }
                break;
                
            case 'reset_password':
                $id = $_POST['id'];
                $new_password = $_POST['new_password'];
                
                $targetSql = "SELECT role, is_super_admin FROM users WHERE id = :id";
                $targetStmt = $db->prepare($targetSql);
                $targetStmt->bindParam(':id', $id, PDO::PARAM_INT);
                $targetStmt->execute();
                $target_user = $targetStmt->fetch(PDO::FETCH_ASSOC);
                
                if(!$is_super_admin && ($target_user['role'] == 'admin' || $target_user['is_super_admin'])) {
                    $_SESSION['flash_error'] = "Only super administrators can reset passwords for admin accounts";
                } elseif(strlen($new_password) >= 6) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET password_hash = :password_hash WHERE id = :id";
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':password_hash', $hashed_password);
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    $userSql = "SELECT username FROM users WHERE id = :id";
                    $userStmt = $db->prepare($userSql);
                    $userStmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $userStmt->execute();
                    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                    
                    logActivity($_SESSION['user_id'], "Reset password for user: " . $user['username'], 'users', $id);
                    $_SESSION['flash_success'] = "Password reset successfully!";
                } else {
                    $_SESSION['flash_error'] = "Password must be at least 6 characters";
                }
                header("Location: users.php");
                exit();
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                $targetSql = "SELECT role, is_super_admin, username FROM users WHERE id = :id";
                $targetStmt = $db->prepare($targetSql);
                $targetStmt->bindParam(':id', $id, PDO::PARAM_INT);
                $targetStmt->execute();
                $target_user = $targetStmt->fetch(PDO::FETCH_ASSOC);
                
                if($id == $current_admin_id) {
                    $_SESSION['flash_error'] = "You cannot delete your own account!";
                }
                elseif(!$is_super_admin && ($target_user['role'] == 'admin' || $target_user['is_super_admin'])) {
                    $_SESSION['flash_error'] = "Only super administrators can delete admin accounts";
                }
                else {
                    if($target_user['role'] == 'driver') {
                        $deleteDriverSql = "DELETE FROM drivers WHERE user_id = :user_id";
                        $deleteDriverStmt = $db->prepare($deleteDriverSql);
                        $deleteDriverStmt->bindParam(':user_id', $id);
                        $deleteDriverStmt->execute();
                    }
                    
                    $sql = "DELETE FROM users WHERE id = :id";
                    $stmt = $db->prepare($sql);
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    
                    logActivity($_SESSION['user_id'], "Deleted user: " . $target_user['username'], 'users', $id);
                    $_SESSION['flash_success'] = "User deleted successfully!";
                }
                header("Location: users.php");
                exit();
                break;
        }
    }
}

// Get flash messages
$flash_error = $_SESSION['flash_error'] ?? '';
$flash_success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

// Get all users
$query = "SELECT id, username, email, role, is_active, is_super_admin, last_login, created_at 
          FROM users 
          ORDER BY 
            is_super_admin DESC,
            role DESC,
            created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

function logActivity($userId, $action, $entityType, $entityId) {
    global $db;
    $query = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent) 
              VALUES (:user_id, :action, :entity_type, :entity_id, :ip_address, :user_agent)";
    $stmt = $db->prepare($query);
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':action', $action);
    $stmt->bindParam(':entity_type', $entityType);
    $stmt->bindParam(':entity_id', $entityId, PDO::PARAM_INT);
    $stmt->bindParam(':ip_address', $ip);
    $stmt->bindParam(':user_agent', $agent);
    $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Fleet System</title>
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .role-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .role-admin { background: #dbeafe; color: #1e40af; }
        .role-manager { background: #d1fae5; color: #065f46; }
        .role-driver { background: #fed7aa; color: #92400e; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .super-admin-badge { background: #fef3c7; color: #92400e; font-size: 0.7rem; margin-left: 0.5rem; padding: 0.125rem 0.5rem; border-radius: 9999px; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">User Management</h2>
                <p class="text-gray-500">Manage system users and their roles</p>
                <?php if($is_super_admin): ?>
                <p class="text-xs text-amber-600 mt-1">⭐ Super Admin: Full access to all users</p>
                <?php else: ?>
                <p class="text-xs text-blue-600 mt-1">ℹ️ Admin: Can manage managers and drivers</p>
                <?php endif; ?>
            </div>
            <button onclick="openModal('addUserModal')" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <i class="ri-add-line"></i> Add New User
            </button>
        </div>
        
        <?php if($flash_error): ?>
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg"><?php echo htmlspecialchars($flash_error); ?></div>
        <?php endif; ?>
        
        <?php if($flash_success): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg"><?php echo htmlspecialchars($flash_success); ?></div>
        <?php endif; ?>
        
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Login</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($users as $user): 
                            $can_edit = false;
                            $can_delete = false;
                            $can_reset = false;
                            
                            if($is_super_admin) {
                                $can_edit = true;
                                $can_reset = true;
                                $can_delete = ($user['id'] != $current_admin_id);
                            } else {
                                if($user['role'] == 'manager' || $user['role'] == 'driver') {
                                    $can_edit = true;
                                    $can_reset = true;
                                    $can_delete = true;
                                }
                            }
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm"><?php echo $user['id']; ?></td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <?php echo htmlspecialchars($user['username']); ?>
                                <?php if($user['is_super_admin']): ?><span class="super-admin-badge">Super Admin</span><?php endif; ?>
                                <?php if($user['id'] == $_SESSION['user_id']): ?><span class="ml-2 text-xs text-blue-600">(You)</span><?php endif; ?>
                             </td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-6 py-4"><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                            <td class="px-6 py-4"><span class="role-badge <?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                            <td class="px-6 py-4 text-sm"><?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                            <td class="px-6 py-4 text-sm"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                            <td class="px-6 py-4 text-sm">
                                <?php if($can_edit): ?>
                                <button onclick='editUser(<?php echo json_encode($user); ?>)' class="text-blue-600 hover:text-blue-800 mr-2"><i class="ri-edit-line"></i></button>
                                <?php endif; ?>
                                <?php if($can_reset): ?>
                                <button onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>', '<?php echo $user['role']; ?>')" class="text-orange-600 hover:text-orange-800 mr-2"><i class="ri-key-2-line"></i></button>
                                <?php endif; ?>
                                <?php if($can_delete): ?>
                                <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>', '<?php echo $user['role']; ?>')" class="text-red-600 hover:text-red-800"><i class="ri-delete-bin-line"></i></button>
                                <?php endif; ?>
                              </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Add New User</h3>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="space-y-4">
                <input type="text" name="username" placeholder="Username" required class="w-full px-3 py-2 border rounded-lg">
                <input type="email" name="email" placeholder="Email" required class="w-full px-3 py-2 border rounded-lg">
                <input type="password" name="password" placeholder="Password (min 6 characters)" required class="w-full px-3 py-2 border rounded-lg">
                <select name="role" class="w-full px-3 py-2 border rounded-lg">
                    <option value="manager">Manager</option>
                    <option value="driver">Driver</option>
                    <?php if($is_super_admin): ?>
                    <option value="admin">Admin</option>
                    <?php endif; ?>
                </select>
                <?php if($is_super_admin): ?>
                <label class="flex items-center"><input type="checkbox" name="make_super" class="mr-2"> Make Super Administrator</label>
                <?php endif; ?>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('addUserModal')" class="px-4 py-2 border rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Edit User</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="space-y-4">
                <input type="text" name="username" id="edit_username" required class="w-full px-3 py-2 border rounded-lg">
                <input type="email" name="email" id="edit_email" required class="w-full px-3 py-2 border rounded-lg">
                <select name="role" id="edit_role" class="w-full px-3 py-2 border rounded-lg">
                    <option value="manager">Manager</option>
                    <option value="driver">Driver</option>
                    <?php if($is_super_admin): ?>
                    <option value="admin">Admin</option>
                    <?php endif; ?>
                </select>
                <?php if($is_super_admin): ?>
                <label class="flex items-center"><input type="checkbox" name="make_super" id="edit_make_super" class="mr-2"> Super Administrator</label>
                <?php endif; ?>
                <label class="flex items-center"><input type="checkbox" name="is_active" id="edit_is_active" class="mr-2"> Active</label>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('editUserModal')" class="px-4 py-2 border rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Update</button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-semibold mb-4">Reset Password</h3>
        <p class="text-sm text-gray-600 mb-4">Reset password for: <span id="reset_username" class="font-medium"></span></p>
        <form method="POST">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="id" id="reset_id">
            <input type="password" name="new_password" placeholder="New Password (min 6 characters)" required class="w-full px-3 py-2 border rounded-lg">
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModal('resetPasswordModal')" class="px-4 py-2 border rounded-lg">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded-lg">Reset</button>
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

function editUser(user) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    <?php if($is_super_admin): ?>
    document.getElementById('edit_make_super').checked = user.is_super_admin === true || user.is_super_admin === 't';
    <?php endif; ?>
    document.getElementById('edit_is_active').checked = user.is_active === true || user.is_active === 't';
    openModal('editUserModal');
}

function resetPassword(id, username, role) {
    <?php if(!$is_super_admin): ?>
    if(role === 'admin') { alert('Cannot reset admin passwords'); return; }
    <?php endif; ?>
    document.getElementById('reset_id').value = id;
    document.getElementById('reset_username').innerText = username;
    openModal('resetPasswordModal');
}

function deleteUser(id, username, role) {
    <?php if(!$is_super_admin): ?>
    if(role === 'admin') { alert('Cannot delete admin accounts'); return; }
    <?php endif; ?>
    if(confirm(`Delete "${username}"? This cannot be undone.`)) {
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