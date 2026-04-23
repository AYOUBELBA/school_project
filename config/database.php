<?php
class Database {
    private $host = "localhost";
    private $db_name = "truck_fleet_db";
    private $username = "postgres";
    private $password = "root";
    private $port = "5432";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES 'UTF8'");
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            die("Database connection failed. Please try again later.");
        }
        return $this->conn;
    }
}

// Helper function to get current user's company_id
function getCurrentCompanyId() {
    if(!isset($_SESSION['user_id'])) {
        return null;
    }
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        $sql = "SELECT company_id FROM users WHERE id = :user_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['company_id'] : null;
    } catch(Exception $e) {
        return null;
    }
}

// Helper function to check subscription limits
function checkCompanyLimit($company_id, $type) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get company subscription info
    $sql = "SELECT subscription_plan, max_drivers, max_trucks, max_managers, subscription_status 
            FROM companies WHERE id = :company_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$company || $company['subscription_status'] !== 'active') {
        return ['allowed' => false, 'message' => 'Subscription is not active'];
    }
    
    $currentCount = 0;
    $maxLimit = 0;
    $limitName = '';
    
    switch($type) {
        case 'driver':
            $countSql = "SELECT COUNT(*) as count FROM drivers WHERE company_id = :company_id";
            $currentCount = getCount($db, $countSql, $company_id);
            $maxLimit = $company['max_drivers'];
            $limitName = 'drivers';
            break;
        case 'truck':
            $countSql = "SELECT COUNT(*) as count FROM trucks WHERE company_id = :company_id";
            $currentCount = getCount($db, $countSql, $company_id);
            $maxLimit = $company['max_trucks'];
            $limitName = 'trucks';
            break;
        case 'manager':
            $countSql = "SELECT COUNT(*) as count FROM users WHERE company_id = :company_id AND role = 'manager'";
            $currentCount = getCount($db, $countSql, $company_id);
            $maxLimit = $company['max_managers'];
            $limitName = 'managers';
            break;
    }
    
    if($company['subscription_plan'] === 'extra') {
        return ['allowed' => true, 'message' => 'Unlimited'];
    }
    
    if($currentCount >= $maxLimit) {
        return ['allowed' => false, 'message' => "You have reached your {$limitName} limit ({$maxLimit}). Please upgrade your plan."];
    }
    
    return ['allowed' => true, 'message' => 'OK'];
}

function getCount($db, $sql, $company_id) {
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}
?>