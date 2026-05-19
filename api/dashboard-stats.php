<?php
require_once "../config/database.php";
header('Content-Type: application/json');

$db   = new Database();
$conn = $db->getConnection();

try {
    $s = [
        'v_total'   => (int)$conn->query("SELECT COUNT(*) FROM vehicles")->fetchColumn(),
        'v_dispo'   => (int)$conn->query("SELECT COUNT(*) FROM vehicles WHERE statut='disponible'")->fetchColumn(),
        'v_loue'    => (int)$conn->query("SELECT COUNT(*) FROM vehicles WHERE statut='loué'")->fetchColumn(),
        'v_maint'   => (int)$conn->query("SELECT COUNT(*) FROM vehicles WHERE statut='maintenance'")->fetchColumn(),
        'c_total'   => (int)$conn->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
        'c_actif'   => (int)$conn->query("SELECT COUNT(*) FROM clients WHERE statut='actif'")->fetchColumn(),
        'r_total'   => (int)$conn->query("SELECT COUNT(*) FROM reservations")->fetchColumn(),
        'r_encours' => (int)$conn->query("SELECT COUNT(*) FROM reservations WHERE statut='en cours'")->fetchColumn(),
        'r_mois'    => (int)$conn->query("SELECT COUNT(*) FROM reservations WHERE YEAR(created_at)=YEAR(NOW()) AND MONTH(created_at)=MONTH(NOW())")->fetchColumn(),
        'ca_mois'   => (float)$conn->query("SELECT COALESCE(SUM(montant_total),0) FROM reservations WHERE statut='terminée' AND YEAR(date_retour_effectif)=YEAR(NOW()) AND MONTH(date_retour_effectif)=MONTH(NOW())")->fetchColumn(),
        'ca_mois_prec' => (float)$conn->query("SELECT COALESCE(SUM(montant_total),0) FROM reservations WHERE statut='terminée' AND YEAR(date_retour_effectif)=YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH)) AND MONTH(date_retour_effectif)=MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH))")->fetchColumn(),
        'sin_ouverts'=> (int)$conn->query("SELECT COUNT(*) FROM sinistres WHERE statut='ouvert'")->fetchColumn(),
    ];

    // Taux utilisation flotte
    $s['taux_utilisation'] = $s['v_total'] > 0 ? round(($s['v_loue'] / $s['v_total']) * 100, 1) : 0;

    // % change CA
    $s['ca_variation'] = $s['ca_mois_prec'] > 0
        ? round((($s['ca_mois'] - $s['ca_mois_prec']) / $s['ca_mois_prec']) * 100, 1)
        : ($s['ca_mois'] > 0 ? 100 : 0);

    // Top 3 véhicules
    $top3 = $conn->query("
        SELECT v.numero, v.marque, v.modele, COUNT(r.id) AS nb_locations
        FROM vehicles v
        LEFT JOIN reservations r ON r.vehicle_id = v.id
        GROUP BY v.id, v.numero, v.marque, v.modele
        ORDER BY nb_locations DESC
        LIMIT 3
    ")->fetchAll();
    $s['top_vehicles'] = $top3;

    // Revenue 12 mois
    $revenue12 = $conn->query("
        SELECT DATE_FORMAT(date_retour_effectif, '%Y-%m') AS mois,
               COALESCE(SUM(montant_total),0) AS ca
        FROM reservations
        WHERE statut = 'terminée'
          AND date_retour_effectif >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY mois
        ORDER BY mois
    ")->fetchAll();
    $s['revenue_12m'] = $revenue12;

    // Recent activity
    $recentActivity = [];
    try {
        $recentActivity = $conn->query("
            SELECT user_name, action, table_name, description, created_at
            FROM audit_logs
            ORDER BY created_at DESC
            LIMIT 10
        ")->fetchAll();
    } catch (Exception $e) {
        // audit_logs table may not exist yet
    }
    $s['recent_activity'] = $recentActivity;

    echo json_encode(['success' => true, 'data' => $s, 'ts' => time()]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
