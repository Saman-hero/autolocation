<?php
/**
 * Mailer functions using PHP mail()
 * All functions return bool (success/failure)
 */

define('MAIL_FROM',    'noreply@autolocation.ma');
define('MAIL_FROM_NAME', 'AutoLocation');

function _mailHeaders(): string {
    return implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'X-Mailer: PHP/' . phpversion(),
    ]);
}

function _mailTemplate(string $title, string $body): string {
    return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>' . htmlspecialchars($title) . '</title>
<style>
body{font-family:Arial,sans-serif;background:#f0f4f8;margin:0;padding:20px}
.wrap{max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.1)}
.header{background:#1a3a5c;color:#fff;padding:24px 30px}
.header h1{margin:0;font-size:22px}.header p{margin:4px 0 0;color:#f97316;font-size:12px;text-transform:uppercase;letter-spacing:.08em}
.content{padding:30px}
.detail-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;font-size:14px}
.detail-row:last-child{border-bottom:none}
.label{color:#666}.value{font-weight:700;color:#1a3a5c}
.footer{background:#f8fafc;padding:16px 30px;font-size:12px;color:#888;text-align:center;border-top:1px solid #eee}
.btn{display:inline-block;background:#f97316;color:#fff;padding:10px 24px;border-radius:5px;text-decoration:none;font-weight:700;margin-top:16px}
</style></head><body>
<div class="wrap">
  <div class="header"><h1>AutoLocation</h1><p>Location de véhicules</p></div>
  <div class="content">' . $body . '</div>
  <div class="footer">AutoLocation — Ce message est automatique, ne pas répondre.</div>
</div></body></html>';
}

/**
 * Envoie une confirmation de réservation au client
 */
function sendReservationConfirmation(array $client, array $reservation, array $vehicle): bool {
    if (empty($client['email'])) return false;

    $to      = $client['email'];
    $subject = '=?UTF-8?B?' . base64_encode('Confirmation de votre location — ' . $reservation['reference']) . '?=';

    $body = '<h2>Confirmation de location</h2>
<p>Bonjour <strong>' . htmlspecialchars($client['prenom'] . ' ' . $client['nom']) . '</strong>,</p>
<p>Votre location a été confirmée. Voici le récapitulatif :</p>
<br>
<div class="detail-row"><span class="label">Référence</span><span class="value">' . htmlspecialchars($reservation['reference']) . '</span></div>
<div class="detail-row"><span class="label">Véhicule</span><span class="value">' . htmlspecialchars($vehicle['numero'] . ' — ' . $vehicle['marque'] . ' ' . $vehicle['modele']) . '</span></div>
<div class="detail-row"><span class="label">Date de début</span><span class="value">' . date('d/m/Y H:i', strtotime($reservation['date_debut'])) . '</span></div>
<div class="detail-row"><span class="label">Date de retour prévue</span><span class="value">' . date('d/m/Y H:i', strtotime($reservation['date_fin_prevue'])) . '</span></div>
<div class="detail-row"><span class="label">Durée</span><span class="value">' . ($reservation['nb_jours'] ?? '—') . ' jour(s)</span></div>
<div class="detail-row"><span class="label">Montant total</span><span class="value">' . number_format($reservation['montant_total'] ?? 0, 2) . ' MAD</span></div>
<br>
<p>Merci de votre confiance. À bientôt !</p>';

    $message = _mailTemplate('Confirmation location ' . $reservation['reference'], $body);

    return mail($to, $subject, $message, _mailHeaders());
}

/**
 * Envoie un rappel de retour au client (J-1)
 */
function sendReturnReminder(array $client, array $reservation, array $vehicle): bool {
    if (empty($client['email'])) return false;

    $to      = $client['email'];
    $subject = '=?UTF-8?B?' . base64_encode('Rappel : retour du véhicule demain — ' . $reservation['reference']) . '?=';

    $body = '<h2>Rappel de retour</h2>
<p>Bonjour <strong>' . htmlspecialchars($client['prenom'] . ' ' . $client['nom']) . '</strong>,</p>
<p>Ce message est un rappel : votre véhicule est attendu en retour <strong>demain</strong>.</p>
<br>
<div class="detail-row"><span class="label">Véhicule</span><span class="value">' . htmlspecialchars($vehicle['numero'] . ' — ' . $vehicle['marque'] . ' ' . $vehicle['modele']) . '</span></div>
<div class="detail-row"><span class="label">Date de retour</span><span class="value">' . date('d/m/Y', strtotime($reservation['date_fin_prevue'])) . '</span></div>
<div class="detail-row"><span class="label">Référence</span><span class="value">' . htmlspecialchars($reservation['reference']) . '</span></div>
<br>
<p>En cas de question, contactez-nous. Merci !</p>';

    $message = _mailTemplate('Rappel retour — ' . $reservation['reference'], $body);

    return mail($to, $subject, $message, _mailHeaders());
}

/**
 * Envoie une alerte de retard au client
 */
function sendLateAlert(array $client, array $reservation): bool {
    if (empty($client['email'])) return false;

    $now         = new DateTime();
    $finPrevue   = new DateTime($reservation['date_fin_prevue']);
    $joursRetard = max(0, (int)ceil(($now->getTimestamp() - $finPrevue->getTimestamp()) / 86400));

    $to      = $client['email'];
    $subject = '=?UTF-8?B?' . base64_encode('Retard de ' . $joursRetard . ' jour(s) — ' . $reservation['reference']) . '?=';

    $fraisRetard = $joursRetard * ($reservation['prix_jour'] ?? 0);

    $body = '<h2 style="color:#dc2626">Alerte : retard de retour</h2>
<p>Bonjour <strong>' . htmlspecialchars($client['prenom'] . ' ' . $client['nom']) . '</strong>,</p>
<p>Votre véhicule est en retard de <strong style="color:#dc2626">' . $joursRetard . ' jour(s)</strong> par rapport à la date prévue.</p>
<br>
<div class="detail-row"><span class="label">Référence</span><span class="value">' . htmlspecialchars($reservation['reference']) . '</span></div>
<div class="detail-row"><span class="label">Retour prévu</span><span class="value">' . date('d/m/Y', strtotime($reservation['date_fin_prevue'])) . '</span></div>
<div class="detail-row"><span class="label">Jours de retard</span><span class="value" style="color:#dc2626">' . $joursRetard . ' jour(s)</span></div>
<div class="detail-row"><span class="label">Frais de retard estimés</span><span class="value" style="color:#dc2626">' . number_format($fraisRetard, 2) . ' MAD</span></div>
<br>
<p>Merci de contacter notre agence immédiatement pour régulariser la situation.</p>';

    $message = _mailTemplate('Alerte retard — ' . $reservation['reference'], $body);

    return mail($to, $subject, $message, _mailHeaders());
}
