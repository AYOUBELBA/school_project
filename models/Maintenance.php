<?php
class Maintenance {
    private $conn;
    private $table = "truck_maintenance";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getAll() {
        $query = "SELECT m.*, t.plate_number as truck_plate, t.model as truck_model
                  FROM " . $this->table . " m
                  LEFT JOIN trucks t ON m.truck_id = t.id
                  ORDER BY m.maintenance_date DESC";
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
                  (truck_id, maintenance_type, description, cost, maintenance_date, next_due_date, 
                   odometer_at_service, performed_by, status, notes)
                  VALUES (:truck_id, :maintenance_type, :description, :cost, :maintenance_date, :next_due_date,
                          :odometer_at_service, :performed_by, :status, :notes)
                  RETURNING id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':truck_id', $data['truck_id']);
        $stmt->bindParam(':maintenance_type', $data['maintenance_type']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':cost', $data['cost']);
        $stmt->bindParam(':maintenance_date', $data['maintenance_date']);
        $stmt->bindParam(':next_due_date', $data['next_due_date']);
        $stmt->bindParam(':odometer_at_service', $data['odometer_at_service']);
        $stmt->bindParam(':performed_by', $data['performed_by']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':notes', $data['notes']);
        
        if($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        }
        return false;
    }
    
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . "
                  SET truck_id = :truck_id, maintenance_type = :maintenance_type, description = :description,
                      cost = :cost, maintenance_date = :maintenance_date, next_due_date = :next_due_date,
                      odometer_at_service = :odometer_at_service, performed_by = :performed_by,
                      status = :status, notes = :notes
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':truck_id', $data['truck_id']);
        $stmt->bindParam(':maintenance_type', $data['maintenance_type']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':cost', $data['cost']);
        $stmt->bindParam(':maintenance_date', $data['maintenance_date']);
        $stmt->bindParam(':next_due_date', $data['next_due_date']);
        $stmt->bindParam(':odometer_at_service', $data['odometer_at_service']);
        $stmt->bindParam(':performed_by', $data['performed_by']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    public function getTrucks() {
        $query = "SELECT id, plate_number, model FROM trucks WHERE status = 'active' ORDER BY plate_number";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>