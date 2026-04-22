<?php
require_once('../config/session.php');
require_once('../config/database.php');

$database = new Database();
$db = $database->getConnection();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../signin.php");
    exit();
}

// Check if user is a driver
$role = $_SESSION['role'] ?? $_SESSION['user_role'] ?? '';
if($role !== 'driver') {
    header("Location: dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Driver';

// Get driver information
$sql = "SELECT d.*, u.email, u.username, u.created_at, u.last_login
        FROM drivers d 
        JOIN users u ON d.user_id = u.id 
        WHERE u.id = :user_id";
$stmt = $db->prepare($sql);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$driver = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $phone = trim($_POST['phone']);
                $address = trim($_POST['address']);
                
                $updateSql = "UPDATE drivers SET phone = :phone, address = :address WHERE user_id = :user_id";
                $updateStmt = $db->prepare($updateSql);
                $updateStmt->bindParam(':phone', $phone);
                $updateStmt->bindParam(':address', $address);
                $updateStmt->bindParam(':user_id', $user_id);
                $updateStmt->execute();
                
                $success = "Profile updated successfully!";
                
                // Refresh driver data
                $driver['phone'] = $phone;
                $driver['address'] = $address;
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];
                
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
                        
                        $success = "Password changed successfully! Please login again.";
                        
                        // Optional: Auto logout after password change
                        // session_destroy();
                        // header("Location: ../signin.php");
                        // exit();
                    } else {
                        $error = "Current password is incorrect";
                    }
                }
                break;
        }
    }
}

// Calculate license expiry status
$license_expiry = strtotime($driver['license_expiry']);
$now = time();
$days_left = round(($license_expiry - $now) / (60 * 60 * 24));
if($days_left < 0) {
    $license_status = 'Expired';
    $license_color = 'text-red-600';
    $license_bg = 'bg-red-100';
} elseif($days_left < 30) {
    $license_status = 'Expiring Soon';
    $license_color = 'text-orange-600';
    $license_bg = 'bg-orange-100';
} else {
    $license_status = 'Valid';
    $license_color = 'text-green-600';
    $license_bg = 'bg-green-100';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Driver Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .info-card {
            transition: all 0.2s ease;
        }
        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg-gray-50">

<!-- Simple Driver Navbar -->
<nav class="bg-white shadow-sm sticky top-0 z-50">
    <div class="container mx-auto px-4 py-3">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                    <i class="ri-truck-line text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">Driver Portal</h1>
                    <p class="text-xs text-gray-500">My Profile</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <a href="driver_dashboard.php" class="text-gray-600 hover:text-blue-600 transition">
                    <i class="ri-dashboard-line text-xl"></i>
                </a>
                <a href="../config/logout.php" class="text-gray-400 hover:text-red-500">
                    <i class="ri-logout-box-r-line text-xl"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<div class="container mx-auto px-4 py-8">
    <!-- Welcome Section -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 rounded-2xl p-6 mb-8 text-white">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold mb-2">My Profile</h1>
                <p class="text-blue-100">Manage your personal information</p>
            </div>
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                <i class="ri-user-line text-3xl text-white"></i>
            </div>
        </div>
    </div>
    
    <!-- Success/Error Messages -->
    <?php if($success): ?>
    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
        <?php echo $success; ?>
    </div>
    <?php endif; ?>
    
    <?php if($error): ?>
    <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <?php echo $error; ?>
    </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Card -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm overflow-hidden info-card">
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 p-6 text-center">
                    <div class="w-24 h-24 bg-white rounded-full mx-auto flex items-center justify-center shadow-lg">
                        <span class="text-3xl font-bold text-blue-600">
                            <?php echo strtoupper(substr($driver['full_name'], 0, 2)); ?>
                        </span>
                    </div>
                    <h3 class="text-white text-xl font-semibold mt-3"><?php echo htmlspecialchars($driver['full_name']); ?></h3>
                    <p class="text-blue-200 text-sm">Driver ID: #<?php echo $driver['id']; ?></p>
                </div>
                
                <div class="p-5 space-y-3">
                    <div class="flex items-center gap-3 text-gray-600">
                        <i class="ri-mail-line text-lg w-5"></i>
                        <span class="text-sm"><?php echo htmlspecialchars($driver['email']); ?></span>
                    </div>
                    <div class="flex items-center gap-3 text-gray-600">
                        <i class="ri-phone-line text-lg w-5"></i>
                        <span class="text-sm"><?php echo htmlspecialchars($driver['phone'] ?: 'Not provided'); ?></span>
                    </div>
                    <div class="flex items-center gap-3 text-gray-600">
                        <i class="ri-id-card-line text-lg w-5"></i>
                        <span class="text-sm">License: <?php echo htmlspecialchars($driver['license_number']); ?></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="ri-calendar-check-line text-lg w-5"></i>
                        <div>
                            <span class="text-sm">License Expiry: <?php echo date('M d, Y', strtotime($driver['license_expiry'])); ?></span>
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?php echo $license_color . ' ' . $license_bg; ?>">
                                <?php echo $license_status; ?>
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-gray-600">
                        <i class="ri-calendar-line text-lg w-5"></i>
                        <span class="text-sm">Member since: <?php echo date('M d, Y', strtotime($driver['created_at'])); ?></span>
                    </div>
                    <div class="flex items-center gap-3 text-gray-600">
                        <i class="ri-login-circle-line text-lg w-5"></i>
                        <span class="text-sm">Last login: <?php echo $driver['last_login'] ? date('M d, Y H:i', strtotime($driver['last_login'])) : 'Never'; ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="bg-white rounded-xl shadow-sm p-5 mt-6">
                <h4 class="font-semibold text-gray-800 mb-3">Quick Stats</h4>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Status</span>
                        <span class="font-medium capitalize"><?php echo str_replace('_', ' ', $driver['status']); ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Hire Date</span>
                        <span class="font-medium"><?php echo date('M d, Y', strtotime($driver['hire_date'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Edit Forms -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Edit Profile Form -->
            <div class="bg-white rounded-xl shadow-sm">
                <div class="p-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Edit Profile</h3>
                    <p class="text-sm text-gray-500">Update your contact information</p>
                </div>
                <form method="POST" class="p-5">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($driver['full_name']); ?>" disabled class="w-full px-3 py-2 border rounded-lg bg-gray-50 text-gray-500">
                            <p class="text-xs text-gray-500 mt-1">Contact administrator to change name</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($driver['phone']); ?>" placeholder="Your phone number" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <textarea name="address" rows="2" placeholder="Your address" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($driver['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Update Profile</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Change Password Form -->
            <div class="bg-white rounded-xl shadow-sm">
                <div class="p-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Change Password</h3>
                    <p class="text-sm text-gray-500">Keep your account secure</p>
                </div>
                <form method="POST" class="p-5">
                    <input type="hidden" name="action" value="change_password">
                    <div class="space-y-4">
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
            
            <!-- App Download Link -->
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl p-5 border border-purple-100">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <h4 class="font-semibold text-gray-800">Mobile App</h4>
                        <p class="text-sm text-gray-600 mt-1">Track trips, update status on the go</p>
                    </div>
                    <div class="flex gap-3">
                        <a href="#" class="flex items-center gap-2 bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition">
                            <i class="ri-apple-line text-xl"></i>
                            <div>
                                <p class="text-xs">Download on</p>
                                <p class="text-sm font-semibold">App Store</p>
                            </div>
                        </a>
                        <a href="#" class="flex items-center gap-2 bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition">
                            <i class="ri-android-line text-xl"></i>
                            <div>
                                <p class="text-xs">Get it on</p>
                                <p class="text-sm font-semibold">Google Play</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Simple Footer -->
<footer class="bg-white border-t border-gray-200 mt-8 py-4 text-center text-sm text-gray-500">
    <div class="container mx-auto px-4">
        © <?php echo date('Y'); ?> Fleet Management System - Driver Portal
    </div>
</footer>

</body>
</html>