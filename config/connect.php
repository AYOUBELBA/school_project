<?php
try {
	$conn = new PDO("pgsql:host=localhost;dbname=truck_tracking", "postgres", "root");
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	die("Connection failed: " . $e->getMessage());
}



?>