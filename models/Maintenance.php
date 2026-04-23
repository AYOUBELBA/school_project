<?php
class Maintenance {
    private $conn;
    private $table = "truck_maintenance";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getAll() {
        $company_id = $_SESSION['company_id'] ?? 0;
        $query = "SELECT m.*, t.plate_number as truck_plate, t.model as truck_model
                  FROM " . $this->table . " m
                  INNER JOIN trucks t ON m.truck_id = t.id
                  WHERE t.company_id = :company_id
                  ORDER BY m.maintenance_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUpcomingMaintenance($days = 30) {
        $company_id = $_SESSION['company_id'] ?? 0;
        $query = "SELECT m.*, t.plate_number as truck_plate, t.model as truck_model
                  FROM " . $this->table . " m
                  INNER JOIN trucks t ON m.truck_id = t.id
                  WHERE t.company_id = :company_id
                  AND m.next_due_date <= CURRENT_DATE + INTERVAL '$days days'
                  AND m.status != 'completed'
                  ORDER BY m.next_due_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        $company_id = $_SESSION['company_id'] ?? 0;
        $query = "SELECT m.*, t.plate_number as truck_plate
                  FROM " . $this->table . " m
                  INNER JOIN trucks t ON m.truck_id = t.id
                  WHERE m.id = :id AND t.company_id = :company_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':company_id', $company_id);
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
                  SET truck_id = :truck_id,
                      maintenance_type = :maintenance_type,
                      description = :description,
                      cost = :cost,
                      maintenance_date = :maintenance_date,
                      next_due_date = :next_due_date,
                      odometer_at_service = :odometer_at_service,
                      performed_by = :performed_by,
                      status = :status,
                      notes = :notes
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
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
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
        $query = "SELECT id, plate_number, model FROM trucks 
                  WHERE status = 'active' AND company_id = :company_id 
                  ORDER BY plate_number";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':company_id', $company_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>