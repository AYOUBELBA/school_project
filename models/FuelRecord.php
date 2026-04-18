<?php
class FuelRecord {
    private $conn;
    private $table = "fuel_records";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getAll() {
        $query = "SELECT f.*, t.plate_number as truck_plate
                  FROM " . $this->table . " f
                  LEFT JOIN trucks t ON f.truck_id = t.id
                  ORDER BY f.fuel_date DESC, f.fuel_time DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    public function getTrucks() {
        $query = "SELECT id, plate_number FROM trucks WHERE status = 'active' ORDER BY plate_number";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getStats() {
        $query = "SELECT 
                    SUM(liters) as total_liters,
                    SUM(total_cost) as total_cost,
                    AVG(cost_per_liter) as avg_cost
                  FROM " . $this->table . "
                  WHERE fuel_date >= DATE_TRUNC('month', CURRENT_DATE)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>