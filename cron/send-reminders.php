<?php
/**
 * Cron script — envoie les rappels de retour J-1
 * Schedule: 0 9 * * * php /path/to/location/cron/send-reminders.php
 */
define('CRON_MODE', true);

// Bootstrap without auth check
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user_id']   = 0;
$_SESSION['username']  = 'cron';
$_SESSION['user_role'] = 'admin';

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/mailer.php';
require_once dirname(__DIR__) . '/includes/audit.php';

$db   = new Database();
$conn = $db->getConnection();

$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Réservations dont le retour est demain et qui sont en cours
$stmt = $conn->prepare("
    SELECT r.*, c.nom, c.prenom, c.email,
           v.numero, v.marque, v.modele
    FROM reservations r
    JOIN clients c ON r.client_id = c.id
    JOIN vehicles v ON r.vehicle_id = v.id
    WHERE r.statut = 'en cours'
      AND DATE(r.date_fin_prevue) = :tomorrow
      AND c.email IS NOT NULL
      AND c.email != ''
");
$stmt->execute([':tomorrow' => $tomorrow]);
$reservations = $stmt->fetchAll();

$sent    = 0;
$errors  = 0;

foreach ($reservations as $r) {
    $client  = ['nom' => $r['nom'], 'prenom' => $r['prenom'], 'email' => $r['email']];
    $vehicle = ['numero' => $r['numero'], 'marque' => $r['marque'], 'modele' => $r['modele']];

    if (sendReturnReminder($client, $r, $vehicle)) {
        $sent++;
        audit_log($conn, 'EMAIL', 'reservations', $r['id'], "Rappel retour envoyé à {$r['email']} pour réservation {$r['reference']}");
        echo "[OK] Rappel envoyé à {$r['email']} — {$r['reference']}\n";
    } else {
        $errors++;
        echo "[ERR] Échec envoi à {$r['email']} — {$r['reference']}\n";
    }
}

// Also send late alerts for overdue reservations
$overdue = $conn->query("
    SELECT r.*, c.nom, c.prenom, c.email
    FROM reservations r
    JOIN clients c ON r.client_id = c.id
    WHERE r.statut = 'en cours'
      AND r.date_fin_prevue < NOW()
      AND c.email IS NOT NULL AND c.email != ''
");
foreach ($overdue->fetchAll() as $r) {
    $client = ['nom' => $r['nom'], 'prenom' => $r['prenom'], 'email' => $r['email']];
    if (sendLateAlert($client, $r)) {
        $sent++;
        echo "[RETARD] Alerte retard envoyée à {$r['email']} — {$r['reference']}\n";
    }
}

echo "\nTerminé : $sent email(s) envoyé(s), $errors erreur(s).\n";
