<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Auth {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function login($email, $password) {
        $query = "SELECT * FROM users WHERE email = :email AND is_active = true";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_time'] = time();
                
                // Update last login
                $update = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = :id";
                $stmt2 = $this->conn->prepare($update);
                $stmt2->bindParam(':id', $user['id']);
                $stmt2->execute();
                
                // Log activity
                $this->logActivity($user['id'], 'User logged in', 'users', $user['id']);
                
                return ['success' => true, 'role' => $user['role']];
            }
        }
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    public function logout() {
        if(isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'User logged out', 'users', $_SESSION['user_id']);
        }
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function hasRole($roles) {
        if(!$this->isLoggedIn()) return false;
        if(!is_array($roles)) $roles = [$roles];
        return in_array($_SESSION['role'], $roles);
    }
    
    public function requireLogin() {
        if(!$this->isLoggedIn()) {
            header("Location: ../signin.php");
            exit();
        }
    }
    
    public function requireRole($roles) {
        $this->requireLogin();
        if(!$this->hasRole($roles)) {
            header("Location: dashboard.php");
            exit();
        }
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