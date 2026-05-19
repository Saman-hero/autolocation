<?php
require_once "../config/database.php";
header('Content-Type: application/json');

$db   = new Database();
$conn = $db->getConnection();

$cin       = trim($_GET['cin']        ?? '');
$email     = trim($_GET['email']      ?? '');
$excludeId = (int)($_GET['exclude_id'] ?? 0);

if (!$cin && !$email) {
    echo json_encode(['exists' => false, 'client' => null]);
    exit;
}

$sql    = "SELECT id, nom, prenom, cin, email, telephone FROM clients WHERE 1=0";
$params = [];

if ($cin) {
    $sql    = "SELECT id, nom, prenom, cin, email, telephone FROM clients WHERE cin = :cin";
    $params[':cin'] = $cin;
} elseif ($email) {
    $sql    = "SELECT id, nom, prenom, cin, email, telephone FROM clients WHERE email = :email";
    $params[':email'] = $email;
}

if ($excludeId) {
    $sql .= " AND id != :exclude_id";
    $params[':exclude_id'] = $excludeId;
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$client = $stmt->fetch();

if ($client) {
    echo json_encode([
        'exists' => true,
        'client' => [
            'id'        => $client['id'],
            'nom'       => $client['nom'],
            'prenom'    => $client['prenom'],
            'cin'       => $client['cin'],
            'email'     => $client['email'],
            'telephone' => $client['telephone'],
        ]
    ]);
} else {
    echo json_encode(['exists' => false, 'client' => null]);
}
