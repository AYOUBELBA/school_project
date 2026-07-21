<?php
require_once '../includes/check_auth.php';
require_once '../config/session.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if(!isset($_SESSION['user_id'])) {
    header("Location: ../signin.php");
    exit();
}
if($_SESSION['user_role'] == "driver"){
    header("Location: ../signin.php");
    exit();
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                
                try {
                    $query = "UPDATE users SET username = :username, email = :email WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':id', $userId);
                    $stmt->execute();
                    
                    $_SESSION['username'] = $username;
                    $_SESSION['user_email'] = $email;
                    $success = "Profile updated successfully!";
                } catch(PDOException $e) {
                    $error = "Error updating profile: " . $e->getMessage();
                }
                break;
                
            case 'change_password':
                $current = $_POST['current_password'];
                $new = $_POST['new_password'];
                $confirm = $_POST['confirm_password'];
                
                if($new !== $confirm) {
                    $error = "New passwords do not match!";
                } elseif(strlen($new) < 6) {
                    $error = "Password must be at least 6 characters!";
                } else {
                    // Verify current password
                    $query = "SELECT password_hash FROM users WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':id', $userId);
                    $stmt->execute();
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if(password_verify($current, $user['password_hash'])) {
                        $newHash = password_hash($new, PASSWORD_DEFAULT);
                        $updateQuery = "UPDATE users SET password_hash = :hash WHERE id = :id";
                        $updateStmt = $db->prepare($updateQuery);
                        $updateStmt->bindParam(':hash', $newHash);
                        $updateStmt->bindParam(':id', $userId);
                        $updateStmt->execute();
                        $success = "Password changed successfully!";
                    } else {
                        $error = "Current password is incorrect!";
                    }
                }
                break;
        }
    }
}

// Get user data
$query = "SELECT username, email, role, created_at FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $userId);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Fleet System</title>
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Settings</h2>
            <p class="text-gray-500">Manage your account preferences</p>
        </div>
        
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
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Profile Settings -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Profile Information</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                            <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled class="w-full px-3 py-2 border rounded-lg bg-gray-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Member Since</label>
                            <input type="text" value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" disabled class="w-full px-3 py-2 border rounded-lg bg-gray-50">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Change Password -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Change Password</h3>
                <form method="POST">
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
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                            Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- System Info -->
        <div class="mt-6 bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">System Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">PHP Version:</span>
                    <span class="ml-2 font-medium"><?php echo phpversion(); ?></span>
                </div>
                <div>
                    <span class="text-gray-500">Database:</span>
                    <span class="ml-2 font-medium">PostgreSQL</span>
                </div>
                <div>
                    <span class="text-gray-500">Session Status:</span>
                    <span class="ml-2 font-medium text-green-600">Active</span>
                </div>
                <div>
                    <span class="text-gray-500">Last Login:</span>
                    <span class="ml-2 font-medium"><?php echo $_SESSION['login_time'] ? date('Y-m-d H:i', $_SESSION['login_time']) : 'N/A'; ?></span>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>