<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
session_start();

require_once '../config/database.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['exists' => false, 'error' => 'Not logged in']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$invoice = $_GET['invoice'] ?? '';

if(empty($invoice)) {
    echo json_encode(['exists' => false]);
    exit();
}

$company_id = $_SESSION['company_id'];

$sql = "SELECT id FROM fuel_records f
        INNER JOIN trucks t ON f.truck_id = t.id
        WHERE f.invoice_number = :invoice AND t.company_id = :company_id";
$stmt = $db->prepare($sql);
$stmt->bindParam(':invoice', $invoice);
$stmt->bindParam(':company_id', $company_id);
$stmt->execute();

echo json_encode(['exists' => $stmt->rowCount() > 0]);
?>