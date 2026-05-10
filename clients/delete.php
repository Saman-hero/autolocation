<?php
require_once "../config/database.php";
require_once "../models/ClientModel.php";

$db    = new Database();
$conn  = $db->getConnection();
$model = new ClientModel($conn);

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    // Vérifier s'il a des réservations actives
    $chk = $conn->prepare("SELECT COUNT(*) FROM reservations WHERE client_id=? AND statut IN ('en attente','confirmée','en cours')");
    $chk->execute([$id]);
    if ($chk->fetchColumn() > 0) {
        flash('danger', 'Impossible de supprimer ce client : il a des réservations actives.');
    } else {
        $model->delete($id);
        flash('success', 'Client supprimé.');
    }
}
header("Location: index.php");
exit;
