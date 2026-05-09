<?php
require_once "../config/database.php";

$db = new Database();
$conn = $db->getConnection();

$id = $_POST['id'];
$commentaire = $_POST['commentaire'] ?? 'Mission terminée';

try {

    $conn->beginTransaction();

    // ✅ 1. Mettre à jour mission + date_fin
    $stmt = $conn->prepare("
        UPDATE missions 
        SET statut = 'terminée',
            date_fin = NOW(),
            commentaire = ?
        WHERE id = ?
    ");
    $stmt->execute([$commentaire, $id]);

    // ✅ 2. récupérer l’équipe
    $team = $conn->prepare("
        SELECT * FROM mission_affectations WHERE mission_id = ?
    ");
    $team->execute([$id]);
    $members = $team->fetchAll();

    // ✅ 3. libérer véhicules et chauffeurs
    foreach ($members as $m) {

        if ($m['vehicle_id']) {
            $conn->prepare("
                UPDATE vehicles 
                SET statut = 'disponible'
                WHERE id = ?
            ")->execute([$m['vehicle_id']]);
        }

        if ($m['chauffeur_id']) {
            $conn->prepare("
                UPDATE chauffeurs 
                SET statut = 'disponible'
                WHERE id = ?
            ")->execute([$m['chauffeur_id']]);
        }
    }

    $conn->commit();

    header("Location: index.php");
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    echo "Erreur : " . $e->getMessage();
}