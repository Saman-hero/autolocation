<?php
require_once "../config/database.php";

$db   = new Database();
$conn = $db->getConnection();

$statut = trim($_GET['statut'] ?? '');
$from   = $_GET['from'] ?? '';
$to     = $_GET['to']   ?? '';

$sql = "
    SELECT r.id, r.reference, r.statut, r.date_debut, r.date_fin_prevue,
           r.date_retour_effectif, r.nb_jours, r.montant_total,
           c.nom AS client_nom, c.prenom AS client_prenom,
           v.numero AS vehicle_numero, v.marque, v.modele
    FROM reservations r
    JOIN clients c ON r.client_id = c.id
    JOIN vehicles v ON r.vehicle_id = v.id
    WHERE 1=1
";
$params = [];
if ($statut) { $sql .= " AND r.statut = :statut"; $params[':statut'] = $statut; }
if ($from)   { $sql .= " AND DATE(r.date_debut) >= :from"; $params[':from'] = $from; }
if ($to)     { $sql .= " AND DATE(r.date_debut) <= :to"; $params[':to'] = $to; }
$sql .= " ORDER BY r.created_at DESC LIMIT 500";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$date = date('d/m/Y');
$totalCA = array_sum(array_column(array_filter($rows, fn($r) => $r['statut'] === 'terminée'), 'montant_total'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Liste des réservations — AutoLocation</title>
  <style>
    * { box-sizing: border-box; margin:0; padding:0; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 8.5pt; color: #1a1a1a; background: #fff; }
    @media print {
      @page { size: A4 landscape; margin: 10mm 12mm; }
      .no-print { display: none !important; }
    }
    .container { max-width: 1200px; margin: 0 auto; padding: 18px; }
    .header { display: flex; justify-content: space-between; border-bottom: 3px solid #1a3a5c; padding-bottom: 12px; margin-bottom: 14px; }
    .header h1 { font-size: 17pt; font-weight: 900; color: #1a3a5c; }
    .header .sub { font-size: 8pt; color: #f97316; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }
    .meta { text-align: right; font-size: 8pt; color: #888; }
    table { width: 100%; border-collapse: collapse; }
    thead th { background: #1a3a5c; color: #fff; padding: 5px 7px; font-size: 7.5pt; text-align: left; font-weight: 700; text-transform: uppercase; }
    tbody td { padding: 5px 7px; border-bottom: 1px solid #e2e8f0; font-size: 8pt; }
    tbody tr:nth-child(even) td { background: #f8fafc; }
    tbody tr.retard td { background: #fee2e2 !important; }
    .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 7pt; font-weight: 700; }
    .b-attente { background: #e2e8f0; color: #475569; }
    .b-confirmee { background: #dbeafe; color: #1d4ed8; }
    .b-encours { background: #fef3c7; color: #92400e; }
    .b-terminee { background: #d1fae5; color: #065f46; }
    .b-annulee { background: #fee2e2; color: #991b1b; }
    .summary { margin-top: 12px; padding: 8px 12px; background: #f0f4f8; border-radius: 6px; font-size: 8.5pt; display: flex; gap: 2rem; }
    .footer { margin-top: 10px; font-size: 7.5pt; color: #aaa; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 8px; }
    .print-btn { position: fixed; bottom: 20px; right: 20px; background: #1a3a5c; color: #fff; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 700; cursor: pointer; }
    .print-btn:hover { background: #f97316; }
  </style>
</head>
<body>
<button class="print-btn no-print" onclick="window.print()">🖨 Imprimer</button>

<div class="container">
  <div class="header">
    <div>
      <h1>AutoLocation</h1>
      <div class="sub">Liste des réservations</div>
    </div>
    <div class="meta">
      Généré le <?= $date ?><br>
      <?= count($rows) ?> réservation(s)
      <?php if ($from || $to): ?>
        <br>Période : <?= $from ?: '…' ?> → <?= $to ?: '…' ?>
      <?php endif; ?>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Réf.</th>
        <th>Client</th>
        <th>Véhicule</th>
        <th>Début</th>
        <th>Fin prévue</th>
        <th>Retour effectif</th>
        <th>Jours</th>
        <th>Total MAD</th>
        <th>Statut</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $bMap = ['en attente'=>'b-attente','confirmée'=>'b-confirmee','en cours'=>'b-encours','terminée'=>'b-terminee','annulée'=>'b-annulee'];
    $now = new DateTime();
    foreach ($rows as $r):
      $retard = $r['statut'] === 'en cours' && $r['date_fin_prevue'] && new DateTime($r['date_fin_prevue']) < $now;
    ?>
      <tr class="<?= $retard ? 'retard' : '' ?>">
        <td class="fw-semibold"><?= htmlspecialchars($r['reference']) ?></td>
        <td><?= htmlspecialchars($r['client_nom'] . ' ' . $r['client_prenom']) ?></td>
        <td><?= htmlspecialchars($r['vehicle_numero'] . ' ' . $r['marque'] . ' ' . $r['modele']) ?></td>
        <td><?= $r['date_debut'] ? date('d/m/Y', strtotime($r['date_debut'])) : '—' ?></td>
        <td><?= $r['date_fin_prevue'] ? date('d/m/Y', strtotime($r['date_fin_prevue'])) : '—' ?></td>
        <td><?= $r['date_retour_effectif'] ? date('d/m/Y', strtotime($r['date_retour_effectif'])) : '—' ?></td>
        <td><?= $r['nb_jours'] ?: '—' ?></td>
        <td><?= $r['montant_total'] ? number_format($r['montant_total'], 2) : '—' ?></td>
        <td><span class="badge <?= $bMap[$r['statut']] ?? 'b-attente' ?>"><?= ucfirst($r['statut']) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <div class="summary">
    <span>Total : <strong><?= count($rows) ?></strong> réservation(s)</span>
    <?php if ($totalCA > 0): ?>
    <span>CA (terminées) : <strong><?= number_format($totalCA, 2) ?> MAD</strong></span>
    <?php endif; ?>
  </div>

  <div class="footer">AutoLocation — Liste réservations générée le <?= $date ?></div>
</div>
</body>
</html>
