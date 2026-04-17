<?php
class Database {
    private $host = "localhost";
    private $db_name = "truck_fleet_db";
    private $username = "postgres";
    private $password = "root";
    private $port = "5432";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES 'UTF8'");
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            die("Database connection failed. Please try again later.");
        }
        return $this->conn;
    }
}
?>