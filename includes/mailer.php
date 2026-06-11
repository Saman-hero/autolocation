<?php
/**
 * includes/mailer.php
 *
 * Transactional email helpers for AutoLocation.
 * All email is sent via PHP's built-in mail() function.
 *
 * Three outbound email types are supported:
 *   1. sendReservationConfirmation() — sent when a new reservation is created.
 *   2. sendReturnReminder()          — sent the day before the vehicle is due back (cron).
 *   3. sendLateAlert()               — sent when a reservation is closed overdue.
 *
 * All functions:
 *   - Guard against empty email addresses (return false without throwing).
 *   - Use UTF-8 encoded subjects to handle French characters safely.
 *   - Build a consistent HTML layout via private helpers (_mailHeaders, _mailTemplate).
 *   - Return bool: true = mail() accepted the message, false = skipped or failed.
 *
 * Note: mail() acceptance does NOT guarantee delivery. In production, replace
 * mail() with a transactional service (Mailgun, SendGrid …) for reliability.
 */

// Sender identity — used in the From header of every outgoing email.
define('MAIL_FROM',    'noreply@autolocation.ma');
define('MAIL_FROM_NAME', 'AutoLocation');

/**
 * Build the standard set of MIME headers for every outgoing email.
 * Declares HTML content type and sets the From address.
 *
 * @return string  Newline-delimited header string accepted by mail().
 */
function _mailHeaders(): string {
    return implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'X-Mailer: PHP/' . phpversion(),
    ]);
}

/**
 * Wrap an HTML body fragment in the AutoLocation branded email shell.
 * Produces a self-contained HTML document with inline CSS so email
 * clients (which ignore external stylesheets) render correctly.
 *
 * @param string $title  Plain-text <title> for the email document.
 * @param string $body   Inner HTML to place inside the content area.
 * @return string        Full HTML email document ready to send.
 */
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
 * Send a booking confirmation email to the client.
 *
 * Triggered automatically right after a new reservation is saved in
 * ReservationController::add(). The email lists all key details:
 * reference, vehicle, dates, duration, and total amount.
 *
 * @param array $client      Client row from the `clients` table.
 * @param array $reservation Reservation row from the `reservations` table.
 * @param array $vehicle     Vehicle row from the `vehicles` table.
 * @return bool              True if mail() accepted the message.
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
 * Send a return reminder email to the client one day before due date.
 *
 * Called by the cron script cron/send-reminders.php which runs daily and
 * queries reservations whose return date is tomorrow (J-1).
 *
 * @param array $client      Client row from the `clients` table.
 * @param array $reservation Reservation row from the `reservations` table.
 * @param array $vehicle     Vehicle row from the `vehicles` table.
 * @return bool              True if mail() accepted the message.
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
 * Send a late return alert email to the client.
 *
 * Called by ReservationController::finish() when the actual return date
 * is past the originally planned return date. The email shows the number
 * of overdue days and the estimated late fees (days × daily rate).
 *
 * @param array $client      Client row from the `clients` table.
 * @param array $reservation Reservation row from the `reservations` table.
 * @return bool              True if mail() accepted the message.
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
