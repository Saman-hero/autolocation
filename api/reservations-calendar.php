<?php
require_once "../config/database.php";
header('Content-Type: application/json');

$db   = new Database();
$conn = $db->getConnection();

$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end']   ?? date('Y-m-t');

$stmt = $conn->prepare("
    SELECT r.id, r.reference, r.statut, r.date_debut, r.date_fin_prevue, r.date_retour_effectif,
           c.nom AS client_nom, c.prenom AS client_prenom,
           v.numero AS vehicle_numero, v.marque, v.modele
    FROM reservations r
    JOIN clients c ON r.client_id = c.id
    JOIN vehicles v ON r.vehicle_id = v.id
    WHERE r.date_debut <= :end
      AND (r.date_fin_prevue >= :start OR r.date_debut >= :start2)
    ORDER BY r.date_debut
    LIMIT 500
");
$stmt->execute([':start' => $start, ':start2' => $start, ':end' => $end]);
$reservations = $stmt->fetchAll();

$colors = [
    'en attente' => '#94a3b8',
    'confirmée'  => '#1a3a5c',
    'en cours'   => '#16a34a',
    'terminée'   => '#64748b',
    'annulée'    => '#dc2626',
];

$events = [];
foreach ($reservations as $r) {
    $endDate = $r['date_retour_effectif'] ?? $r['date_fin_prevue'];
    // FullCalendar end is exclusive, add 1 day for all-day events
    $endDt = new DateTime($endDate);
    $endDt->modify('+1 day');

    $events[] = [
        'id'    => $r['id'],
        'title' => $r['vehicle_numero'] . ' — ' . $r['client_nom'] . ' ' . $r['client_prenom'],
        'start' => (new DateTime($r['date_debut']))->format('Y-m-d'),
        'end'   => $endDt->format('Y-m-d'),
        'color' => $colors[$r['statut']] ?? '#888',
        'url'   => '/location/reservations/view.php?id=' . $r['id'],
        'extendedProps' => [
            'client'   => $r['client_nom'] . ' ' . $r['client_prenom'],
            'vehicle'  => $r['vehicle_numero'] . ' ' . $r['marque'] . ' ' . $r['modele'],
            'statut'   => $r['statut'],
            'reference'=> $r['reference'],
        ]
    ];
}

echo json_encode($events);
