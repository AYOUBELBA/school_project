<?php
require_once '../config/session.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../signin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
$username = $_SESSION['username'] ?? '';

// Get user information
$userSql = "SELECT id, username, email, role, is_active, last_login, created_at 
            FROM users WHERE id = :user_id";
$userStmt = $db->prepare($userSql);
$userStmt->bindParam(':user_id', $user_id);
$userStmt->execute();
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

// Get driver information if user is a driver
$driver = null;
if($user_role == 'driver') {
    $driverSql = "SELECT * FROM drivers WHERE user_id = :user_id";
    $driverStmt = $db->prepare($driverSql);
    $driverStmt->bindParam(':user_id', $user_id);
    $driverStmt->execute();
    $driver = $driverStmt->fetch(PDO::FETCH_ASSOC);
}

// Handle profile update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $new_username = trim($_POST['username']);
                $new_email = trim($_POST['email']);
                
                // Validate email
                if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Invalid email format";
                } else {
                    // Check if email already exists for another user
                    $checkSql = "SELECT id FROM users WHERE email = :email AND id != :user_id";
                    $checkStmt = $db->prepare($checkSql);
                    $checkStmt->bindParam(':email', $new_email);
                    $checkStmt->bindParam(':user_id', $user_id);
                    $checkStmt->execute();
                    
                    if($checkStmt->rowCount() > 0) {
                        $error = "Email already in use by another account";
                    } else {
                        $updateSql = "UPDATE users SET username = :username, email = :email WHERE id = :user_id";
                        $updateStmt = $db->prepare($updateSql);
                        $updateStmt->bindParam(':username', $new_username);
                        $updateStmt->bindParam(':email', $new_email);
                        $updateStmt->bindParam(':user_id', $user_id);
                        $updateStmt->execute();
                        
                        // Update session
                        $_SESSION['username'] = $new_username;
                        $_SESSION['user_email'] = $new_email;
                        
                        $success = "Profile updated successfully!";
                        
                        // Refresh user data
                        $user['username'] = $new_username;
                        $user['email'] = $new_email;
                    }
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
                // Validate passwords
                if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $error = "All password fields are required";
                } elseif($new_password !== $confirm_password) {
                    $error = "New passwords do not match";
                } elseif(strlen($new_password) < 6) {
                    $error = "Password must be at least 6 characters";
                } else {
                    // Verify current password
                    $passSql = "SELECT password_hash FROM users WHERE id = :user_id";
                    $passStmt = $db->prepare($passSql);
                    $passStmt->bindParam(':user_id', $user_id);
                    $passStmt->execute();
                    $user_pass = $passStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if(password_verify($current_password, $user_pass['password_hash'])) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $updateSql = "UPDATE users SET password_hash = :password_hash WHERE id = :user_id";
                        $updateStmt = $db->prepare($updateSql);
                        $updateStmt->bindParam(':password_hash', $hashed_password);
                        $updateStmt->bindParam(':user_id', $user_id);
                        $updateStmt->execute();
                        
                        $success = "Password changed successfully!";
                    } else {
                        $error = "Current password is incorrect";
                    }
                }
                break;
                
            case 'update_driver':
                if($user_role == 'driver') {
                    $phone = $_POST['phone'];
                    $address = $_POST['address'];
                    
                    $updateSql = "UPDATE drivers SET phone = :phone, address = :address WHERE user_id = :user_id";
                    $updateStmt = $db->prepare($updateSql);
                    $updateStmt->bindParam(':phone', $phone);
                    $updateStmt->bindParam(':address', $address);
                    $updateStmt->bindParam(':user_id', $user_id);
                    $updateStmt->execute();
                    
                    $success = "Driver information updated successfully!";
                    
                    // Refresh driver data
                    $driver['phone'] = $phone;
                    $driver['address'] = $address;
                }
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .profile-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .info-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-50">

<?php 
// Include appropriate sidebar based on role
if($user_role == 'driver') {
    include '../includes/sidebar_driver.php';
} else {
    include '../includes/sidebar.php';
}
?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">My Profile</h2>
            <p class="text-gray-500">Manage your account information</p>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if($success): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center justify-between">
            <span><?php echo $success; ?></span>
            <button onclick="this.parentElement.remove()" class="text-green-700">&times;</button>
        </div>
        <?php endif; ?>
        
        <?php if($error): ?>
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center justify-between">
            <span><?php echo $error; ?></span>
            <button onclick="this.parentElement.remove()" class="text-red-700">&times;</button>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Profile Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="profile-avatar p-6 text-center">
                        <div class="w-24 h-24 bg-white rounded-full mx-auto flex items-center justify-center shadow-lg">
                            <span class="text-3xl font-bold text-purple-600">
                                <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                            </span>
                        </div>
                        <h3 class="text-white text-xl font-semibold mt-3"><?php echo htmlspecialchars($user['username']); ?></h3>
                        <p class="text-purple-200 text-sm"><?php echo ucfirst($user['role']); ?></p>
                    </div>
                    
                    <div class="p-6 space-y-4">
                        <div class="flex items-center gap-3 text-gray-600">
                            <i class="ri-mail-line text-lg"></i>
                            <span class="text-sm"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="flex items-center gap-3 text-gray-600">
                            <i class="ri-calendar-line text-lg"></i>
                            <span class="text-sm">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <div class="flex items-center gap-3 text-gray-600">
                            <i class="ri-login-circle-line text-lg"></i>
                            <span class="text-sm">Last login: <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></span>
                        </div>
                        <div class="flex items-center gap-3 text-gray-600">
                            <i class="ri-shield-check-line text-lg"></i>
                            <span class="text-sm">Status: <?php echo $user['is_active'] ? '<span class="text-green-600">Active</span>' : '<span class="text-red-600">Inactive</span>'; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
                    <h4 class="font-semibold text-gray-800 mb-3">Quick Actions</h4>
                    <div class="space-y-2">
                        <a href="#" onclick="document.getElementById('passwordSection').scrollIntoView({behavior: 'smooth'})" class="flex items-center gap-3 text-sm text-gray-600 hover:text-blue-600 transition">
                            <i class="ri-lock-password-line"></i>
                            <span>Change Password</span>
                        </a>
                        <a href="#" class="flex items-center gap-3 text-sm text-gray-600 hover:text-blue-600 transition">
                            <i class="ri-notification-3-line"></i>
                            <span>Notification Settings</span>
                        </a>
                        <a href="#" class="flex items-center gap-3 text-sm text-gray-600 hover:text-blue-600 transition">
                            <i class="ri-privacy-line"></i>
                            <span>Privacy Settings</span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Profile Information -->
                <div class="bg-white rounded-xl shadow-sm" id="profileSection">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Profile Information</h3>
                        <p class="text-sm text-gray-500">Update your personal information</p>
                    </div>
                    <form method="POST" class="p-6">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                                <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled class="w-full px-3 py-2 border rounded-lg bg-gray-50 text-gray-500">
                                <p class="text-xs text-gray-500 mt-1">Role cannot be changed. Contact an administrator for role changes.</p>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Update Profile</button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Driver Information (Only for drivers) -->
                <?php if($user_role == 'driver' && $driver): ?>
                <div class="bg-white rounded-xl shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Driver Information</h3>
                        <p class="text-sm text-gray-500">Your driver profile details</p>
                    </div>
                    <form method="POST" class="p-6">
                        <input type="hidden" name="action" value="update_driver">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" value="<?php echo htmlspecialchars($driver['full_name']); ?>" disabled class="w-full px-3 py-2 border rounded-lg bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($driver['phone']); ?>" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">License Number</label>
                                <input type="text" value="<?php echo htmlspecialchars($driver['license_number']); ?>" disabled class="w-full px-3 py-2 border rounded-lg bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">License Expiry</label>
                                <input type="text" value="<?php echo date('Y-m-d', strtotime($driver['license_expiry'])); ?>" disabled class="w-full px-3 py-2 border rounded-lg bg-gray-50">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <textarea name="address" rows="2" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($driver['address'] ?? ''); ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <input type="text" value="<?php echo ucfirst(str_replace('_', ' ', $driver['status'])); ?>" disabled class="w-full px-3 py-2 border rounded-lg bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Hire Date</label>
                                <input type="text" value="<?php echo date('Y-m-d', strtotime($driver['hire_date'])); ?>" disabled class="w-full px-3 py-2 border rounded-lg bg-gray-50">
                            </div>
                        </div>
                        <div class="flex justify-end mt-4">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Update Driver Info</button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- Change Password -->
                <div class="bg-white rounded-xl shadow-sm" id="passwordSection">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Change Password</h3>
                        <p class="text-sm text-gray-500">Update your password to keep your account secure</p>
                    </div>
                    <form method="POST" class="p-6">
                        <input type="hidden" name="action" value="change_password">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                <input type="password" name="current_password" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                <input type="password" name="new_password" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                <input type="password" name="confirm_password" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Change Password</button>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Session Management -->
                <div class="bg-white rounded-xl shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Active Sessions</h3>
                        <p class="text-sm text-gray-500">Manage your active login sessions</p>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <i class="ri-computer-line text-gray-400 text-xl"></i>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Current Session</p>
                                    <p class="text-xs text-gray-500">Logged in since <?php echo date('H:i', $_SESSION['login_time'] ?? time()); ?></p>
                                </div>
                            </div>
                            <span class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded-full">Active Now</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-4">This is your current browser session. Other sessions would appear here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if(target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>
</body>
</html>