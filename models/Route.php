<?php
class Route {
    private $conn;
    private $table = "routes";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getAll() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY id DESC";
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
                  (name, start_point, end_point, start_lat, start_lng, end_lat, end_lng, 
                   estimated_distance_km, estimated_duration_min, is_active)
                  VALUES (:name, :start_point, :end_point, :start_lat, :start_lng, :end_lat, :end_lng,
                          :estimated_distance_km, :estimated_duration_min, :is_active)
                  RETURNING id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':start_point', $data['start_point']);
        $stmt->bindParam(':end_point', $data['end_point']);
        $stmt->bindParam(':start_lat', $data['start_lat']);
        $stmt->bindParam(':start_lng', $data['start_lng']);
        $stmt->bindParam(':end_lat', $data['end_lat']);
        $stmt->bindParam(':end_lng', $data['end_lng']);
        $stmt->bindParam(':estimated_distance_km', $data['estimated_distance_km']);
        $stmt->bindParam(':estimated_duration_min', $data['estimated_duration_min']);
        $stmt->bindParam(':is_active', $data['is_active']);
        
        if($stmt->execute()) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['id'];
        }
        return false;
    }
    
    public function update($id, $data) {
        $query = "UPDATE " . $this->table . "
                  SET name = :name, start_point = :start_point, end_point = :end_point,
                      start_lat = :start_lat, start_lng = :start_lng, end_lat = :end_lat, end_lng = :end_lng,
                      estimated_distance_km = :estimated_distance_km, estimated_duration_min = :estimated_duration_min,
                      is_active = :is_active
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':start_point', $data['start_point']);
        $stmt->bindParam(':end_point', $data['end_point']);
        $stmt->bindParam(':start_lat', $data['start_lat']);
        $stmt->bindParam(':start_lng', $data['start_lng']);
        $stmt->bindParam(':end_lat', $data['end_lat']);
        $stmt->bindParam(':end_lng', $data['end_lng']);
        $stmt->bindParam(':estimated_distance_km', $data['estimated_distance_km']);
        $stmt->bindParam(':estimated_duration_min', $data['estimated_duration_min']);
        $stmt->bindParam(':is_active', $data['is_active']);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>