<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sinistres — Location</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . "/../../includes/navbar.php"; ?>
<?php include __DIR__ . "/../../includes/flash.php"; ?>

<div class="container-fluid px-4 py-4">

  <div class="page-header">
    <h1 class="page-title">Sinistres &amp; Incidents</h1>
    <a href="/location/public/index.php?url=sinistres/add" class="btn btn-danger">+ Déclarer un sinistre</a>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="stat-card stat-orange">
        <div class="stat-number"><?= count($sinistres) ?></div>
        <div class="stat-label">Total sinistres</div>
        <div class="stat-bg-icon">⚠</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card stat-slate">
        <div class="stat-number"><?= number_format($totalCout, 0) ?></div>
        <div class="stat-label">Coût total (MAD)</div>
        <div class="stat-bg-icon">🔧</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card stat-blue">
        <div class="stat-number"><?= count(array_filter($sinistres, fn($s) => $s['statut'] === 'ouvert')) ?></div>
        <div class="stat-label">Ouverts</div>
        <div class="stat-bg-icon">📂</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card stat-green">
        <div class="stat-number"><?= count(array_filter($sinistres, fn($s) => $s['statut'] === 'clôturé')) ?></div>
        <div class="stat-label">Clôturés</div>
        <div class="stat-bg-icon">✅</div>
      </div>
    </div>
  </div>

  <!-- Filtres -->
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-3">
          <select name="type" class="form-select">
            <option value="">Tous types</option>
            <?php foreach (['accident','dommage','vol','panne','autre'] as $t): ?>
              <option value="<?= $t ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <select name="statut" class="form-select">
            <option value="">Tous statuts</option>
            <option value="ouvert"    <?= $filterStatut === 'ouvert'    ? 'selected' : '' ?>>Ouvert</option>
            <option value="en cours"  <?= $filterStatut === 'en cours'  ? 'selected' : '' ?>>En cours</option>
            <option value="clôturé"   <?= $filterStatut === 'clôturé'   ? 'selected' : '' ?>>Clôturé</option>
          </select>
        </div>
        <div class="col-auto">
          <button class="btn btn-primary">Filtrer</button>
          <a href="/location/public/index.php?url=sinistres" class="btn btn-outline-secondary ms-1">Réinitialiser</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Tableau -->
  <div class="card">
    <div class="card-body p-0">
      <?php if (empty($sinistres)): ?>
        <div class="empty-state">
          <span class="empty-icon">✅</span>
          <p>Aucun sinistre enregistré</p>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Référence</th>
              <th>Date</th>
              <th>Véhicule</th>
              <th>Client</th>
              <th>Type</th>
              <th>Coût</th>
              <th>Prise en charge</th>
              <th>Statut</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($sinistres as $s): ?>
            <tr>
              <td class="text-muted small"><?= htmlspecialchars($s['reference'] ?: '—') ?></td>
              <td class="text-muted small"><?= $s['date_sinistre'] ? date('d/m/Y', strtotime($s['date_sinistre'])) : '—' ?></td>
              <td>
                <strong><?= htmlspecialchars($s['vehicle_numero']) ?></strong>
                <div class="text-muted small"><?= htmlspecialchars($s['marque'] . ' ' . $s['modele']) ?></div>
              </td>
              <td><?= $s['client_nom'] ? htmlspecialchars($s['client_nom'] . ' ' . $s['client_prenom']) : '—' ?></td>
              <td>
                <?php $typeBadge = ['accident' => 'bg-danger', 'vol' => 'bg-dark', 'panne' => 'bg-warning text-dark', 'dommage' => 'bg-orange', 'autre' => 'bg-secondary']; ?>
                <span class="badge <?= $typeBadge[$s['type']] ?? 'bg-secondary' ?>"><?= ucfirst($s['type']) ?></span>
              </td>
              <td class="fw-semibold"><?= $s['cout_reparation'] ? number_format($s['cout_reparation'], 2) . ' MAD' : '—' ?></td>
              <td class="text-muted small"><?= ucfirst($s['prise_en_charge']) ?></td>
              <td>
                <?php $sBadge = ['ouvert' => 'bg-danger', 'en cours' => 'bg-warning text-dark', 'clôturé' => 'badge-terminee']; ?>
                <span class="badge <?= $sBadge[$s['statut']] ?? 'bg-secondary' ?>"><?= ucfirst($s['statut']) ?></span>
              </td>
              <td class="text-end">
                <?php if ($s['res_ref']): ?>
                  <a href="/location/public/index.php?url=reservations/view&id=<?= $s['reservation_id'] ?>" class="btn btn-sm btn-outline-primary">Location</a>
                <?php endif; ?>
                <a href="/location/public/index.php?url=sinistres/delete&id=<?= $s['id'] ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Supprimer ce sinistre ?')">Supprimer</a>
              </td>
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
