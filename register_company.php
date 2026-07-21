<?php
require_once 'config/database.php';
require_once 'config/auth_saas.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $company_name = $_POST['company_name'];
    $plan = $_POST['plan'];
    
    $result = $auth->registerCompany($username, $email, $password, $company_name, $plan);
    
    if($result['success']) {
        $success = "Company registered successfully! You can now login.";
        // Auto login
        $login = $auth->login($email, $password);
        if($login['success']) {
            header("Location: pages/dashboard.php");
            exit();
        }
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
    <title>Register Company | Fleet System</title>
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ri-truck-line text-white text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Start Your Fleet</h2>
                <p class="text-gray-500">Create your company account</p>
            </div>
            
            <?php if($error): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
            <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                    <input type="text" name="company_name" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                    <input type="text" name="username" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Choose Plan</label>
                    <select name="plan" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="standard">Standard - $49/mo (5 drivers, 5 trucks)</option>
                        <option value="premium">Premium - $99/mo (20 drivers, 20 trucks)</option>
                        <option value="extra">Extra - $199/mo (Unlimited)</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">7-day free trial included</p>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                    Register
                </button>
            </form>
            
            <p class="text-center text-sm text-gray-500 mt-4">
                Already have an account? <a href="signin.php" class="text-blue-600 hover:underline">Sign In</a>
            </p>
        </div>
    </div>
</body>
</html>