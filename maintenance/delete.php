<?php
require_once "../config/database.php";

$db   = new Database();
$conn = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }

$record = $conn->prepare("SELECT * FROM maintenance WHERE id=?");
$record->execute([$id]);
$record = $record->fetch();

if ($record) {
    // Si la maintenance était en cours/planifiée, remettre le véhicule en disponible
    if (in_array($record['statut'], ['planifiée', 'en cours'])) {
        $conn->prepare("UPDATE vehicles SET statut='disponible' WHERE id=? AND statut='maintenance'")
             ->execute([$record['vehicle_id']]);
    }

    $conn->prepare("DELETE FROM maintenance WHERE id=?")->execute([$id]);
    flash('success', 'Maintenance supprimée.');
} else {
    flash('danger', 'Maintenance introuvable.');
}

header("Location: index.php");
exit;
