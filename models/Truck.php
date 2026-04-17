<?php
class Truck {
    private $conn;
    private $table = "trucks";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getAll() {
        $query = "SELECT t.*, d.full_name as driver_name, d.id as driver_id
                  FROM " . $this->table . " t
                  LEFT JOIN drivers d ON t.current_driver_id = d.id
                  ORDER BY t.id DESC";
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
                  (plate_number, model, brand, year, capacity_kg, fuel_type, status)
                  VALUES (:plate_number, :model, :brand, :year, :capacity_kg, :fuel_type, :status)
                  RETURNING id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':plate_number', $data['plate_number']);
        $stmt->bindParam(':model', $data['model']);
        $stmt->bindParam(':brand', $data['brand']);
        $stmt->bindParam(':year', $data['year']);
        $stmt->bindParam(':capacity_kg', $data['capacity_kg']);
        $stmt->bindParam(':fuel_type', $data['fuel_type']);
        $stmt->bindParam(':status', $data['status']);
        
        if($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        }
        return false;
    }
    
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . "
                  SET plate_number = :plate_number, 
                      model = :model, 
                      brand = :brand,
                      year = :year, 
                      capacity_kg = :capacity_kg,
                      fuel_type = :fuel_type,
                      status = :status,
                      current_driver_id = :current_driver_id
                  WHERE id = :id";
        
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
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active,
                    COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance,
                    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive
                  FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getAvailableDrivers() {
        $query = "SELECT id, full_name FROM drivers WHERE status = 'available'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>