<?php
require_once "../config/database.php";

$db = new Database();
$conn = $db->getConnection();

$id = $_GET['id'];

// 🔥 récupérer team avant suppression
$team = $conn->prepare("SELECT * FROM mission_team WHERE mission_id=?");
$team->execute([$id]);
$members = $team->fetchAll();

// 🔄 remettre disponible
foreach ($members as $m) {

    // véhicule
    if ($m['vehicle_id']) {
        $conn->prepare("UPDATE vehicles SET statut='disponible' WHERE id=?")
             ->execute([$m['vehicle_id']]);
    }

    // chauffeur
    if ($m['chauffeur_id']) {
        $conn->prepare("UPDATE chauffeurs SET statut='disponible' WHERE id=?")
             ->execute([$m['chauffeur_id']]);
    }
}

// 🔥 supprimer team
$conn->prepare("DELETE FROM mission_team WHERE mission_id=?")->execute([$id]);

// 🔥 supprimer mission
$conn->prepare("DELETE FROM missions WHERE id=?")->execute([$id]);

header("Location: index.php");