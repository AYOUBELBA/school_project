<?php
require_once 'config/database.php';
require_once 'config/auth.php';

session_start();
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->logout();

header("Location: login.php");
exit();
?>