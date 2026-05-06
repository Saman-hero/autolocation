<?php
require_once "../config/database.php";
require_once "../models/VehicleModel.php";

$db = new Database();
$conn = $db->getConnection();

$model = new VehicleModel($conn);

if(isset($_GET['id'])) {
    $model->delete($_GET['id']);
}

header("Location: index.php");
exit;