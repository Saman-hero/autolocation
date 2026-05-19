<?php
require_once "../config/database.php";

$db   = new Database();
$conn = $db->getConnection();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo 'ID manquant'; exit; }

$r = $conn->prepare("
    SELECT r.*,
           c.nom AS client_nom, c.prenom AS client_prenom, c.cin AS client_cin,
           c.telephone AS client_tel, c.email AS client_email, c.adresse AS client_adresse,
           v.numero AS vehicle_numero, v.marque, v.modele, v.immatriculation
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
$resteAPayer = max(0, ($r['montant_total'] ?? 0) - $totalPaye);

$invoiceNum = 'FAC-' . str_pad($id, 5, '0', STR_PAD_LEFT);
$dateGeneration = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Facture <?= $invoiceNum ?> — <?= htmlspecialchars($r['reference']) ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #1a1a1a; background: #f0f4f8; }

    @media print {
      @page { size: A4; margin: 12mm 18mm; }
      body { background: #fff !important; font-size: 9.5pt; }
      .no-print { display: none !important; }
      .invoice-wrap { box-shadow: none !important; margin: 0 !important; padding: 0 !important; }
    }

    .invoice-wrap { max-width: 800px; margin: 20px auto; background: #fff; padding: 30px; box-shadow: 0 4px 30px rgba(0,0,0,.12); }

    /* Header */
    .inv-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
    .company-block .name { font-size: 22pt; font-weight: 900; color: #1a3a5c; }
    .company-block .sub  { font-size: 8pt; color: #f97316; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; }
    .company-block .info { font-size: 8pt; color: #666; margin-top: 6px; line-height: 1.5; }

    .inv-title-block { text-align: right; }
    .inv-title { font-size: 20pt; font-weight: 900; color: #1a3a5c; text-transform: uppercase; letter-spacing: .05em; }
    .inv-num   { font-size: 12pt; color: #f97316; font-weight: 700; margin-top: 3px; }
    .inv-date  { font-size: 8.5pt; color: #888; margin-top: 2px; }
    .inv-ref   { font-size: 8.5pt; color: #888; }

    .divider { height: 4px; background: linear-gradient(90deg, #1a3a5c 0%, #f97316 40%, #1a3a5c 100%); border-radius: 2px; margin-bottom: 22px; }

    /* Parties */
    .parties { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 22px; }
    .party { border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; }
    .party.highlight { border-color: #1a3a5c; background: #f0f4f8; }
    .party-label { font-size: 7.5pt; font-weight: 700; text-transform: uppercase; color: #888; letter-spacing: .08em; margin-bottom: 8px; }
    .party-name  { font-size: 12pt; font-weight: 800; color: #1a3a5c; }
    .party-info  { font-size: 8.5pt; color: #555; margin-top: 5px; line-height: 1.6; }

    /* Items table */
    .items-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    .items-table thead th { background: #1a3a5c; color: #fff; padding: 8px 12px; text-align: left; font-size: 8.5pt; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
    .items-table thead th:last-child { text-align: right; }
    .items-table tbody td { padding: 9px 12px; border-bottom: 1px solid #f0f4f8; font-size: 9.5pt; }
    .items-table tbody td:last-child { text-align: right; font-weight: 600; }
    .items-table tbody tr:last-child td { border-bottom: none; }
    .items-table tfoot td { padding: 9px 12px; font-weight: 700; }
    .items-table tfoot .subtotal td { border-top: 1px solid #e2e8f0; color: #555; }
    .items-table tfoot .total-row td { border-top: 2px solid #1a3a5c; font-size: 12pt; color: #1a3a5c; background: #f0f4f8; }
    .items-table tfoot .total-row td:last-child { text-align: right; }
    .items-table tfoot .paid-row td { color: #065f46; font-size: 9pt; }
    .items-table tfoot .paid-row td:last-child { text-align: right; }
    .items-table tfoot .due-row td { color: <?= $resteAPayer > 0 ? '#991b1b' : '#065f46' ?>; font-size: 10pt; font-weight: 800; }
    .items-table tfoot .due-row td:last-child { text-align: right; }

    /* Notes */
    .notes-area { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; font-size: 8.5pt; color: #555; margin-bottom: 18px; line-height: 1.6; }

    /* Paiements */
    .paiements-title { font-size: 9pt; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #1a3a5c; margin-bottom: 8px; }
    .pmt-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    .pmt-table td { padding: 6px 10px; border-bottom: 1px solid #f0f4f8; font-size: 8.5pt; }
    .pmt-table td:last-child { text-align: right; font-weight: 600; }
    .pmt-table thead th { background: #f0f4f8; padding: 6px 10px; font-size: 7.5pt; text-transform: uppercase; color: #888; font-weight: 700; text-align: left; }
    .pmt-table thead th:last-child { text-align: right; }

    /* Status bar */
    .status-bar { padding: 10px 14px; border-radius: 6px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; }
    .status-bar.paid     { background: #d1fae5; color: #065f46; }
    .status-bar.partial  { background: #fef3c7; color: #92400e; }
    .status-bar.unpaid   { background: #fee2e2; color: #991b1b; }
    .status-bar .status-text { font-weight: 700; font-size: 10pt; }
    .status-bar .status-amount { font-size: 14pt; font-weight: 900; }

    /* Footer */
    .inv-footer { border-top: 1px solid #e2e8f0; padding-top: 10px; display: flex; justify-content: space-between; font-size: 7.5pt; color: #aaa; }

    /* Print button */
    .print-btn { position: fixed; bottom: 24px; right: 24px; background: #f97316; color: #fff; border: none; border-radius: 50px; padding: 12px 24px; font-size: 11pt; font-weight: 700; cursor: pointer; box-shadow: 0 4px 20px rgba(0,0,0,.3); z-index: 999; }
    .print-btn:hover { background: #1a3a5c; }
  </style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()">🖨 Imprimer / PDF</button>

<div class="invoice-wrap">

  <!-- Header -->
  <div class="inv-header">
    <div class="company-block">
      <div class="name">AutoLocation</div>
      <div class="sub">Location de véhicules</div>
      <div class="info">
        Maroc · contact@autolocation.ma<br>
        +212 5XX-XXXXXX
      </div>
    </div>
    <div class="inv-title-block">
      <div class="inv-title">Facture</div>
      <div class="inv-num"><?= $invoiceNum ?></div>
      <div class="inv-date">Date : <?= $dateGeneration ?></div>
      <div class="inv-ref">Réf. location : <?= htmlspecialchars($r['reference']) ?></div>
    </div>
  </div>

  <div class="divider"></div>

  <!-- Parties -->
  <div class="parties">
    <div class="party highlight">
      <div class="party-label">Émetteur</div>
      <div class="party-name">AutoLocation</div>
      <div class="party-info">
        Société de location de véhicules<br>
        Maroc
      </div>
    </div>
    <div class="party">
      <div class="party-label">Facturé à</div>
      <div class="party-name"><?= htmlspecialchars($r['client_nom'] . ' ' . $r['client_prenom']) ?></div>
      <div class="party-info">
        <?php if ($r['client_cin']): ?>CIN : <?= htmlspecialchars($r['client_cin']) ?><br><?php endif; ?>
        <?php if ($r['client_tel']): ?>Tél : <?= htmlspecialchars($r['client_tel']) ?><br><?php endif; ?>
        <?php if ($r['client_email']): ?><?= htmlspecialchars($r['client_email']) ?><br><?php endif; ?>
        <?php if ($r['client_adresse']): ?><?= htmlspecialchars($r['client_adresse']) ?><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Statut paiement -->
  <?php
    if ($resteAPayer <= 0)      $statusClass = 'paid';
    elseif ($totalPaye > 0)     $statusClass = 'partial';
    else                        $statusClass = 'unpaid';
    $statusText = $resteAPayer <= 0 ? 'PAYÉE INTÉGRALEMENT' : ($totalPaye > 0 ? 'PARTIELLEMENT PAYÉE' : 'EN ATTENTE DE PAIEMENT');
  ?>
  <div class="status-bar <?= $statusClass ?>">
    <div class="status-text"><?= $statusText ?></div>
    <div class="status-amount"><?= number_format($resteAPayer, 2) ?> MAD restants</div>
  </div>

  <!-- Lignes de facturation -->
  <table class="items-table">
    <thead>
      <tr>
        <th>Description</th>
        <th>Détail</th>
        <th>Qté</th>
        <th>P.U.</th>
        <th>Montant</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          <strong>Location véhicule</strong><br>
          <span style="font-size:8pt;color:#888"><?= htmlspecialchars($r['marque'] . ' ' . $r['modele']) ?> — <?= htmlspecialchars($r['vehicle_numero']) ?></span>
        </td>
        <td style="font-size:8pt;color:#555">
          Du <?= date('d/m/Y', strtotime($r['date_debut'])) ?><br>
          au <?= date('d/m/Y', strtotime($r['date_fin_prevue'])) ?>
        </td>
        <td><?= $r['nb_jours'] ?> j</td>
        <td><?= number_format($r['prix_jour'], 2) ?> MAD</td>
        <td><?= number_format(($r['prix_jour'] ?? 0) * ($r['nb_jours'] ?? 0), 2) ?> MAD</td>
      </tr>
      <?php if ($r['frais_extra'] > 0): ?>
      <tr>
        <td><strong>Frais supplémentaires</strong></td>
        <td style="font-size:8pt;color:#888">Retard / dommages / autres</td>
        <td>1</td>
        <td><?= number_format($r['frais_extra'], 2) ?> MAD</td>
        <td><?= number_format($r['frais_extra'], 2) ?> MAD</td>
      </tr>
      <?php endif; ?>
    </tbody>
    <tfoot>
      <?php if ($r['caution'] > 0): ?>
      <tr class="subtotal">
        <td colspan="4" style="color:#888;font-size:8.5pt">Caution (remboursable à la restitution)</td>
        <td style="text-align:right;color:#888;font-size:8.5pt"><?= number_format($r['caution'], 2) ?> MAD</td>
      </tr>
      <?php endif; ?>
      <tr class="total-row">
        <td colspan="4">TOTAL TTC</td>
        <td><?= number_format($r['montant_total'] ?? 0, 2) ?> MAD</td>
      </tr>
      <?php foreach ($paiements as $p): ?>
      <tr class="paid-row">
        <td colspan="4">Paiement reçu — <?= date('d/m/Y', strtotime($p['date_paiement'])) ?> (<?= htmlspecialchars($p['type_paiement']) ?>)</td>
        <td>- <?= number_format($p['montant'], 2) ?> MAD</td>
      </tr>
      <?php endforeach; ?>
      <tr class="due-row">
        <td colspan="4"><?= $resteAPayer <= 0 ? 'SOLDÉE' : 'RESTE À PAYER' ?></td>
        <td><?= number_format($resteAPayer, 2) ?> MAD</td>
      </tr>
    </tfoot>
  </table>

  <!-- Détail paiements -->
  <?php if (!empty($paiements)): ?>
  <div class="paiements-title">Historique des paiements</div>
  <table class="pmt-table">
    <thead><tr><th>Date</th><th>Type</th><th>Mode</th><th>Référence</th><th>Montant</th></tr></thead>
    <tbody>
    <?php foreach ($paiements as $p): ?>
    <tr>
      <td><?= date('d/m/Y', strtotime($p['date_paiement'])) ?></td>
      <td><?= htmlspecialchars($p['type']) ?></td>
      <td><?= htmlspecialchars($p['type_paiement']) ?></td>
      <td><?= htmlspecialchars($p['reference_transaction'] ?: '—') ?></td>
      <td><?= number_format($p['montant'], 2) ?> MAD</td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <!-- Notes -->
  <?php if ($r['commentaire']): ?>
  <div class="notes-area">
    <strong>Notes :</strong> <?= nl2br(htmlspecialchars($r['commentaire'])) ?>
  </div>
  <?php endif; ?>

  <div class="inv-footer">
    <div>AutoLocation — Facture <?= $invoiceNum ?></div>
    <div>Générée le <?= $dateGeneration ?></div>
    <div>Réf. contrat : <?= htmlspecialchars($r['reference']) ?></div>
  </div>

</div>
</body>
</html>
