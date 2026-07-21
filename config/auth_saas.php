<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'database.php';
class Auth {
    private \PDO $conn;
    
    public function __construct(\PDO $db) {
        $this->conn = $db;
    }
    
public function login(string $email, string $password): array|false { 
    $query = "SELECT u.*, c.id as company_id, c.name as company_name, c.subscription_status 
              FROM users u
              LEFT JOIN companies c ON u.company_id = c.id
              WHERE u.email = :email AND u.is_active = true";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if(password_verify($password, $user['password_hash'])) {
            // Set ALL session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['company_id'] = $user['company_id'];
            $_SESSION['company_name'] = $user['company_name'];
            $_SESSION['subscription_status'] = $user['subscription_status'];
            $_SESSION['login_time'] = time();
            
            // Update last login
            $update = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id";
            $stmt2 = $this->conn->prepare($update);
            $stmt2->bindParam(':id', $user['id']);
            $stmt2->execute();
            
            $this->logActivity($user['id'], 'User logged in', 'users', $user['id']);
            
            return ['success' => true, 'role' => $user['role']];
        }
    }
    return ['success' => false, 'message' => 'Invalid credentials'];
}
    
    public function registerCompany($username, $email, $password, $companyName, $plan = 'standard') {
    try {
        $this->conn->beginTransaction();
        
        // Create company (NO trial_ends_at)
        $maxDrivers = $plan === 'standard' ? 5 : ($plan === 'premium' ? 20 : 999999);
        $maxTrucks = $plan === 'standard' ? 5 : ($plan === 'premium' ? 20 : 999999);
        $maxManagers = $plan === 'standard' ? 2 : ($plan === 'premium' ? 5 : 999999);
        
        $companySql = "INSERT INTO companies (name, subscription_plan, max_drivers, max_trucks, max_managers, 
                      subscription_status, created_at) 
                      VALUES (:name, :plan, :max_drivers, :max_trucks, :max_managers, 'active', CURRENT_TIMESTAMP) 
                      RETURNING id";
        $companyStmt = $this->conn->prepare($companySql);
        $companyStmt->bindParam(':name', $companyName);
        $companyStmt->bindParam(':plan', $plan);
        $companyStmt->bindParam(':max_drivers', $maxDrivers);
        $companyStmt->bindParam(':max_trucks', $maxTrucks);
        $companyStmt->bindParam(':max_managers', $maxManagers);
        $companyStmt->execute();
        $companyId = $companyStmt->fetch(PDO::FETCH_ASSOC)['id'];
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $userSql = "INSERT INTO users (username, email, password_hash, role, company_id, is_active, created_at) 
                    VALUES (:username, :email, :password_hash, 'owner', :company_id, true, CURRENT_TIMESTAMP) 
                    RETURNING id";
        $userStmt = $this->conn->prepare($userSql);
        $userStmt->bindParam(':username', $username);
        $userStmt->bindParam(':email', $email);
        $userStmt->bindParam(':password_hash', $hashed_password);
        $userStmt->bindParam(':company_id', $companyId);
        $userStmt->execute();
        $userId = $userStmt->fetch(PDO::FETCH_ASSOC)['id'];
        
        $updateCompany = "UPDATE companies SET owner_id = :owner_id WHERE id = :company_id";
        $updateStmt = $this->conn->prepare($updateCompany);
        $updateStmt->bindParam(':owner_id', $userId);
        $updateStmt->bindParam(':company_id', $companyId);
        $updateStmt->execute();
        
        $this->conn->commit();
        
        return ['success' => true, 'company_id' => $companyId, 'user_id' => $userId];
    } catch(PDOException $e) {
        $this->conn->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
    
    public function registerWithInvite($token, $username, $email, $password) {
        // Check if invitation exists and is valid
        $inviteSql = "SELECT i.*, c.name as company_name 
                      FROM invitations i
                      JOIN companies c ON i.company_id = c.id
                      WHERE i.token = :token AND i.is_used = false AND i.expires_at > CURRENT_TIMESTAMP";
        $inviteStmt = $this->conn->prepare($inviteSql);
        $inviteStmt->bindParam(':token', $token);
        $inviteStmt->execute();
        
        if($inviteStmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Invalid or expired invitation'];
        }
        
        $invite = $inviteStmt->fetch(PDO::FETCH_ASSOC);
        
        // Check email matches invitation
        if($invite['email'] !== $email) {
            return ['success' => false, 'message' => 'Email does not match invitation'];
        }
        
        try {
            $this->conn->beginTransaction();
            
            // Create user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $userSql = "INSERT INTO users (username, email, password_hash, role, company_id, is_active, created_at) 
                        VALUES (:username, :email, :password_hash, :role, :company_id, true, CURRENT_TIMESTAMP) 
                        RETURNING id";
            $userStmt = $this->conn->prepare($userSql);
            $userStmt->bindParam(':username', $username);
            $userStmt->bindParam(':email', $email);
            $userStmt->bindParam(':password_hash', $hashed_password);
            $userStmt->bindParam(':role', $invite['role']);
            $userStmt->bindParam(':company_id', $invite['company_id']);
            $userStmt->execute();
            
            // Mark invitation as used
            $updateInvite = "UPDATE invitations SET is_used = true WHERE id = :id";
            $updateStmt = $this->conn->prepare($updateInvite);
            $updateStmt->bindParam(':id', $invite['id']);
            $updateStmt->execute();
            
            $this->conn->commit();
            
            return ['success' => true, 'user_id' => $userStmt->fetch(PDO::FETCH_ASSOC)['id']];
        } catch(PDOException $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function createInvitation($company_id, $email, $role, $created_by) {
        // Check if user with this email already exists in company
        $checkSql = "SELECT id FROM users WHERE email = :email AND company_id = :company_id";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->bindParam(':company_id', $company_id);
        $checkStmt->execute();
        
        if($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'User with this email already exists in your company'];
        }
        
        // Check role limits for managers
        if($role === 'manager') {
            $limitCheck = checkCompanyLimit($company_id, 'manager');
            if(!$limitCheck['allowed']) {
                return ['success' => false, 'message' => $limitCheck['message']];
            }
        }
        
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $sql = "INSERT INTO invitations (company_id, email, role, token, expires_at, created_by, created_at) 
                VALUES (:company_id, :email, :role, :token, :expires_at, :created_by, CURRENT_TIMESTAMP)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':expires_at', $expiresAt);
        $stmt->bindParam(':created_by', $created_by);
        $stmt->execute();
        
        return ['success' => true, 'token' => $token];
    }
    
public function requirePermission($resourceType = null) {
    if(!$this->isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
    
    $role = $_SESSION['user_role'];
    $company_id = $_SESSION['company_id'];
    
    // Super admin has full access
    if($role === 'super_admin') {
        return true;
    }
    
    // Check subscription status
    if($_SESSION['subscription_status'] !== 'active') {
        $_SESSION['flash_error'] = 'Your subscription is not active. Please contact your administrator.';
        header("Location: dashboard.php");
        exit();
    }
    
    // Owner can access everything
    if($role === 'owner') {
        return true;
    }
    
    // Manager can access most operational features
    if($role === 'manager') {
        $allowedResources = [
            'dashboard', 'trucks', 'drivers', 'trips', 'routes', 'shipments', 
            'maintenance', 'fuel', 'reports', 'tracking', 'profile'
        ];
        if($resourceType && !in_array($resourceType, $allowedResources)) {
            header("Location: dashboard.php");
            exit();
        }
        return true;
    }
    
    // Driver permissions
    if($role === 'driver') {
        $allowedResources = ['driver_dashboard', 'tracking', 'profile', 'driver_track'];
        if($resourceType && !in_array($resourceType, $allowedResources)) {
            header("Location: driver_dashboard.php");
            exit();
        }
        return true;
    }
    
    return true;
}
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function logout() {
        if(isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'User logged out', 'users', $_SESSION['user_id']);
        }
        session_destroy();
        return true;
    }
    
    private function logActivity($userId, $action, $entityType, $entityId) {
        $query = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, ip_address, user_agent) 
                  VALUES (:user_id, :action, :entity_type, :entity_id, :ip_address, :user_agent)";
        $stmt = $this->conn->prepare($query);
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':entity_type', $entityType);
        $stmt->bindParam(':entity_id', $entityId);
        $stmt->bindParam(':ip_address', $ip);
        $stmt->bindParam(':user_agent', $agent);
        $stmt->execute();
    }
}
?>