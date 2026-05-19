<?php
require_once "../config/database.php";
require_once "../includes/audit.php";

$db   = new Database();
$conn = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }

$r = $conn->prepare("SELECT * FROM reservations WHERE id=?");
$r->execute([$id]);
$r = $r->fetch();
if (!$r || !in_array($r['statut'], ['en attente','confirmée'])) {
    flash('danger', 'Réservation introuvable ou déjà en cours.');
    header("Location: index.php"); exit;
}

$kmDepart = (int)($_GET['km'] ?? 0) ?: null;

// Démarrer : mettre à jour statut + km départ + statut véhicule
$conn->prepare("UPDATE reservations SET statut='en cours', km_depart=? WHERE id=?")
     ->execute([$kmDepart, $id]);

$conn->prepare("UPDATE vehicles SET statut='loué' WHERE id=?")
     ->execute([$r['vehicle_id']]);

audit_log($conn, 'START', 'reservations', $id, "Location démarrée : réservation {$r['reference']}");

flash('success', 'Location démarrée.');
header("Location: view.php?id=$id");
exit;
