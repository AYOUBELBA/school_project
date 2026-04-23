<?php
session_start();
if($_SESSION['user_role'] !== 'super_admin') {
    die('Unauthorized');
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$backupFile = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $backupFile . '"');

// Get all tables
$tables = ['users', 'companies', 'drivers', 'trucks', 'trips', 'routes', 'shipments', 'truck_locations', 'truck_maintenance', 'fuel_records', 'invitations', 'activity_logs', 'notifications'];

foreach($tables as $table) {
    $stmt = $db->query("SELECT * FROM $table");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "-- Table: $table\n";
    if(!empty($rows)) {
        $columns = array_keys($rows[0]);
        echo "INSERT INTO $table (" . implode(',', $columns) . ") VALUES\n";
        $values = [];
        foreach($rows as $row) {
            $vals = [];
            foreach($row as $col => $val) {
                $vals[] = $val === null ? 'NULL' : "'" . addslashes($val) . "'";
            }
            $values[] = "(" . implode(',', $vals) . ")";
        }
        echo implode(",\n", $values) . ";\n\n";
    }
}
?>