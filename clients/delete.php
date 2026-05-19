<?php
require_once "../config/database.php";
require_once "../models/ClientModel.php";
require_once "../includes/audit.php";

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
        // Get client name for audit
        $clientStmt = $conn->prepare("SELECT nom, prenom FROM clients WHERE id=?");
        $clientStmt->execute([$id]);
        $clientData = $clientStmt->fetch();
        $name = $clientData ? ($clientData['nom'] . ' ' . $clientData['prenom']) : "ID $id";

        $model->delete($id);
        audit_log($conn, 'DELETE', 'clients', $id, "Client supprimé : $name");
        flash('success', 'Client supprimé.');
    }
}
header("Location: index.php");
exit;
