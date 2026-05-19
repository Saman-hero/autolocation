<?php
require_once "../config/database.php";
require_once "../models/ReservationModel.php";

$db    = new Database();
$conn  = $db->getConnection();
$model = new ReservationModel($conn);

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: index.php"); exit; }
$r = $model->getById($id);
if (!$r) { header("Location: index.php"); exit; }

// Paiements
$paiements = $conn->prepare("SELECT * FROM paiements WHERE reservation_id=? ORDER BY date_paiement");
$paiements->execute([$id]);
$paiements = $paiements->fetchAll();
$totalPaye  = array_sum(array_column($paiements, 'montant'));

// Sinistres liés
$sinistres = $conn->prepare("SELECT * FROM sinistres WHERE reservation_id=?");
$sinistres->execute([$id]);
$sinistres = $sinistres->fetchAll();

$rBadge = [
    'en attente' => 'bg-secondary',
    'confirmée'  => 'bg-primary',
    'en cours'   => 'badge-encours',
    'terminée'   => 'badge-terminee',
    'annulée'    => 'badge-annulee',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Réservation <?= htmlspecialchars($r['reference']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include "../includes/navbar.php"; ?>
<?php include "../includes/flash.php"; ?>

<div class="container-fluid px-4 py-4">

  <div class="page-header">
    <div>
      <div class="text-muted small mb-1">
        <a href="index.php" class="text-decoration-none text-muted">Réservations</a> / Détail
      </div>
      <h1 class="page-title"><?= htmlspecialchars($r['reference']) ?></h1>
    </div>
    <div class="d-flex gap-2">
      <a href="index.php" class="btn btn-outline-secondary">← Retour</a>
      <?php if (in_array($r['statut'], ['en attente','confirmée'])): ?>
        <a href="edit.php?id=<?= $id ?>" class="btn btn-primary">Modifier</a>
        <a href="start.php?id=<?= $id ?>" class="btn btn-success" onclick="return confirm('Démarrer la location ?')">Démarrer</a>
      <?php elseif ($r['statut'] === 'en cours'): ?>
        <a href="finish.php?id=<?= $id ?>" class="btn btn-warning">Clôturer</a>
      <?php endif; ?>
      <a href="pdf.php?id=<?= $id ?>" class="btn btn-outline-secondary" target="_blank">🖨 Contrat</a>
      <a href="invoice.php?id=<?= $id ?>" class="btn btn-outline-secondary" target="_blank">📄 Facture</a>
    </div>
  </div>

  <div class="row g-3">

    <!-- LEFT -->
    <div class="col-lg-7">

      <!-- Infos principales -->
      <div class="card mb-3">
        <div class="card-header">Détails de la location</div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-sm-6">
              <div class="info-label">Référence</div>
              <div class="fw-semibold"><?= htmlspecialchars($r['reference']) ?></div>
            </div>
            <div class="col-sm-6">
              <div class="info-label">Statut</div>
              <span class="badge <?= $rBadge[$r['statut']] ?? 'bg-secondary' ?> fs-6"><?= ucfirst($r['statut']) ?></span>
            </div>
            <div class="col-sm-6">
              <div class="info-label">Date de début</div>
              <div><?= date('d/m/Y H:i', strtotime($r['date_debut'])) ?></div>
            </div>
            <div class="col-sm-6">
              <div class="info-label">Date de fin prévue</div>
              <div><?= date('d/m/Y H:i', strtotime($r['date_fin_prevue'])) ?></div>
            </div>
            <?php if ($r['date_retour_effectif']): ?>
            <div class="col-sm-6">
              <div class="info-label">Retour effectif</div>
              <div><?= date('d/m/Y H:i', strtotime($r['date_retour_effectif'])) ?></div>
            </div>
            <?php endif; ?>
            <div class="col-sm-6">
              <div class="info-label">Lieu de départ</div>
              <div><?= htmlspecialchars($r['lieu_depart'] ?: '—') ?></div>
            </div>
            <div class="col-sm-6">
              <div class="info-label">Lieu de retour</div>
              <div><?= htmlspecialchars($r['lieu_retour'] ?: '—') ?></div>
            </div>
            <div class="col-sm-6">
              <div class="info-label">Km départ</div>
              <div><?= $r['km_depart'] ? number_format($r['km_depart']) . ' km' : '—' ?></div>
            </div>
            <div class="col-sm-6">
              <div class="info-label">Km retour</div>
              <div><?= $r['km_retour'] ? number_format($r['km_retour']) . ' km' : '—' ?></div>
            </div>
            <?php if ($r['commentaire']): ?>
            <div class="col-12">
              <div class="info-label">Commentaire</div>
              <div class="text-muted"><?= nl2br(htmlspecialchars($r['commentaire'])) ?></div>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Paiements -->
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Paiements</span>
          <a href="/location/paiements/add.php?reservation_id=<?= $id ?>" class="btn btn-sm btn-success">+ Enregistrer</a>
        </div>
        <div class="card-body p-0">
          <?php if (empty($paiements)): ?>
            <div class="empty-state py-3"><span class="empty-icon">💳</span><p>Aucun paiement</p></div>
          <?php else: ?>
          <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Mode</th><th>Montant</th><th>Réf.</th></tr></thead>
            <tbody>
            <?php foreach ($paiements as $p): ?>
              <tr>
                <td class="small"><?= date('d/m/Y', strtotime($p['date_paiement'])) ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($p['type']) ?></span></td>
                <td class="text-muted small"><?= htmlspecialchars($p['type_paiement']) ?></td>
                <td class="fw-semibold"><?= number_format($p['montant'], 2) ?> MAD</td>
                <td class="text-muted small"><?= htmlspecialchars($p['reference_transaction'] ?: '—') ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <div class="p-3 text-end fw-bold border-top">
            Total encaissé : <?= number_format($totalPaye, 2) ?> MAD
            <?php if ($r['montant_total']): ?>
              / <?= number_format($r['montant_total'], 2) ?> MAD
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Sinistres -->
      <?php if (!empty($sinistres)): ?>
      <div class="card">
        <div class="card-header">Sinistres / Incidents</div>
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Date</th><th>Type</th><th>Statut</th><th>Coût</th></tr></thead>
            <tbody>
            <?php foreach ($sinistres as $s): ?>
              <tr>
                <td class="small"><?= date('d/m/Y', strtotime($s['date_sinistre'])) ?></td>
                <td><span class="badge bg-danger"><?= htmlspecialchars($s['type']) ?></span></td>
                <td class="text-muted small"><?= htmlspecialchars($s['statut']) ?></td>
                <td><?= $s['cout_reparation'] ? number_format($s['cout_reparation'], 2) . ' MAD' : '—' ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    </div>

    <!-- RIGHT -->
    <div class="col-lg-5">

      <!-- Client -->
      <div class="card mb-3">
        <div class="card-header">Client</div>
        <div class="card-body">
          <div class="fw-bold fs-5 mb-1"><?= htmlspecialchars($r['client_nom'] . ' ' . $r['client_prenom']) ?></div>
          <div class="text-muted small mb-2"><?= htmlspecialchars($r['client_cin'] ?: '') ?></div>
          <div><?= htmlspecialchars($r['client_tel'] ?: '—') ?></div>
          <div class="text-muted small"><?= htmlspecialchars($r['client_email'] ?: '') ?></div>
          <div class="mt-2 text-muted small">Permis : <?= htmlspecialchars($r['permis_numero'] ?: '—') ?></div>
          <a href="/location/clients/view.php?id=<?= $r['client_id'] ?>" class="btn btn-sm btn-outline-primary mt-2">Voir profil client</a>
        </div>
      </div>

      <!-- Véhicule -->
      <div class="card mb-3">
        <div class="card-header">Véhicule</div>
        <div class="card-body">
          <div class="fw-bold text-primary fs-5"><?= htmlspecialchars($r['vehicle_numero']) ?></div>
          <div class="fw-semibold"><?= htmlspecialchars($r['marque'] . ' ' . $r['modele']) ?></div>
          <div class="text-muted small"><?= htmlspecialchars($r['categorie']) ?> · <?= htmlspecialchars($r['couleur'] ?: '—') ?></div>
        </div>
      </div>

      <!-- Tarif récap -->
      <div class="card">
        <div class="card-header">Récapitulatif financier</div>
        <div class="card-body">
          <?php $rows = [
            'Prix / jour'    => number_format($r['prix_jour'] ?? 0, 2) . ' MAD',
            'Durée prévue'   => ($r['nb_jours'] ?: '—') . ' jour(s)',
            'Caution'        => number_format($r['caution'] ?? 0, 2) . ' MAD',
            'Frais extra'    => number_format($r['frais_extra'] ?? 0, 2) . ' MAD',
            'Total facturé'  => number_format($r['montant_total'] ?? 0, 2) . ' MAD',
            'Total encaissé' => number_format($totalPaye, 2) . ' MAD',
          ]; ?>
          <?php foreach ($rows as $lbl => $val): ?>
          <div class="d-flex justify-content-between py-2 border-bottom" style="font-size:.875rem">
            <span class="text-muted"><?= $lbl ?></span>
            <span class="fw-semibold"><?= $val ?></span>
          </div>
          <?php endforeach; ?>
          <?php if ($r['montant_total'] > $totalPaye): ?>
          <div class="d-flex justify-content-between py-2 text-danger fw-bold mt-1">
            <span>Reste à payer</span>
            <span><?= number_format($r['montant_total'] - $totalPaye, 2) ?> MAD</span>
          </div>
          <?php endif; ?>
        </div>
        <?php if ($r['statut'] !== 'terminée' && $r['statut'] !== 'annulée'): ?>
        <div class="card-footer d-flex flex-column gap-2">
          <a href="/location/sinistres/add.php?reservation_id=<?= $id ?>&vehicle_id=<?= $r['vehicle_id'] ?>&client_id=<?= $r['client_id'] ?>"
             class="btn btn-sm btn-outline-danger w-100">+ Déclarer un sinistre</a>
          <a href="/location/etat-vehicule/add.php?reservation_id=<?= $id ?>&type=depart"
             class="btn btn-sm btn-outline-primary w-100">🚗 État au départ</a>
          <a href="/location/etat-vehicule/add.php?reservation_id=<?= $id ?>&type=retour"
             class="btn btn-sm btn-outline-success w-100">🏁 État au retour</a>
        </div>
        <?php else: ?>
        <div class="card-footer">
          <a href="/location/etat-vehicule/add.php?reservation_id=<?= $id ?>&type=retour"
             class="btn btn-sm btn-outline-success w-100">🏁 Fiche état retour</a>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
