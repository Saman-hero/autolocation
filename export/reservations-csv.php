<?php
require_once "../config/database.php";

$db   = new Database();
$conn = $db->getConnection();

$q      = trim($_GET['q']      ?? '');
$statut = trim($_GET['statut'] ?? '');
$from   = $_GET['from'] ?? '';
$to     = $_GET['to']   ?? '';

$sql = "
    SELECT r.id, r.reference, r.statut, r.date_debut, r.date_fin_prevue,
           r.date_retour_effectif, r.nb_jours, r.prix_jour, r.caution,
           r.frais_extra, r.montant_total, r.km_depart, r.km_retour,
           r.lieu_depart, r.lieu_retour, r.commentaire, r.created_at,
           c.nom AS client_nom, c.prenom AS client_prenom, c.cin,
           v.numero AS vehicle_numero, v.marque, v.modele
    FROM reservations r
    JOIN clients c ON r.client_id = c.id
    JOIN vehicles v ON r.vehicle_id = v.id
    WHERE 1=1
";
$params = [];
if ($q)      { $sql .= " AND (r.reference LIKE :q OR c.nom LIKE :q OR v.numero LIKE :q)"; $params[':q'] = "%$q%"; }
if ($statut) { $sql .= " AND r.statut = :statut"; $params[':statut'] = $statut; }
if ($from)   { $sql .= " AND DATE(r.date_debut) >= :from"; $params[':from'] = $from; }
if ($to)     { $sql .= " AND DATE(r.date_debut) <= :to"; $params[':to'] = $to; }
$sql .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filename = 'reservations_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($out, [
    'ID', 'Référence', 'Statut', 'Client', 'CIN',
    'Véhicule', 'Marque', 'Modèle',
    'Date début', 'Date fin prévue', 'Date retour effectif',
    'Jours', 'Prix/jour', 'Caution', 'Frais extra', 'Total',
    'Km départ', 'Km retour', 'Lieu départ', 'Lieu retour',
    'Commentaire', 'Créé le'
], ';');

foreach ($rows as $r) {
    fputcsv($out, [
        $r['id'], $r['reference'], $r['statut'],
        $r['client_nom'] . ' ' . $r['client_prenom'], $r['cin'] ?? '',
        $r['vehicle_numero'], $r['marque'], $r['modele'],
        $r['date_debut'], $r['date_fin_prevue'], $r['date_retour_effectif'] ?? '',
        $r['nb_jours'], $r['prix_jour'], $r['caution'],
        $r['frais_extra'], $r['montant_total'],
        $r['km_depart'], $r['km_retour'],
        $r['lieu_depart'] ?? '', $r['lieu_retour'] ?? '',
        $r['commentaire'] ?? '', $r['created_at'],
    ], ';');
}

fclose($out);
exit;
