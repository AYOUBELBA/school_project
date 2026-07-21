<?php
require_once '../includes/check_auth.php';

// Only owner can access
if($_SESSION['user_role'] !== 'owner' && $_SESSION['user_role'] !== 'super_admin') {
    header("Location: dashboard.php");
    exit();
}

$company_id = $_SESSION['company_id'];

// Get company info
$sql = "SELECT * FROM companies WHERE id = :company_id";
$stmt = $db->prepare($sql);
$stmt->bindParam(':company_id', $company_id);
$stmt->execute();
$company = $stmt->fetch(PDO::FETCH_ASSOC);

// Get pending invitations
$inviteSql = "SELECT * FROM invitations WHERE company_id = :company_id AND is_used = false AND expires_at > CURRENT_TIMESTAMP ORDER BY created_at DESC";
$inviteStmt = $db->prepare($inviteSql);
$inviteStmt->bindParam(':company_id', $company_id);
$inviteStmt->execute();
$invitations = $inviteStmt->fetchAll(PDO::FETCH_ASSOC);

// Handle invitation creation
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if($_POST['action'] === 'create_invite') {
        $email = $_POST['email'];
        $invite_role = $_POST['role'];
        
        // Create auth instance
        $auth = new Auth($db);
        $result = $auth->createInvitation($company_id, $email, $invite_role, $_SESSION['user_id']);
        
        if($result['success']) {
            $_SESSION['flash_success'] = "Invitation created successfully! Share this link with the user.";
            $_SESSION['invite_token'] = $result['token'];
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
        header("Location: company_settings.php");
        exit();
    }
    
    if($_POST['action'] === 'upgrade_plan') {
        $new_plan = $_POST['plan'];
        $max_drivers = $new_plan === 'standard' ? 5 : ($new_plan === 'premium' ? 20 : 999999);
        $max_trucks = $new_plan === 'standard' ? 5 : ($new_plan === 'premium' ? 20 : 999999);
        $max_managers = $new_plan === 'standard' ? 2 : ($new_plan === 'premium' ? 5 : 999999);
        
        $updateSql = "UPDATE companies SET subscription_plan = :plan, max_drivers = :max_drivers, 
                      max_trucks = :max_trucks, max_managers = :max_managers
                      WHERE id = :company_id";
        $updateStmt = $db->prepare($updateSql);
        $updateStmt->bindParam(':plan', $new_plan);
        $updateStmt->bindParam(':max_drivers', $max_drivers);
        $updateStmt->bindParam(':max_trucks', $max_trucks);
        $updateStmt->bindParam(':max_managers', $max_managers);
        $updateStmt->bindParam(':company_id', $company_id);
        $updateStmt->execute();
        
        $_SESSION['flash_success'] = "Plan upgraded to $new_plan!";
        header("Location: company_settings.php");
        exit();
    }
}

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
$invite_token = $_SESSION['invite_token'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['invite_token']);

// Get usage stats
$statsSql = "SELECT 
             (SELECT COUNT(*) FROM users WHERE company_id = :company_id AND role = 'driver') as driver_count,
             (SELECT COUNT(*) FROM users WHERE company_id = :company_id AND role = 'manager') as manager_count,
             (SELECT COUNT(*) FROM trucks WHERE company_id = :company_id) as truck_count";
$statsStmt = $db->prepare($statsSql);
$statsStmt->bindParam(':company_id', $company_id);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Settings | Fleet System</title>
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .info-card { transition: all 0.2s ease; }
        .info-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .progress-bar { background: #e5e7eb; border-radius: 10px; overflow: hidden; }
        .progress-fill { background: #3758f9; height: 8px; border-radius: 10px; transition: width 0.3s ease; }
        .warning-fill { background: #f59e0b; }
        .danger-fill { background: #ef4444; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Company Settings</h2>
            <p class="text-gray-500">Manage your company profile and team members</p>
        </div>
        
        <?php if($flash_success): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg"><?php echo $flash_success; ?></div>
        <?php endif; ?>
        
        <?php if($flash_error): ?>
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg"><?php echo $flash_error; ?></div>
        <?php endif; ?>
        
        <?php if($invite_token): ?>
        <div class="mb-6 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded-lg">
            <p class="font-medium mb-2">Invitation Link (Share this with the user):</p>
            <code class="block bg-white p-2 rounded text-sm break-all">
                <?php echo "http://" . $_SERVER['HTTP_HOST'] . "/school_project/register_invite.php?token=" . $invite_token; ?>
            </code>
            <button onclick="copyInviteLink()" class="mt-2 text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Copy Link</button>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Company Info Card -->
            <div class="bg-white rounded-xl shadow-sm p-6 info-card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="ri-building-line text-blue-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">Company Information</h3>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-gray-600">Company Name:</span>
                        <span class="font-medium"><?php echo htmlspecialchars($company['name']); ?></span>
                    </div>
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-gray-600">Subscription Plan:</span>
                        <span class="font-medium capitalize"><?php echo $company['subscription_plan']; ?></span>
                    </div>
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-gray-600">Status:</span>
                        <span class="px-2 py-1 text-xs rounded-full <?php echo $company['subscription_status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                            <?php echo ucfirst($company['subscription_status']); ?>
                        </span>
                    </div>
                    <div class="flex justify-between py-2 border-b">
                        <span class="text-gray-600">Member Since:</span>
                        <span class="font-medium"><?php echo date('F j, Y', strtotime($company['created_at'])); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Usage Stats Card -->
            <div class="bg-white rounded-xl shadow-sm p-6 info-card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="ri-bar-chart-2-line text-green-600 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800">Usage Statistics</h3>
                </div>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span>Drivers (<?php echo $stats['driver_count']; ?>/<?php echo $company['max_drivers'] == 999999 ? '∞' : $company['max_drivers']; ?>)</span>
                            <span class="text-gray-500"><?php echo round(($stats['driver_count'] / max($company['max_drivers'], 1)) * 100); ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?php echo $stats['driver_count'] >= $company['max_drivers'] && $company['max_drivers'] != 999999 ? 'danger-fill' : ''; ?>" style="width: <?php echo min(($stats['driver_count'] / max($company['max_drivers'], 1)) * 100, 100); ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span>Trucks (<?php echo $stats['truck_count']; ?>/<?php echo $company['max_trucks'] == 999999 ? '∞' : $company['max_trucks']; ?>)</span>
                            <span class="text-gray-500"><?php echo round(($stats['truck_count'] / max($company['max_trucks'], 1)) * 100); ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?php echo $stats['truck_count'] >= $company['max_trucks'] && $company['max_trucks'] != 999999 ? 'danger-fill' : ''; ?>" style="width: <?php echo min(($stats['truck_count'] / max($company['max_trucks'], 1)) * 100, 100); ?>%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span>Managers (<?php echo $stats['manager_count']; ?>/<?php echo $company['max_managers'] == 999999 ? '∞' : $company['max_managers']; ?>)</span>
                            <span class="text-gray-500"><?php echo round(($stats['manager_count'] / max($company['max_managers'], 1)) * 100); ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill <?php echo $stats['manager_count'] >= $company['max_managers'] && $company['max_managers'] != 999999 ? 'danger-fill' : ''; ?>" style="width: <?php echo min(($stats['manager_count'] / max($company['max_managers'], 1)) * 100, 100); ?>%"></div>
                        </div>
                    </div>
                </div>
                <?php if($stats['driver_count'] >= $company['max_drivers'] || $stats['truck_count'] >= $company['max_trucks']): ?>
                <div class="mt-4 p-3 bg-yellow-50 rounded-lg text-sm text-yellow-800">
                    <i class="ri-alert-line"></i> You've reached your limit. Consider upgrading your plan.
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Upgrade Plan Card -->
        <div class="bg-white rounded-xl shadow-sm p-6 mt-6 info-card">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="ri-rocket-line text-purple-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800">Upgrade Plan</h3>
            </div>
            <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input type="hidden" name="action" value="upgrade_plan">
                <div class="col-span-2">
                    <select name="plan" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="standard" <?php echo $company['subscription_plan'] == 'standard' ? 'selected' : ''; ?>>Standard - $49/mo (5 drivers, 5 trucks, 2 managers)</option>
                        <option value="premium" <?php echo $company['subscription_plan'] == 'premium' ? 'selected' : ''; ?>>Premium - $99/mo (20 drivers, 20 trucks, 5 managers)</option>
                        <option value="extra" <?php echo $company['subscription_plan'] == 'extra' ? 'selected' : ''; ?>>Extra - $199/mo (Unlimited)</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        Upgrade Now
                    </button>
                </div>
                <div class="text-right text-sm text-gray-500">
                    <?php if($company['subscription_plan'] == 'standard'): ?>
                    Upgrade to Premium for more features
                    <?php elseif($company['subscription_plan'] == 'premium'): ?>
                    Go Unlimited with Extra plan
                    <?php else: ?>
                    You're on the Unlimited plan
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Invite Team Members Card -->
        <div class="bg-white rounded-xl shadow-sm p-6 mt-6 info-card">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="ri-user-add-line text-orange-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800">Invite Team Members</h3>
            </div>
            
            <form method="POST" class="mb-6">
                <input type="hidden" name="action" value="create_invite">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="email" placeholder="colleague@company.com" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select name="role" class="w-full px-3 py-2 border rounded-lg">
                            <option value="manager">Manager - Full operational access</option>
                            <option value="driver">Driver - Limited access (mobile app only)</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center justify-center gap-2">
                            <i class="ri-send-plane-line"></i> Send Invitation
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if(!empty($invitations)): ?>
            <div class="mt-6">
                <h4 class="font-medium text-gray-700 mb-3 flex items-center gap-2">
                    <i class="ri-time-line"></i> Pending Invitations
                </h4>
                <div class="space-y-2">
                    <?php foreach($invitations as $invite): ?>
                    <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <i class="ri-mail-line text-gray-400"></i>
                                <p class="font-medium text-gray-800"><?php echo htmlspecialchars($invite['email']); ?></p>
                                <span class="px-2 py-0.5 text-xs rounded-full <?php echo $invite['role'] === 'manager' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?>">
                                    <?php echo ucfirst($invite['role']); ?>
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="ri-calendar-line"></i> Expires: <?php echo date('M d, Y', strtotime($invite['expires_at'])); ?>
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="copyInviteLink('<?php echo $invite['token']; ?>')" class="text-blue-600 hover:text-blue-800 text-sm px-2 py-1">
                                <i class="ri-file-copy-line"></i> Copy Link
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Team Members List -->
        <div class="bg-white rounded-xl shadow-sm p-6 mt-6 info-card">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <i class="ri-team-line text-indigo-600 text-xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800">Team Members</h3>
            </div>
            
            <?php
            $teamSql = "SELECT u.id, u.username, u.email, u.role, u.is_active, u.last_login 
                        FROM users u
                        WHERE u.company_id = :company_id AND u.id != :user_id
                        ORDER BY u.role, u.username";
            $teamStmt = $db->prepare($teamSql);
            $teamStmt->bindParam(':company_id', $company_id);
            $teamStmt->bindParam(':user_id', $_SESSION['user_id']);
            $teamStmt->execute();
            $teamMembers = $teamStmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            
            <?php if(empty($teamMembers)): ?>
            <div class="text-center py-8 text-gray-500">
                <i class="ri-user-line text-4xl mb-2 block"></i>
                <p>No team members yet. Invite your colleagues to get started!</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Last Login</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($teamMembers as $member): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium"><?php echo htmlspecialchars($member['username']); ?></td>
                            <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($member['email']); ?></td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $member['role'] === 'manager' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?>">
                                    <?php echo ucfirst($member['role']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $member['is_active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                    <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm"><?php echo $member['last_login'] ? date('Y-m-d', strtotime($member['last_login'])) : 'Never'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
function copyInviteLink(token) {
    const inviteLink = window.location.origin + '/school_project/register_invite.php?token=' + token;
    
    // Modern way to copy to clipboard
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(inviteLink).then(function() {
            alert('Invite link copied! Share it with the user.\n\n' + inviteLink);
        }).catch(function() {
            // Fallback
            copyFallback(inviteLink);
        });
    } else {
        // Fallback for older browsers
        copyFallback(inviteLink);
    }
}

function copyFallback(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
}
</script>
</body>
</html>