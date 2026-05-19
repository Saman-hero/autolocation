<?php
require_once "../config/database.php";
header('Content-Type: application/json');

$db   = new Database();
$conn = $db->getConnection();

$q         = trim($_GET['q']          ?? '');
$statut    = trim($_GET['statut']     ?? '');
$clientId  = (int)($_GET['client_id']  ?? 0);
$vehicleId = (int)($_GET['vehicle_id'] ?? 0);
$from      = $_GET['from'] ?? '';
$to        = $_GET['to']   ?? '';

$sql = "
    SELECT r.id, r.reference, r.statut, r.date_debut, r.date_fin_prevue,
           r.date_retour_effectif, r.nb_jours, r.montant_total, r.prix_jour,
           c.nom AS client_nom, c.prenom AS client_prenom,
           v.numero AS vehicle_numero, v.marque, v.modele
    FROM reservations r
    JOIN clients c ON r.client_id = c.id
    JOIN vehicles v ON r.vehicle_id = v.id
    WHERE 1=1
";
$params = [];

if ($q) {
    $sql .= " AND (r.reference LIKE :q OR c.nom LIKE :q OR c.prenom LIKE :q OR v.numero LIKE :q)";
    $params[':q'] = "%$q%";
}
if ($statut) {
    $sql .= " AND r.statut = :statut";
    $params[':statut'] = $statut;
}
if ($clientId) {
    $sql .= " AND r.client_id = :cid";
    $params[':cid'] = $clientId;
}
if ($vehicleId) {
    $sql .= " AND r.vehicle_id = :vid";
    $params[':vid'] = $vehicleId;
}
if ($from) {
    $sql .= " AND DATE(r.date_debut) >= :from";
    $params[':from'] = $from;
}
if ($to) {
    $sql .= " AND DATE(r.date_debut) <= :to";
    $params[':to'] = $to;
}

$sql .= " ORDER BY r.created_at DESC LIMIT 200";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$now = new DateTime();
foreach ($rows as &$row) {
    $row['retard'] = $row['statut'] === 'en cours'
        && $row['date_fin_prevue']
        && new DateTime($row['date_fin_prevue']) < $now;
}
unset($row);

echo json_encode($rows);
