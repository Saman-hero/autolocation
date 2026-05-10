<?php
require_once "../config/database.php";
require_once "../models/ReservationModel.php";

$db    = new Database();
$conn  = $db->getConnection();
$model = new ReservationModel($conn);

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $r = $conn->prepare("SELECT * FROM reservations WHERE id=?");
    $r->execute([$id]);
    $r = $r->fetch();
    if ($r && $r['statut'] === 'en cours') {
        flash('danger', 'Impossible de supprimer une location en cours.');
    } else {
        $model->delete($id);
        flash('success', 'Réservation supprimée.');
    }
}
header("Location: index.php");
exit;
