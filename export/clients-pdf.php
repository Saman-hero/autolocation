<?php
require_once "../config/database.php";

$db   = new Database();
$conn = $db->getConnection();

$stmt = $conn->query("SELECT * FROM clients ORDER BY nom, prenom");
$clients = $stmt->fetchAll();
$date = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Liste des clients — AutoLocation</title>
  <style>
    * { box-sizing: border-box; margin:0; padding:0; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 9pt; color: #1a1a1a; background: #fff; }
    @media print {
      @page { size: A4 landscape; margin: 12mm 15mm; }
      .no-print { display: none !important; }
    }
    .container { max-width: 1100px; margin: 0 auto; padding: 20px; }
    .header { display: flex; justify-content: space-between; border-bottom: 3px solid #1a3a5c; padding-bottom: 12px; margin-bottom: 16px; }
    .header h1 { font-size: 18pt; font-weight: 900; color: #1a3a5c; }
    .header .sub { font-size: 8pt; color: #f97316; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; margin-top: 2px; }
    .meta { text-align: right; font-size: 8pt; color: #888; }
    table { width: 100%; border-collapse: collapse; }
    thead th { background: #1a3a5c; color: #fff; padding: 6px 8px; font-size: 8pt; text-align: left; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    tbody td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; font-size: 8.5pt; }
    tbody tr:nth-child(even) td { background: #f8fafc; }
    .badge { display: inline-block; padding: 2px 7px; border-radius: 3px; font-size: 7.5pt; font-weight: 700; }
    .badge-actif { background: #d1fae5; color: #065f46; }
    .badge-suspendu { background: #fef3c7; color: #92400e; }
    .badge-liste_noire { background: #fee2e2; color: #991b1b; }
    .badge-exp { background: #fee2e2; color: #991b1b; }
    .footer { margin-top: 12px; font-size: 7.5pt; color: #aaa; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 8px; }
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
      <div class="sub">Liste des clients</div>
    </div>
    <div class="meta">
      Généré le <?= $date ?><br>
      <?= count($clients) ?> client(s)
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Nom / Prénom</th>
        <th>CIN</th>
        <th>Téléphone</th>
        <th>Email</th>
        <th>Type</th>
        <th>Permis N°</th>
        <th>Expiration permis</th>
        <th>Statut</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($clients as $c):
      $sBadge = ['actif' => 'badge-actif', 'suspendu' => 'badge-suspendu', 'liste_noire' => 'badge-liste_noire'];
      $sLabel = ['actif' => 'Actif', 'suspendu' => 'Suspendu', 'liste_noire' => 'Liste noire'];
      $permisExp = '';
      if ($c['permis_expiration']) {
        $exp = new DateTime($c['permis_expiration']);
        $now = new DateTime();
        if ($exp < $now) $permisExp = ' <span class="badge badge-exp">Expiré</span>';
      }
    ?>
      <tr>
        <td><?= $c['id'] ?></td>
        <td><strong><?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?></strong></td>
        <td><?= htmlspecialchars($c['cin'] ?: '—') ?></td>
        <td><?= htmlspecialchars($c['telephone'] ?: '—') ?></td>
        <td><?= htmlspecialchars($c['email'] ?: '—') ?></td>
        <td><?= $c['type_client'] === 'entreprise' ? 'Entreprise' : 'Particulier' ?></td>
        <td><?= htmlspecialchars($c['permis_numero'] ?: '—') ?></td>
        <td><?= $c['permis_expiration'] ? date('d/m/Y', strtotime($c['permis_expiration'])) : '—' ?><?= $permisExp ?></td>
        <td><span class="badge <?= $sBadge[$c['statut']] ?? 'badge-actif' ?>"><?= $sLabel[$c['statut']] ?? $c['statut'] ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <div class="footer">AutoLocation — Liste clients générée le <?= $date ?></div>
</div>
</body>
</html>
