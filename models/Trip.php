<?php
class Trip {
    private $conn;
    private $table = "trips";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getAll() {
        $query = "SELECT t.*, 
                  tr.plate_number as truck_plate, tr.model as truck_model,
                  d.full_name as driver_name,
                  r.name as route_name
                  FROM " . $this->table . " t
                  LEFT JOIN trucks tr ON t.truck_id = tr.id
                  LEFT JOIN drivers d ON t.driver_id = d.id
                  LEFT JOIN routes r ON t.route_id = r.id
                  ORDER BY t.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getActiveTrips() {
        $query = "SELECT t.*, 
                  tr.plate_number as truck_plate,
                  d.full_name as driver_name,
                  r.name as route_name
                  FROM " . $this->table . " t
                  LEFT JOIN trucks tr ON t.truck_id = tr.id
                  LEFT JOIN drivers d ON t.driver_id = d.id
                  LEFT JOIN routes r ON t.route_id = r.id
                  WHERE t.status IN ('planned', 'ongoing')
                  ORDER BY t.start_time ASC";
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
                  (trip_number, truck_id, driver_id, route_id, client_name, client_phone,
                   start_location, end_location, start_time, status, priority, notes, created_by)
                  VALUES (:trip_number, :truck_id, :driver_id, :route_id, :client_name, :client_phone,
                          :start_location, :end_location, :start_time, :status, :priority, :notes, :created_by)
                  RETURNING id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':trip_number', $data['trip_number']);
        $stmt->bindParam(':truck_id', $data['truck_id']);
        $stmt->bindParam(':driver_id', $data['driver_id']);
        $stmt->bindParam(':route_id', $data['route_id']);
        $stmt->bindParam(':client_name', $data['client_name']);
        $stmt->bindParam(':client_phone', $data['client_phone']);
        $stmt->bindParam(':start_location', $data['start_location']);
        $stmt->bindParam(':end_location', $data['end_location']);
        $stmt->bindParam(':start_time', $data['start_time']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':priority', $data['priority']);
        $stmt->bindParam(':notes', $data['notes']);
        $stmt->bindParam(':created_by', $data['created_by']);
        
        if($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        }
        return false;
    }
    
    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
    
    public function getStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'planned' THEN 1 END) as planned,
                    COUNT(CASE WHEN status = 'ongoing' THEN 1 END) as ongoing,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
                  FROM " . $this->table;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function generateTripNumber() {
        // Fixed PostgreSQL compatible version
        $query = "SELECT COALESCE(
                    (SELECT MAX(CAST(SUBSTRING(trip_number FROM 4) AS INTEGER)) 
                     FROM " . $this->table . " 
                     WHERE trip_number ~ '^TR-[0-9]+$'), 
                    0) as max_num";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextNum = $result['max_num'] + 1;
        return 'TR-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
}
?>