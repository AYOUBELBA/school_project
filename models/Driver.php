<?php
class Driver {
    private $conn;
    private $table = "drivers";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getAll() {
        $query = "SELECT d.*, u.email, u.username, 
                t.id as truck_id, t.plate_number as truck_plate
                FROM " . $this->table . " d
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN trucks t ON t.current_driver_id = d.id
                ORDER BY d.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getExpiringLicenses($days = 30) {
    $query = "SELECT * FROM " . $this->table . "
              WHERE license_expiry <= CURRENT_DATE + INTERVAL '$days days'
              AND license_expiry >= CURRENT_DATE
              AND status != 'off'
              ORDER BY license_expiry ASC";
    $stmt = $this->conn->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $query = "INSERT INTO " . $this->table . "
                  (user_id, full_name, phone, license_number, license_expiry, status, hire_date, address)
                  VALUES (:user_id, :full_name, :phone, :license_number, :license_expiry, :status, :hire_date, :address)
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
        
        if($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        }
        return false;
    }
    
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . "
                  SET full_name = :full_name,
                      phone = :phone,
                      license_number = :license_number,
                      license_expiry = :license_expiry,
                      status = :status,
                      hire_date = :hire_date,
                      address = :address
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':license_number', $data['license_number']);
        $stmt->bindParam(':license_expiry', $data['license_expiry']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':hire_date', $data['hire_date']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'available' THEN 1 END) as available,
                    COUNT(CASE WHEN status = 'on_trip' THEN 1 END) as on_trip,
                    COUNT(CASE WHEN status = 'off' THEN 1 END) as off_duty
                  FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>