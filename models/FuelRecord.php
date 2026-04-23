<?php
class FuelRecord {
    private $conn;
    private $table = "fuel_records";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getAll() {
        $company_id = $_SESSION['company_id'] ?? 0;
        $query = "SELECT f.*, t.plate_number as truck_plate
                  FROM " . $this->table . " f
                  INNER JOIN trucks t ON f.truck_id = t.id
                  WHERE t.company_id = :company_id
                  ORDER BY f.fuel_date DESC, f.fuel_time DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStats() {
        $company_id = $_SESSION['company_id'] ?? 0;
        $query = "SELECT 
                    SUM(f.liters) as total_liters,
                    SUM(f.total_cost) as total_cost,
                    AVG(f.cost_per_liter) as avg_cost
                  FROM " . $this->table . " f
                  INNER JOIN trucks t ON f.truck_id = t.id
                  WHERE t.company_id = :company_id
                  AND f.fuel_date >= DATE_TRUNC('month', CURRENT_DATE)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function create($data) {
        $query = "INSERT INTO " . $this->table . "
                  (truck_id, liters, cost_per_liter, total_cost, odometer_km, fuel_station, fuel_date, fuel_time, created_by)
                  VALUES (:truck_id, :liters, :cost_per_liter, :total_cost, :odometer_km, :fuel_station, :fuel_date, :fuel_time, :created_by)
                  RETURNING id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':truck_id', $data['truck_id']);
        $stmt->bindParam(':liters', $data['liters']);
        $stmt->bindParam(':cost_per_liter', $data['cost_per_liter']);
        $stmt->bindParam(':total_cost', $data['total_cost']);
        $stmt->bindParam(':odometer_km', $data['odometer_km']);
        $stmt->bindParam(':fuel_station', $data['fuel_station']);
        $stmt->bindParam(':fuel_date', $data['fuel_date']);
        $stmt->bindParam(':fuel_time', $data['fuel_time']);
        $stmt->bindParam(':created_by', $data['created_by']);
        
        if($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        }
        return false;
    }
    
    public function delete($id) {
        $company_id = $_SESSION['company_id'] ?? 0;
        $query = "DELETE FROM " . $this->table . " 
                  WHERE id = :id 
                  AND truck_id IN (SELECT id FROM trucks WHERE company_id = :company_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':company_id', $company_id);
        return $stmt->execute();
    }
    
    public function getTrucks() {
        $company_id = $_SESSION['company_id'] ?? 0;
        $query = "SELECT id, plate_number FROM trucks 
                  WHERE status = 'active' AND company_id = :company_id 
                  ORDER BY plate_number";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>