<?php
class Shipment {
    private $conn;
    private $table = "shipments";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getAll() {
        $query = "SELECT s.*, t.trip_number, tr.plate_number as truck_plate
                  FROM " . $this->table . " s
                  LEFT JOIN trips t ON s.trip_id = t.id
                  LEFT JOIN trucks tr ON t.truck_id = tr.id
                  ORDER BY s.created_at DESC";
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
                  (shipment_number, trip_id, description, weight_kg, quantity, dimensions, cargo_type, status)
                  VALUES (:shipment_number, :trip_id, :description, :weight_kg, :quantity, :dimensions, :cargo_type, :status)
                  RETURNING id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':shipment_number', $data['shipment_number']);
        $stmt->bindParam(':trip_id', $data['trip_id']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':weight_kg', $data['weight_kg']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':dimensions', $data['dimensions']);
        $stmt->bindParam(':cargo_type', $data['cargo_type']);
        $stmt->bindParam(':status', $data['status']);
        
        if($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        }
        return false;
    }
    
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . "
                  SET shipment_number = :shipment_number, trip_id = :trip_id, description = :description,
                      weight_kg = :weight_kg, quantity = :quantity, dimensions = :dimensions,
                      cargo_type = :cargo_type, status = :status
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':shipment_number', $data['shipment_number']);
        $stmt->bindParam(':trip_id', $data['trip_id']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':weight_kg', $data['weight_kg']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':dimensions', $data['dimensions']);
        $stmt->bindParam(':cargo_type', $data['cargo_type']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    public function generateShipmentNumber() {
        // Fixed PostgreSQL compatible version
        $query = "SELECT COALESCE(
                    (SELECT MAX(CAST(SUBSTRING(shipment_number FROM 5) AS INTEGER)) 
                     FROM " . $this->table . " 
                     WHERE shipment_number ~ '^SHP-[0-9]+$'), 
                    0) as max_num";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextNum = $result['max_num'] + 1;
        return 'SHP-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
    
    public function getTrips() {
        $query = "SELECT id, trip_number FROM trips WHERE status IN ('planned', 'ongoing') ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>