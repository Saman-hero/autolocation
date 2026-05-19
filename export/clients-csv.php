<?php
require_once "../config/database.php";

$db   = new Database();
$conn = $db->getConnection();

$q      = trim($_GET['q']           ?? '');
$statut = trim($_GET['statut']      ?? '');
$type   = trim($_GET['type_client'] ?? '');

$sql    = "SELECT * FROM clients WHERE 1=1";
$params = [];
if ($q)      { $sql .= " AND (nom LIKE :q OR prenom LIKE :q OR cin LIKE :q OR email LIKE :q)"; $params[':q'] = "%$q%"; }
if ($statut) { $sql .= " AND statut = :statut"; $params[':statut'] = $statut; }
if ($type)   { $sql .= " AND type_client = :type"; $params[':type'] = $type; }
$sql .= " ORDER BY nom, prenom";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();

$filename = 'clients_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

fputcsv($out, ['ID', 'Nom', 'Prénom', 'CIN', 'Téléphone', 'Email', 'Type', 'Statut',
               'Permis N°', 'Permis Catégorie', 'Permis Expiration',
               'Entreprise', 'Adresse', 'Notes', 'Créé le'], ';');

foreach ($clients as $c) {
    fputcsv($out, [
        $c['id'],
        $c['nom'],
        $c['prenom'],
        $c['cin'] ?? '',
        $c['telephone'] ?? '',
        $c['email'] ?? '',
        $c['type_client'],
        $c['statut'],
        $c['permis_numero'] ?? '',
        $c['permis_categorie'] ?? '',
        $c['permis_expiration'] ?? '',
        $c['entreprise'] ?? '',
        $c['adresse'] ?? '',
        $c['notes'] ?? '',
        $c['created_at'] ?? '',
    ], ';');
}

fclose($out);
exit;
