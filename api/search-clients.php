<?php
require_once "../config/database.php";
header('Content-Type: application/json');

$db   = new Database();
$conn = $db->getConnection();

$q      = trim($_GET['q']           ?? '');
$statut = trim($_GET['statut']      ?? '');
$type   = trim($_GET['type_client'] ?? '');

$sql    = "SELECT id, nom, prenom, cin, telephone, email, type_client, statut, permis_numero, permis_expiration FROM clients WHERE 1=1";
$params = [];

if ($q) {
    $sql .= " AND (nom LIKE :q OR prenom LIKE :q OR cin LIKE :q OR telephone LIKE :q OR email LIKE :q)";
    $params[':q'] = "%$q%";
}
if ($statut) {
    $sql .= " AND statut = :statut";
    $params[':statut'] = $statut;
}
if ($type) {
    $sql .= " AND type_client = :type";
    $params[':type'] = $type;
}

$sql .= " ORDER BY nom, prenom LIMIT 100";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$clients = $stmt->fetchAll();

// Add permis status
foreach ($clients as &$c) {
    $c['permis_expired']  = false;
    $c['permis_expiring'] = false;
    if ($c['permis_expiration']) {
        $exp  = new DateTime($c['permis_expiration']);
        $now  = new DateTime();
        $in30 = new DateTime('+30 days');
        if ($exp < $now)   $c['permis_expired']  = true;
        elseif ($exp < $in30) $c['permis_expiring'] = true;
    }
}
unset($c);

echo json_encode($clients);
