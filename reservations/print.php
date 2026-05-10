<?php
require_once "../config/database.php";
require_once "../models/ReservationModel.php";

$db    = new Database();
$conn  = $db->getConnection();
$model = new ReservationModel($conn);

$id = (int)($_GET['id'] ?? 0);
if (!$id) exit;
$r = $model->getById($id);
if (!$r) exit;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Contrat de location <?= htmlspecialchars($r['reference']) ?></title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; font-size: 12px; color: #1a1a1a; padding: 20mm; }
    h1 { font-size: 20px; text-align: center; margin-bottom: 4px; }
    .subtitle { text-align: center; color: #666; margin-bottom: 20px; font-size: 11px; }
    .ref { text-align: center; font-size: 14px; font-weight: bold; margin-bottom: 20px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    th { background: #f5f5f5; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; }
    th, td { border: 1px solid #ddd; padding: 6px 10px; }
    .section-title { font-weight: bold; font-size: 11px; margin: 14px 0 6px; text-transform: uppercase;
                     border-bottom: 2px solid #1b5e35; padding-bottom: 4px; color: #1b5e35; }
    .sign-box { display: flex; gap: 40px; margin-top: 40px; }
    .sign-item { flex: 1; border-top: 2px solid #333; padding-top: 8px; text-align: center; font-size: 11px; }
    .highlight { background: #fff8e1; border: 1px solid #f59e0b; padding: 10px; border-radius: 4px; margin-bottom: 16px; }
    @media print { button { display: none; } }
  </style>
</head>
<body>

<div style="text-align:center;margin-bottom:24px">
  <h1>CONTRAT DE LOCATION DE VÉHICULE</h1>
  <div class="subtitle">Société de Location de Voitures</div>
  <div class="ref">N° <?= htmlspecialchars($r['reference']) ?></div>
</div>

<div class="highlight">
  Contrat établi le <?= date('d/m/Y à H:i') ?> — Statut : <strong><?= ucfirst($r['statut']) ?></strong>
</div>

<div class="section-title">Informations du client</div>
<table>
  <tr>
    <th>Nom complet</th>
    <td><?= htmlspecialchars($r['client_nom'] . ' ' . $r['client_prenom']) ?></td>
    <th>CIN / Passeport</th>
    <td><?= htmlspecialchars($r['client_cin'] ?: '—') ?></td>
  </tr>
  <tr>
    <th>Téléphone</th>
    <td><?= htmlspecialchars($r['client_tel'] ?: '—') ?></td>
    <th>Permis de conduire</th>
    <td><?= htmlspecialchars($r['permis_numero'] ?: '—') ?></td>
  </tr>
</table>

<div class="section-title">Véhicule loué</div>
<table>
  <tr>
    <th>Numéro</th>
    <td><?= htmlspecialchars($r['vehicle_numero']) ?></td>
    <th>Marque / Modèle</th>
    <td><?= htmlspecialchars($r['marque'] . ' ' . $r['modele']) ?></td>
  </tr>
  <tr>
    <th>Catégorie</th>
    <td><?= htmlspecialchars($r['categorie']) ?></td>
    <th>Couleur</th>
    <td><?= htmlspecialchars($r['couleur'] ?: '—') ?></td>
  </tr>
  <tr>
    <th>Km au départ</th>
    <td><?= $r['km_depart'] ? number_format($r['km_depart']) . ' km' : '—' ?></td>
    <th>Km au retour</th>
    <td><?= $r['km_retour'] ? number_format($r['km_retour']) . ' km' : '—' ?></td>
  </tr>
</table>

<div class="section-title">Période de location</div>
<table>
  <tr>
    <th>Date de départ</th>
    <td><?= date('d/m/Y H:i', strtotime($r['date_debut'])) ?></td>
    <th>Date de retour prévue</th>
    <td><?= date('d/m/Y H:i', strtotime($r['date_fin_prevue'])) ?></td>
  </tr>
  <tr>
    <th>Lieu de départ</th>
    <td><?= htmlspecialchars($r['lieu_depart'] ?: '—') ?></td>
    <th>Lieu de retour</th>
    <td><?= htmlspecialchars($r['lieu_retour'] ?: '—') ?></td>
  </tr>
  <tr>
    <th>Durée prévue</th>
    <td><?= $r['nb_jours'] ?> jour(s)</td>
    <th>Retour effectif</th>
    <td><?= $r['date_retour_effectif'] ? date('d/m/Y H:i', strtotime($r['date_retour_effectif'])) : '—' ?></td>
  </tr>
</table>

<div class="section-title">Tarification</div>
<table>
  <tr>
    <th>Prix / jour</th>
    <td><?= number_format($r['prix_jour'], 2) ?> MAD</td>
    <th>Caution</th>
    <td><?= number_format($r['caution'], 2) ?> MAD</td>
  </tr>
  <tr>
    <th>Durée (jours)</th>
    <td><?= $r['nb_jours'] ?></td>
    <th>Frais supplémentaires</th>
    <td><?= number_format($r['frais_extra'] ?? 0, 2) ?> MAD</td>
  </tr>
  <tr>
    <th colspan="2" style="text-align:right;font-size:13px">TOTAL</th>
    <td colspan="2" style="font-size:14px;font-weight:bold;color:#1b5e35">
      <?= number_format($r['montant_total'] ?? 0, 2) ?> MAD
    </td>
  </tr>
</table>

<?php if ($r['commentaire']): ?>
<div class="section-title">Observations</div>
<div style="border:1px solid #ddd;padding:8px;min-height:30px"><?= nl2br(htmlspecialchars($r['commentaire'])) ?></div>
<?php endif; ?>

<div class="section-title">Conditions</div>
<p style="font-size:10px;color:#555;line-height:1.6">
  Le locataire reconnaît avoir pris possession du véhicule en bon état de marche et s'engage à le restituer dans le même état.
  Tout dommage constaté au retour sera facturé au locataire. La caution sera remboursée à la restitution du véhicule en bon état.
  Tout dépassement de la date de retour prévue sera facturé au tarif journalier en vigueur.
</p>

<div class="sign-box">
  <div class="sign-item">Signature du locataire<br><br><br></div>
  <div class="sign-item">Cachet &amp; Signature de la société<br><br><br></div>
</div>

<div style="margin-top:20px;text-align:center">
  <button onclick="window.print()" style="padding:8px 24px;background:#1b5e35;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px">
    🖨 Imprimer
  </button>
</div>

</body>
</html>
