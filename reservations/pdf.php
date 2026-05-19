<?php
require_once "../config/database.php";

$db   = new Database();
$conn = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo 'ID manquant'; exit; }

$r = $conn->prepare("
    SELECT r.*,
           c.nom AS client_nom, c.prenom AS client_prenom, c.cin AS client_cin,
           c.telephone AS client_tel, c.email AS client_email,
           c.adresse AS client_adresse, c.permis_numero, c.permis_expiration,
           v.numero AS vehicle_numero, v.marque, v.modele, v.couleur,
           v.immatriculation, v.categorie, v.carburant AS vehicle_carburant
    FROM reservations r
    JOIN clients c ON r.client_id = c.id
    JOIN vehicles v ON r.vehicle_id = v.id
    WHERE r.id = ?
");
$r->execute([$id]);
$r = $r->fetch();
if (!$r) { echo 'Réservation introuvable'; exit; }

$paiements = $conn->prepare("SELECT * FROM paiements WHERE reservation_id = ? ORDER BY date_paiement");
$paiements->execute([$id]);
$paiements = $paiements->fetchAll();
$totalPaye  = array_sum(array_column($paiements, 'montant'));

$dateGeneration = date('d/m/Y à H:i');

// URL de la réservation encodée pour le QR code
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl   = $protocol . '://' . $_SERVER['HTTP_HOST'];
$viewUrl   = $baseUrl . '/location/reservations/view.php?id=' . $id;
$qrUrl     = 'https://api.qrserver.com/v1/create-qr-code/?size=110x110&margin=4&data=' . urlencode($viewUrl);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Contrat de location — <?= htmlspecialchars($r['reference']) ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #1a1a1a; background: #fff; }

    @media print {
      @page { size: A4; margin: 15mm 20mm; }
      body { font-size: 9.5pt; }
      .no-print { display: none !important; }
      .page-break { page-break-before: always; }
    }

    .container { max-width: 800px; margin: 0 auto; padding: 20px; }

    /* Header */
    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #1a3a5c; padding-bottom: 16px; margin-bottom: 20px; }
    .company-name { font-size: 24pt; font-weight: 900; color: #1a3a5c; letter-spacing: -0.02em; }
    .company-sub  { font-size: 8pt; color: #f97316; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; margin-top: 2px; }
    .company-info { font-size: 8pt; color: #666; margin-top: 6px; line-height: 1.5; }

    .contract-meta { text-align: right; }
    .contract-title { font-size: 14pt; font-weight: 800; color: #1a3a5c; text-transform: uppercase; letter-spacing: .04em; }
    .contract-ref   { font-size: 11pt; color: #f97316; font-weight: 700; margin-top: 4px; }
    .contract-date  { font-size: 8pt; color: #888; margin-top: 2px; }

    /* Accent bar */
    .accent-bar { height: 4px; background: linear-gradient(90deg, #1a3a5c, #f97316, #1a3a5c); margin-bottom: 20px; border-radius: 2px; }

    /* Section */
    .section { margin-bottom: 16px; }
    .section-title { font-size: 9pt; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #fff; background: #1a3a5c; padding: 5px 10px; border-radius: 4px; margin-bottom: 10px; display: inline-block; }

    /* Grid */
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }

    /* Info box */
    .info-box { border: 1px solid #d1dbe6; border-radius: 6px; padding: 10px 12px; background: #f8fafc; }
    .info-box .label { font-size: 7.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #888; margin-bottom: 2px; }
    .info-box .value { font-size: 10pt; font-weight: 600; color: #1a1a1a; }
    .info-box .sub   { font-size: 8pt; color: #888; }

    /* Party block */
    .party { border: 1px solid #d1dbe6; border-radius: 6px; padding: 12px; }
    .party-header { font-size: 8pt; font-weight: 700; text-transform: uppercase; color: #888; letter-spacing: .06em; margin-bottom: 8px; }
    .party-name  { font-size: 13pt; font-weight: 800; color: #1a3a5c; }
    .party-detail { font-size: 9pt; color: #444; margin-top: 4px; line-height: 1.6; }

    /* Financial table */
    .fin-table { width: 100%; border-collapse: collapse; }
    .fin-table td { padding: 7px 10px; border-bottom: 1px solid #eef0f3; font-size: 9.5pt; }
    .fin-table td:last-child { text-align: right; font-weight: 600; }
    .fin-table .total-row td { border-top: 2px solid #1a3a5c; font-weight: 800; font-size: 11pt; color: #1a3a5c; border-bottom: none; }
    .fin-table .sub-row td { color: #888; font-size: 8.5pt; }
    .fin-table .paid-row td { color: #065f46; background: #d1fae5; }

    /* Conditions */
    .conditions { border: 1px solid #fed7aa; border-radius: 6px; padding: 12px; background: #fff7ed; font-size: 8pt; line-height: 1.6; color: #92400e; }
    .conditions-title { font-weight: 700; margin-bottom: 6px; }

    /* Signatures */
    .sig-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 6px; }
    .sig-box  { border: 1px solid #d1dbe6; border-radius: 6px; padding: 14px; text-align: center; }
    .sig-label { font-size: 8pt; font-weight: 700; text-transform: uppercase; color: #888; letter-spacing: .06em; margin-bottom: 50px; }
    .sig-line  { border-top: 1px solid #999; padding-top: 6px; font-size: 8pt; color: #666; }

    /* Status badge */
    .status-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 8pt; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
    .status-encours  { background: #fef3c7; color: #92400e; }
    .status-terminee { background: #d1fae5; color: #065f46; }
    .status-default  { background: #e2e8f0; color: #475569; }

    /* Print button */
    .print-btn { position: fixed; bottom: 24px; right: 24px; background: #1a3a5c; color: #fff; border: none; border-radius: 50px; padding: 12px 24px; font-size: 11pt; font-weight: 700; cursor: pointer; box-shadow: 0 4px 20px rgba(0,0,0,.3); z-index: 999; }
    .print-btn:hover { background: #f97316; }

    .footer { border-top: 1px solid #d1dbe6; margin-top: 20px; padding-top: 8px; text-align: center; font-size: 7.5pt; color: #aaa; }

    /* QR code */
    .qr-block { text-align: center; }
    .qr-block img { display: block; margin: 0 auto 4px; border: 1px solid #d1dbe6; border-radius: 4px; padding: 3px; background: #fff; }
    .qr-block .qr-label { font-size: 7pt; color: #aaa; }
  </style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()">🖨 Imprimer / PDF</button>

<div class="container">

  <!-- En-tête -->
  <div class="header">
    <div>
      <div class="company-name">AutoLocation</div>
      <div class="company-sub">Location de véhicules</div>
      <div class="company-info">
        Maroc · Tél: +212 5XX-XXXXXX<br>
        contact@autolocation.ma
      </div>
    </div>
    <div style="display:flex;align-items:flex-start;gap:16px">
      <div class="contract-meta">
        <div class="contract-title">Contrat de Location</div>
        <div class="contract-ref"><?= htmlspecialchars($r['reference']) ?></div>
        <div class="contract-date">Généré le <?= $dateGeneration ?></div>
        <?php
          $statusClass = match($r['statut']) {
              'en cours'  => 'status-encours',
              'terminée'  => 'status-terminee',
              default     => 'status-default',
          };
        ?>
        <div class="mt-2"><span class="status-badge <?= $statusClass ?>"><?= ucfirst($r['statut']) ?></span></div>
      </div>
      <div class="qr-block">
        <img src="<?= htmlspecialchars($qrUrl) ?>" width="110" height="110" alt="QR Code">
        <div class="qr-label">Scan pour vérifier</div>
      </div>
    </div>
  </div>

  <div class="accent-bar"></div>

  <!-- Parties -->
  <div class="section">
    <div class="section-title">Parties du contrat</div>
    <div class="grid-2">
      <div class="party">
        <div class="party-header">Le loueur</div>
        <div class="party-name">AutoLocation</div>
        <div class="party-detail">
          Société de location de véhicules<br>
          Maroc
        </div>
      </div>
      <div class="party">
        <div class="party-header">Le locataire</div>
        <div class="party-name"><?= htmlspecialchars($r['client_nom'] . ' ' . $r['client_prenom']) ?></div>
        <div class="party-detail">
          <?php if ($r['client_cin']): ?>CIN/Passeport : <?= htmlspecialchars($r['client_cin']) ?><br><?php endif; ?>
          <?php if ($r['permis_numero']): ?>Permis : <?= htmlspecialchars($r['permis_numero']) ?><?php if ($r['permis_expiration']): ?> (exp. <?= date('d/m/Y', strtotime($r['permis_expiration'])) ?>)<?php endif; ?><br><?php endif; ?>
          <?php if ($r['client_tel']): ?>Tél : <?= htmlspecialchars($r['client_tel']) ?><br><?php endif; ?>
          <?php if ($r['client_email']): ?><?= htmlspecialchars($r['client_email']) ?><br><?php endif; ?>
          <?php if ($r['client_adresse']): ?><?= htmlspecialchars($r['client_adresse']) ?><?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Véhicule -->
  <div class="section">
    <div class="section-title">Véhicule loué</div>
    <div class="grid-3">
      <div class="info-box">
        <div class="label">Numéro</div>
        <div class="value"><?= htmlspecialchars($r['vehicle_numero']) ?></div>
      </div>
      <div class="info-box">
        <div class="label">Marque / Modèle</div>
        <div class="value"><?= htmlspecialchars($r['marque'] . ' ' . $r['modele']) ?></div>
      </div>
      <div class="info-box">
        <div class="label">Immatriculation</div>
        <div class="value"><?= htmlspecialchars($r['immatriculation'] ?: '—') ?></div>
      </div>
      <div class="info-box">
        <div class="label">Catégorie</div>
        <div class="value"><?= htmlspecialchars($r['categorie'] ?: '—') ?></div>
      </div>
      <div class="info-box">
        <div class="label">Couleur</div>
        <div class="value"><?= htmlspecialchars($r['couleur'] ?: '—') ?></div>
      </div>
      <div class="info-box">
        <div class="label">Carburant</div>
        <div class="value"><?= htmlspecialchars($r['vehicle_carburant'] ?: '—') ?></div>
      </div>
    </div>
  </div>

  <!-- Dates & Lieux -->
  <div class="section">
    <div class="section-title">Dates &amp; Lieux</div>
    <div class="grid-3">
      <div class="info-box">
        <div class="label">Date de départ</div>
        <div class="value"><?= date('d/m/Y', strtotime($r['date_debut'])) ?></div>
        <div class="sub"><?= date('H:i', strtotime($r['date_debut'])) ?></div>
      </div>
      <div class="info-box">
        <div class="label">Date de retour prévue</div>
        <div class="value"><?= date('d/m/Y', strtotime($r['date_fin_prevue'])) ?></div>
        <div class="sub"><?= date('H:i', strtotime($r['date_fin_prevue'])) ?></div>
      </div>
      <?php if ($r['date_retour_effectif']): ?>
      <div class="info-box">
        <div class="label">Retour effectif</div>
        <div class="value"><?= date('d/m/Y', strtotime($r['date_retour_effectif'])) ?></div>
        <div class="sub"><?= date('H:i', strtotime($r['date_retour_effectif'])) ?></div>
      </div>
      <?php else: ?>
      <div class="info-box">
        <div class="label">Durée prévue</div>
        <div class="value"><?= $r['nb_jours'] ?: '—' ?> jour(s)</div>
      </div>
      <?php endif; ?>
      <div class="info-box">
        <div class="label">Lieu de départ</div>
        <div class="value"><?= htmlspecialchars($r['lieu_depart'] ?: '—') ?></div>
      </div>
      <div class="info-box">
        <div class="label">Lieu de retour</div>
        <div class="value"><?= htmlspecialchars($r['lieu_retour'] ?: '—') ?></div>
      </div>
      <div class="info-box">
        <div class="label">Km au départ</div>
        <div class="value"><?= $r['km_depart'] ? number_format($r['km_depart']) . ' km' : '—' ?></div>
      </div>
    </div>
  </div>

  <!-- Financier -->
  <div class="section">
    <div class="section-title">Récapitulatif financier</div>
    <table class="fin-table">
      <tr>
        <td>Prix par jour</td>
        <td><?= number_format($r['prix_jour'], 2) ?> MAD</td>
      </tr>
      <tr>
        <td>Durée (<?= $r['nb_jours'] ?: '—' ?> jour(s))</td>
        <td><?= number_format(($r['prix_jour'] ?? 0) * ($r['nb_jours'] ?? 0), 2) ?> MAD</td>
      </tr>
      <?php if ($r['frais_extra'] > 0): ?>
      <tr>
        <td>Frais supplémentaires</td>
        <td><?= number_format($r['frais_extra'], 2) ?> MAD</td>
      </tr>
      <?php endif; ?>
      <?php if ($r['caution'] > 0): ?>
      <tr class="sub-row">
        <td>Caution (remboursable)</td>
        <td><?= number_format($r['caution'], 2) ?> MAD</td>
      </tr>
      <?php endif; ?>
      <tr class="total-row">
        <td>TOTAL DU CONTRAT</td>
        <td><?= number_format($r['montant_total'] ?? 0, 2) ?> MAD</td>
      </tr>
      <?php if (!empty($paiements)): ?>
      <?php foreach ($paiements as $p): ?>
      <tr class="paid-row">
        <td>Paiement — <?= date('d/m/Y', strtotime($p['date_paiement'])) ?> (<?= htmlspecialchars($p['type_paiement']) ?>)</td>
        <td><?= number_format($p['montant'], 2) ?> MAD</td>
      </tr>
      <?php endforeach; ?>
      <tr class="sub-row">
        <td><strong>Reste à payer</strong></td>
        <td><strong><?= number_format(max(0, ($r['montant_total'] ?? 0) - $totalPaye), 2) ?> MAD</strong></td>
      </tr>
      <?php endif; ?>
    </table>
  </div>

  <!-- Conditions -->
  <div class="section">
    <div class="conditions">
      <div class="conditions-title">Conditions générales de location</div>
      1. Le locataire s'engage à utiliser le véhicule conformément au Code de la Route marocain.<br>
      2. Le véhicule doit être restitué dans l'état dans lequel il a été remis, avec le même niveau de carburant.<br>
      3. Tout dommage causé au véhicule sera à la charge du locataire, déduction faite de la couverture d'assurance.<br>
      4. En cas de retard de restitution non signalé, des frais supplémentaires seront appliqués (prix journalier × jours de retard).<br>
      5. La conduite sous l'emprise d'alcool ou de stupéfiants entraîne l'annulation immédiate de la couverture assurance.<br>
      6. Tout litige sera soumis à la juridiction compétente du lieu du siège social du loueur.
    </div>
  </div>

  <!-- Signatures -->
  <div class="section">
    <div class="section-title">Signatures</div>
    <div class="sig-grid">
      <div class="sig-box">
        <div class="sig-label">Le locataire<br><span style="color:#1a3a5c;font-size:9pt"><?= htmlspecialchars($r['client_nom'] . ' ' . $r['client_prenom']) ?></span></div>
        <div class="sig-line">Lu et approuvé — Date : ____________</div>
      </div>
      <div class="sig-box">
        <div class="sig-label">Pour AutoLocation<br><span style="color:#1a3a5c;font-size:9pt">Le responsable</span></div>
        <div class="sig-line">Signature et cachet</div>
      </div>
    </div>
  </div>

  <div class="footer">
    AutoLocation — Contrat réf. <?= htmlspecialchars($r['reference']) ?> — Généré le <?= $dateGeneration ?><br>
    Vérification : <?= htmlspecialchars($viewUrl) ?>
  </div>

</div>

<script>
// Auto-trigger print dialog
window.addEventListener('load', function() {
  // Small delay to ensure styles are loaded
  setTimeout(function() {
    if (window.location.search.indexOf('autoprint=1') !== -1) {
      window.print();
    }
  }, 500);
});
</script>
</body>
</html>
