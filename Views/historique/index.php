<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Historique — Location</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . "/../../includes/navbar.php"; ?>
<?php include __DIR__ . "/../../includes/flash.php"; ?>

<div class="container-fluid px-4 py-4">

  <div class="page-header">
    <h1 class="page-title">Historique des locations</h1>
    <div class="d-flex gap-2 align-items-center">
      <span class="badge bg-secondary fs-6"><?= count($reservations) ?> résultat(s)</span>
    </div>
  </div>

  <!-- Filtres -->
  <div class="card mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2">
        <div class="col-md-3">
          <input type="text" name="q" class="form-control" placeholder="Référence, client, véhicule…" value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-2">
          <select name="client_id" class="form-select">
            <option value="">Tous clients</option>
            <?php foreach ($clients as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $clientId == $c['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <select name="vehicle_id" class="form-select">
            <option value="">Tous véhicules</option>
            <?php foreach ($vehicles as $v): ?>
              <option value="<?= $v['id'] ?>" <?= $vehicleId == $v['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($v['numero']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-1">
          <select name="statut" class="form-select">
            <option value="">Tous</option>
            <?php foreach (['en attente','confirmée','en cours','terminée','annulée'] as $s): ?>
              <option value="<?= $s ?>" <?= $statut === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-1">
          <input type="date" name="from" class="form-control" value="<?= $from ?>">
        </div>
        <div class="col-md-1">
          <input type="date" name="to" class="form-control" value="<?= $to ?>">
        </div>
        <div class="col-auto">
          <button class="btn btn-primary">Filtrer</button>
          <a href="/location/public/index.php?url=historique" class="btn btn-outline-secondary ms-1">Réinitialiser</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Tableau -->
  <div class="card">
    <div class="card-body p-0">
      <?php if (empty($reservations)): ?>
        <div class="empty-state">
          <span class="empty-icon">📋</span>
          <p>Aucune location trouvée</p>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Référence</th>
              <th>Client</th>
              <th>Véhicule</th>
              <th>Début</th>
              <th>Fin prévue</th>
              <th>Retour effectif</th>
              <th>Total</th>
              <th>Statut</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($reservations as $r): ?>
            <tr>
              <td class="fw-semibold"><?= htmlspecialchars($r['reference']) ?></td>
              <td><?= htmlspecialchars($r['client_nom'] . ' ' . $r['client_prenom']) ?></td>
              <td>
                <strong><?= htmlspecialchars($r['vehicle_numero']) ?></strong>
                <span class="text-muted small"> <?= htmlspecialchars($r['marque'] . ' ' . $r['modele']) ?></span>
              </td>
              <td class="text-muted small"><?= date('d/m/Y', strtotime($r['date_debut'])) ?></td>
              <td class="text-muted small"><?= date('d/m/Y', strtotime($r['date_fin_prevue'])) ?></td>
              <td class="text-muted small">
                <?= $r['date_retour_effectif'] ? date('d/m/Y', strtotime($r['date_retour_effectif'])) : '—' ?>
              </td>
              <td><?= $r['montant_total'] ? number_format($r['montant_total'], 2) . ' MAD' : '—' ?></td>
              <td><span class="badge <?= $rBadge[$r['statut']] ?? 'bg-secondary' ?>"><?= ucfirst($r['statut']) ?></span></td>
              <td><a href="/location/public/index.php?url=reservations/view&id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">Voir</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
