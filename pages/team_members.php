<?php
require_once '../includes/check_auth.php';

// Only owner can access
if($_SESSION['user_role'] !== 'owner') {
    header("Location: dashboard.php");
    exit();
}

$company_id = $_SESSION['company_id'];

// Handle removing a team member
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if($_POST['action'] === 'remove_member') {
        $user_id = $_POST['user_id'];
        
        if($user_id == $_SESSION['user_id']) {
            $_SESSION['flash_error'] = "You cannot remove yourself.";
        } else {
            $getSql = "SELECT role FROM users WHERE id = :user_id AND company_id = :company_id";
            $getStmt = $db->prepare($getSql);
            $getStmt->bindParam(':user_id', $user_id);
            $getStmt->bindParam(':company_id', $company_id);
            $getStmt->execute();
            $user = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            if($user) {
                if($user['role'] === 'driver') {
                    $delDriverSql = "DELETE FROM drivers WHERE user_id = :user_id";
                    $delDriverStmt = $db->prepare($delDriverSql);
                    $delDriverStmt->bindParam(':user_id', $user_id);
                    $delDriverStmt->execute();
                }
                
                $delSql = "DELETE FROM users WHERE id = :user_id AND company_id = :company_id";
                $delStmt = $db->prepare($delSql);
                $delStmt->bindParam(':user_id', $user_id);
                $delStmt->bindParam(':company_id', $company_id);
                $delStmt->execute();
                
                $_SESSION['flash_success'] = "Team member removed successfully.";
            }
        }
        header("Location: team_members.php");
        exit();
    }
    
    // Cancel invitation
    if($_POST['action'] === 'cancel_invitation') {
        $invite_id = $_POST['invite_id'];
        
        $cancelSql = "DELETE FROM invitations WHERE id = :invite_id AND company_id = :company_id";
        $cancelStmt = $db->prepare($cancelSql);
        $cancelStmt->bindParam(':invite_id', $invite_id);
        $cancelStmt->bindParam(':company_id', $company_id);
        $cancelStmt->execute();
        
        $_SESSION['flash_success'] = "Invitation cancelled successfully.";
        header("Location: team_members.php");
        exit();
    }
}

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Get all team members
$sql = "SELECT u.id, u.username, u.email, u.role, u.is_active, u.last_login, u.created_at
        FROM users u
        WHERE u.company_id = :company_id
        ORDER BY 
            CASE u.role 
                WHEN 'owner' THEN 1 
                WHEN 'manager' THEN 2 
                WHEN 'driver' THEN 3 
                ELSE 4 
            END, 
            u.username";
$stmt = $db->prepare($sql);
$stmt->bindParam(':company_id', $company_id);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get pending invitations
$inviteSql = "SELECT * FROM invitations WHERE company_id = :company_id AND is_used = false AND expires_at > CURRENT_TIMESTAMP ORDER BY created_at DESC";
$inviteStmt = $db->prepare($inviteSql);
$inviteStmt->bindParam(':company_id', $company_id);
$inviteStmt->execute();
$pendingInvites = $inviteStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Members | Fleet System</title>
    <script src="https://cdn.tailwindcss.com/3.4.1"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .role-badge { display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 500; }
        .role-owner { background: #dbeafe; color: #1e40af; }
        .role-manager { background: #d1fae5; color: #065f46; }
        .role-driver { background: #fed7aa; color: #92400e; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .status-pending { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<main class="flex-1 ml-72 overflow-y-auto">
    <?php include '../includes/header.php'; ?>
    
    <div class="p-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Team Members</h2>
                <p class="text-gray-500">Manage your company team members and invitations</p>
            </div>
            <a href="company_settings.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <i class="ri-user-add-line"></i> Invite Member
            </a>
        </div>
        
        <?php if($flash_success): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg"><?php echo $flash_success; ?></div>
        <?php endif; ?>
        
        <?php if($flash_error): ?>
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg"><?php echo $flash_error; ?></div>
        <?php endif; ?>
        
        <!-- Pending Invitations Section -->
        <?php if(!empty($pendingInvites)): ?>
        <div class="bg-white rounded-xl shadow-sm mb-8 overflow-hidden">
            <div class="p-4 border-b bg-yellow-50">
                <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="ri-time-line text-yellow-600"></i> Pending Invitations
                </h3>
                <p class="text-xs text-gray-500 mt-1">These invitations are waiting for users to accept</p>
            </div>
            <div class="divide-y divide-gray-200">
                <?php foreach($pendingInvites as $invite): ?>
                <div class="p-4 flex justify-between items-center hover:bg-gray-50">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <i class="ri-mail-line text-gray-400"></i>
                            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($invite['email']); ?></span>
                            <span class="px-2 py-0.5 text-xs rounded-full <?php echo $invite['role'] === 'manager' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?>">
                                <?php echo ucfirst($invite['role']); ?>
                            </span>
                            <span class="px-2 py-0.5 text-xs rounded-full bg-yellow-100 text-yellow-700">
                                <i class="ri-time-line"></i> Pending
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            <i class="ri-calendar-line"></i> Expires: <?php echo date('M d, Y', strtotime($invite['expires_at'])); ?>
                        </p>
                        <p class="text-xs text-gray-400 mt-1 break-all">
                            <i class="ri-link"></i> Token: <?php echo substr($invite['token'], 0, 20); ?>...
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="copyInviteLink('<?php echo $invite['token']; ?>')" class="text-blue-600 hover:text-blue-800 text-sm px-3 py-1 rounded border border-blue-200 hover:bg-blue-50">
                            <i class="ri-file-copy-line"></i> Copy Link
                        </button>
                        <form method="POST" onsubmit="return confirm('Cancel this invitation? The invite link will no longer work.');">
                            <input type="hidden" name="action" value="cancel_invitation">
                            <input type="hidden" name="invite_id" value="<?php echo $invite['id']; ?>">
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm px-3 py-1 rounded border border-red-200 hover:bg-red-50">
                                <i class="ri-close-line"></i> Cancel
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Team Members Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b bg-gray-50">
                <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="ri-team-line"></i> Active Team Members
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Member Since</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Login</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach($members as $member): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium">
                                <?php echo htmlspecialchars($member['username']); ?>
                                <?php if($member['id'] == $_SESSION['user_id']): ?>
                                <span class="ml-2 text-xs text-blue-600">(You)</span>
                                <?php endif; ?>
                             </td>
                            <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($member['email']); ?></td>
                            <td class="px-6 py-4">
                                <span class="role-badge role-<?php echo $member['role']; ?>">
                                    <?php echo ucfirst($member['role']); ?>
                                </span>
                             </td>
                            <td class="px-6 py-4">
                                <span class="role-badge <?php echo $member['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                             </td>
                            <td class="px-6 py-4 text-sm"><?php echo date('M d, Y', strtotime($member['created_at'])); ?> </td>
                            <td class="px-6 py-4 text-sm"><?php echo $member['last_login'] ? date('M d, Y', strtotime($member['last_login'])) : 'Never'; ?> </td>
                            <td class="px-6 py-4 text-sm">
                                <?php if($member['role'] !== 'owner' && $member['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this team member? This action cannot be undone.');">
                                    <input type="hidden" name="action" value="remove_member">
                                    <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800">
                                        <i class="ri-delete-bin-line"></i> Remove
                                    </button>
                                </form>
                                <?php elseif($member['role'] === 'owner'): ?>
                                <span class="text-gray-400">Owner</span>
                                <?php else: ?>
                                <span class="text-gray-400">—</span>
                                <?php endif; ?>
                             </td>
                         </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Role Info Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <div class="bg-blue-50 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="ri-crown-line text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-blue-600 font-medium">Owner</p>
                        <p class="text-xs text-blue-500">Full access to everything</p>
                    </div>
                </div>
            </div>
            <div class="bg-green-50 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="ri-user-star-line text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-green-600 font-medium">Manager</p>
                        <p class="text-xs text-green-500">Operational access to all features</p>
                    </div>
                </div>
            </div>
            <div class="bg-orange-50 rounded-xl p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                        <i class="ri-driver-line text-orange-600"></i>
                    </div>
                    <div>
                        <p class="text-sm text-orange-600 font-medium">Driver</p>
                        <p class="text-xs text-orange-500">Limited access, mobile app only</p>
                    </div>
                </div>
            </div>
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