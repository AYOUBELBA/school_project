<?php
class Driver {
    private $conn;
    private $table = "drivers";
    
    public function __construct($db) {
    $this->conn = $db;
    // Ensure company_id is in session
    if(isset($_SESSION['user_id']) && !isset($_SESSION['company_id'])) {
        // Try to fetch it
        $sql = "SELECT company_id FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if($user) {
            $_SESSION['company_id'] = $user['company_id'];
        }
    }
}
    
    public function getAll() {
        $company_id = $_SESSION['company_id'];
        $query = "SELECT d.*, u.email, u.username, t.id as truck_id, t.plate_number as truck_plate
                FROM " . $this->table . " d
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN trucks t ON t.current_driver_id = d.id
                WHERE d.company_id = :company_id
                ORDER BY d.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
    public function getById($id) {
        $company_id = $_SESSION['company_id'];
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND company_id = :company_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $company_id = $_SESSION['company_id'];
        
        // Check limit
        $limitCheck = checkCompanyLimit($company_id, 'driver');
        if(!$limitCheck['allowed']) {
            $_SESSION['flash_error'] = $limitCheck['message'];
            return false;
        }
        
        $query = "INSERT INTO " . $this->table . "
              (user_id, company_id, full_name, phone, license_number, license_expiry, status, hire_date, address)
              VALUES (:user_id, :company_id, :full_name, :phone, :license_number, :license_expiry, :status, :hire_date, :address)
              RETURNING id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $data['user_id']);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':license_number', $data['license_number']);
        $stmt->bindParam(':license_expiry', $data['license_expiry']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':hire_date', $data['hire_date']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':company_id', $data['company_id']);
        
        if($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        }
        return false;
    }
    
    public function update($id, $data) {
        $company_id = $_SESSION['company_id'];
        $query = "UPDATE " . $this->table . "
                  SET full_name = :full_name,
                      phone = :phone,
                      license_number = :license_number,
                      license_expiry = :license_expiry,
                      status = :status,
                      hire_date = :hire_date,
                      address = :address
                  WHERE id = :id AND company_id = :company_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':license_number', $data['license_number']);
        $stmt->bindParam(':license_expiry', $data['license_expiry']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':hire_date', $data['hire_date']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':company_id', $company_id);
        
        return $stmt->execute();
    }
    
    public function delete($id) {
        $company_id = $_SESSION['company_id'];
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND company_id = :company_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':company_id', $company_id);
        return $stmt->execute();
    }
    
    public function getStats() {
        $company_id = $_SESSION['company_id'];
        $query = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'available' THEN 1 END) as available,
                    COUNT(CASE WHEN status = 'on_trip' THEN 1 END) as on_trip,
                    COUNT(CASE WHEN status = 'off' THEN 1 END) as off_duty
                  FROM " . $this->table . "
                  WHERE company_id = :company_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getExpiringLicenses($days = 30) {
        $company_id = $_SESSION['company_id'];
        $query = "SELECT * FROM " . $this->table . "
                  WHERE license_expiry <= CURRENT_DATE + INTERVAL '$days days'
                  AND license_expiry >= CURRENT_DATE
                  AND company_id = :company_id
                  ORDER BY license_expiry ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>