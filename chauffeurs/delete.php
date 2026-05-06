<?php
require_once "../config/database.php";
require_once "../models/ChauffeurModel.php";

$db = new Database();
$conn = $db->getConnection();

$model = new ChauffeurModel($conn);
$id = $_GET['id'];

$model->delete($id);

header("Location: index.php");
exit();
?>