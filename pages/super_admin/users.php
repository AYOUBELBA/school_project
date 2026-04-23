<?php
require_once '../../includes/check_auth.php';

if($_SESSION['user_role'] !== 'super_admin') {
    header("Location: ../dashboard.php");
    exit();
}

$sql = "SELECT u.*, c.name as company_name 
        FROM users u
        LEFT JOIN companies c ON u.company_id = c.id
        ORDER BY u.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Users | Super Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body class="bg-gray-100">

<?php include '../../includes/super_admin_sidebar.php'; ?>

<main class="ml-72 p-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">System Users</h1>
        <p class="text-gray-500">All users across all companies</p>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Company</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Last Login</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach($users as $user): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm"><?php echo $user['id']; ?></td>
                    <td class="px-6 py-4 text-sm font-medium"><?php echo htmlspecialchars($user['username']); ?></td>
                    <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($user['email']); ?></td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full <?php 
                            echo $user['role'] === 'super_admin' ? 'bg-purple-100 text-purple-800' : 
                                ($user['role'] === 'owner' ? 'bg-blue-100 text-blue-800' :
                                ($user['role'] === 'manager' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800')); ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($user['company_name'] ?? 'N/A'); ?></td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm"><?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                    <td class="px-6 py-4 text-sm">
                        <?php if($user['role'] !== 'super_admin'): ?>
                        <button onclick="toggleUser(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 'false' : 'true'; ?>)" class="text-orange-600 hover:text-orange-800 mr-2">
                            <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                        </button>
                        <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>')" class="text-red-600 hover:text-red-800">Delete</button>
                        <?php else: ?>
                        <span class="text-gray-400">Protected</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
function toggleUser(userId, activate) {
    const action = activate ? 'activate' : 'deactivate';
    if(confirm(`Are you sure you want to ${action} this user?`)) {
        fetch('../../api/super_admin/toggle_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, active: activate })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) location.reload();
            else alert('Error: ' + data.message);
        });
    }
}

function deleteUser(userId, username) {
    if(confirm(`⚠️ Are you sure you want to delete user "${username}"? This action CANNOT be undone!`)) {
        fetch('../../api/super_admin/delete_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert('User deleted successfully');
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