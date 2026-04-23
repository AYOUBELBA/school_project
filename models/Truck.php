<?php
class Truck {
    private $conn;
    private $table = "trucks";
    
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
    // Get company_id with fallback
    $company_id = $_SESSION['company_id'] ?? null;
    
    // If no company_id, return empty array (user shouldn't see anything)
    if(!$company_id) {
        return [];
    }
    
    $query = "SELECT t.*, d.full_name as driver_name, d.id as driver_id
              FROM " . $this->table . " t
              LEFT JOIN drivers d ON t.current_driver_id = d.id
              WHERE t.company_id = :company_id
              ORDER BY t.id DESC";
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
        $limitCheck = checkCompanyLimit($company_id, 'truck');
        if(!$limitCheck['allowed']) {
            $_SESSION['flash_error'] = $limitCheck['message'];
            return false;
        }
        
        $query = "INSERT INTO " . $this->table . "
                  (plate_number, model, brand, year, capacity_kg, fuel_type, status, company_id, created_at)
                  VALUES (:plate_number, :model, :brand, :year, :capacity_kg, :fuel_type, :status, :company_id, CURRENT_TIMESTAMP)
                  RETURNING id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':plate_number', $data['plate_number']);
        $stmt->bindParam(':model', $data['model']);
        $stmt->bindParam(':brand', $data['brand']);
        $stmt->bindParam(':year', $data['year']);
        $stmt->bindParam(':capacity_kg', $data['capacity_kg']);
        $stmt->bindParam(':fuel_type', $data['fuel_type']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':company_id', $company_id);
        
        if($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        }
        return false;
    }
    
    public function update($id, $data) {
        $company_id = $_SESSION['company_id'];
        
        $query = "UPDATE " . $this->table . "
                  SET plate_number = :plate_number, 
                      model = :model, 
                      brand = :brand,
                      year = :year, 
                      capacity_kg = :capacity_kg,
                      fuel_type = :fuel_type,
                      status = :status,
                      current_driver_id = :current_driver_id
                  WHERE id = :id AND company_id = :company_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':plate_number', $data['plate_number']);
        $stmt->bindParam(':model', $data['model']);
        $stmt->bindParam(':brand', $data['brand']);
        $stmt->bindParam(':year', $data['year']);
        $stmt->bindParam(':capacity_kg', $data['capacity_kg']);
        $stmt->bindParam(':fuel_type', $data['fuel_type']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':current_driver_id', $data['current_driver_id']);
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
    $company_id = $_SESSION['company_id'] ?? null;
    if(!$company_id) {
        return ['total' => 0, 'active' => 0, 'maintenance' => 0, 'inactive' => 0];
    }
    
    $query = "SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
                COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance,
                COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive
              FROM " . $this->table . "
              WHERE company_id = :company_id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
    
    public function getAvailableDrivers() {
        $company_id = $_SESSION['company_id'];
        $query = "SELECT d.id, d.full_name 
                  FROM drivers d
                  WHERE d.status = 'available' AND d.company_id = :company_id
                  ORDER BY d.full_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getTrucksWithLocation() {
        $company_id = $_SESSION['company_id'];
        $query = "SELECT t.id, t.plate_number, t.current_location_lat, t.current_location_lng, 
                         t.last_location_update, d.full_name as driver_name,
                         (SELECT speed_kmh FROM truck_locations WHERE truck_id = t.id ORDER BY recorded_at DESC LIMIT 1) as speed
                  FROM " . $this->table . " t
                  LEFT JOIN drivers d ON t.current_driver_id = d.id
                  WHERE t.company_id = :company_id
                  ORDER BY t.id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>