<?php
class Shipment {
    private $conn;
    private $table = "shipments";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getAll() {
        $query = "SELECT s.*, t.trip_number 
                  FROM " . $this->table . " s
                  LEFT JOIN trips t ON s.trip_id = t.id
                  ORDER BY s.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    
    public function generateShipmentNumber() {
        $query = "SELECT 'SHP-' || LPAD(COALESCE(MAX(CAST(SUBSTRING(shipment_number FROM 5) AS INTEGER)), 0) + 1, 4, '0') as new_number 
                  FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['new_number'];
    }
}
?>