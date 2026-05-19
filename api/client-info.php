<?php
require_once "../config/database.php";
header('Content-Type: application/json');

$db   = new Database();
$conn = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['error' => 'ID manquant']);
    exit;
}

$stmt = $conn->prepare("SELECT id, nom, prenom, cin, telephone, email, permis_numero, permis_expiration, statut FROM clients WHERE id = ?");
$stmt->execute([$id]);
$client = $stmt->fetch();

if (!$client) {
    echo json_encode(['error' => 'Client introuvable']);
    exit;
}

// Add license status info
$permisStatus = 'ok';
$permisMsg    = '';
if ($client['permis_expiration']) {
    $exp = new DateTime($client['permis_expiration']);
    $now = new DateTime();
    $in30 = new DateTime('+30 days');

    if ($exp < $now) {
        $permisStatus = 'expired';
        $permisMsg    = 'Permis expiré depuis le ' . $exp->format('d/m/Y');
    } elseif ($exp < $in30) {
        $daysLeft = (int)$now->diff($exp)->days;
        $permisStatus = 'expiring';
        $permisMsg    = 'Permis expire dans ' . $daysLeft . ' jour(s) (' . $exp->format('d/m/Y') . ')';
    }
}

echo json_encode([
    'id'               => $client['id'],
    'nom'              => $client['nom'],
    'prenom'           => $client['prenom'],
    'cin'              => $client['cin'],
    'telephone'        => $client['telephone'],
    'email'            => $client['email'],
    'permis_numero'    => $client['permis_numero'],
    'permis_expiration'=> $client['permis_expiration'],
    'statut'           => $client['statut'],
    'permis_status'    => $permisStatus,
    'permis_msg'       => $permisMsg,
]);
