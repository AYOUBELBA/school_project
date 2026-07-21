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
    die("Invalid or expired invitation link. Please contact your company administrator.");
}

$invite = $inviteStmt->fetch(PDO::FETCH_ASSOC);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $result = $auth->registerWithInvite($token, $username, $email, $password);
    
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Complete Registration</title>
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-center mb-4">Join <?php echo htmlspecialchars($invite['company_name']); ?></h2>
            <p class="text-center text-gray-600 mb-6">You've been invited as a <?php echo ucfirst($invite['role']); ?></p>
            
            <?php if($error): ?>
            <div class="mb-4 p-3 bg-red-100 text-red-700 rounded"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="text" name="username" placeholder="Full Name" required class="w-full px-3 py-2 border rounded-lg mb-3">
                <input type="email" name="email" value="<?php echo htmlspecialchars($invite['email']); ?>" class="w-full px-3 py-2 border rounded-lg mb-3 bg-gray-100" readonly>
                <input type="password" name="password" placeholder="Password (min 6 characters)" required class="w-full px-3 py-2 border rounded-lg mb-3">
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">Complete Registration</button>
            </form>
        </div>
    </div>
</body>
</html>