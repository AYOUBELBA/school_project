<?php
require_once 'config/database.php';
require_once 'config/auth.php';

session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: pages/dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth($db);
    
    $result = $auth->login($_POST['email'], $_POST['password']);
    
    if($result['success']) {
        if($result['role'] == 'driver') {
            header("Location: mobile_app.php");
        } else {
            header("Location: pages/dashboard.php");
        }
        exit();
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Fleet Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-8 w-full max-w-md">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ri-truck-line text-white text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">FleetManager</h2>
                <p class="text-gray-500">Truck Tracking System</p>
            </div>
            
            <?php if($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                </div>
                <button type="submit" class="w-full bg-primary text-white py-2 rounded-lg hover:bg-blue-dark transition">Sign In</button>
            </form>
            
            <div class="mt-6 text-center">
                <a href="forgot_password.php" class="text-sm text-primary hover:underline">Forgot Password?</a>
            </div>
        </div>
    </div>
</body>
</html>