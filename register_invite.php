<?php
require_once 'config/database.php';
require_once 'config/auth_saas.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$token = $_GET['token'] ?? '';

// Validate token
$inviteSql = "SELECT i.*, c.name as company_name 
              FROM invitations i
              JOIN companies c ON i.company_id = c.id
              WHERE i.token = :token AND i.is_used = false AND i.expires_at > CURRENT_TIMESTAMP";
$inviteStmt = $db->prepare($inviteSql);
$inviteStmt->bindParam(':token', $token);
$inviteStmt->execute();

if($inviteStmt->rowCount() === 0) {
    die("<div style='text-align:center; padding:50px; font-family:Arial;'>
            <h2>Invalid or Expired Invitation</h2>
            <p>This invitation link is no longer valid. Please contact your company administrator.</p>
            <a href='signin.php'>Go to Login</a>
         </div>");
}

$invite = $inviteStmt->fetch(PDO::FETCH_ASSOC);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $license_number = $_POST['license_number'] ?? '';
    $license_expiry = $_POST['license_expiry'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif ($invite['role'] === 'driver' && (empty($license_number) || empty($license_expiry))) {
        $error = "License number and expiry date are required for drivers";
    }
    
    if (empty($error)) {
        $result = $auth->registerWithInvite($token, $username, $email, $password, $license_number, $license_expiry);
        
        if($result['success']) {
            // Auto login
            $auth->login($email, $password);
            if($invite['role'] === 'driver') {
                header("Location: pages/driver_dashboard.php");
            } else {
                header("Location: pages/dashboard.php");
            }
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Registration | Fleet System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ri-truck-line text-white text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Join <?php echo htmlspecialchars($invite['company_name']); ?></h2>
                <p class="text-gray-600 mt-2">You've been invited as a <?php echo ucfirst($invite['role']); ?></p>
            </div>
            
            <?php if($error): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" name="username" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($invite['email']); ?>" class="w-full px-3 py-2 border rounded-lg bg-gray-100" readonly>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                    <input type="password" name="password" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                </div>
                
                <?php if($invite['role'] === 'driver'): ?>
                <div class="border-t border-gray-200 pt-4 mt-2">
                    <p class="text-sm font-medium text-gray-700 mb-3">Driver License Information</p>
                    
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">License Number *</label>
                        <input type="text" name="license_number" required placeholder="e.g., DL123456" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">License Expiry Date *</label>
                        <input type="date" name="license_expiry" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Your license must be valid</p>
                    </div>
                </div>
                <?php endif; ?>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition mt-2">
                    Complete Registration
                </button>
            </form>
            
            <p class="text-center text-sm text-gray-500 mt-4">
                Already have an account? <a href="signin.php" class="text-blue-600 hover:underline">Sign In</a>
            </p>
        </div>
    </div>
</body>
</html>