<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Paiements — Location</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . "/../../includes/navbar.php"; ?>
<?php include __DIR__ . "/../../includes/flash.php"; ?>

<div class="container-fluid px-4 py-4">

  <div class="page-header">
    <h1 class="page-title">Paiements</h1>
    <a href="/location/public/index.php?url=paiements/add" class="btn btn-success">+ Enregistrer un paiement</a>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="stat-card stat-green">
        <div class="stat-number"><?= count($paiements) ?></div>
        <div class="stat-label">Paiements</div>
        <div class="stat-bg-icon">💳</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card stat-blue">
        <div class="stat-number"><?= number_format($totalMontant, 0) ?></div>
        <div class="stat-label">Total encaissé (MAD)</div>
        <div class="stat-bg-icon">💰</div>
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
            <?php foreach (['acompte','solde','caution','remboursement','frais extra'] as $t): ?>
              <option value="<?= $t ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-auto">
          <button class="btn btn-primary">Filtrer</button>
          <a href="/location/public/index.php?url=paiements" class="btn btn-outline-secondary ms-1">Réinitialiser</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Tableau -->
  <div class="card">
    <div class="card-body p-0">
      <?php if (empty($paiements)): ?>
        <div class="empty-state">
          <span class="empty-icon">💳</span>
          <p>Aucun paiement enregistré</p>
        </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Date</th>
              <th>Réservation</th>
              <th>Client</th>
              <th>Type</th>
              <th>Mode de paiement</th>
              <th>Montant</th>
              <th>Référence</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($paiements as $p): ?>
            <tr>
              <td class="text-muted small"><?= date('d/m/Y', strtotime($p['date_paiement'])) ?></td>
              <td>
                <a href="/location/public/index.php?url=reservations/view&id=<?= $p['reservation_id'] ?>" class="fw-semibold text-decoration-none">
                  <?= htmlspecialchars($p['res_ref']) ?>
                </a>
              </td>
              <td><?= htmlspecialchars($p['client_nom'] . ' ' . $p['client_prenom']) ?></td>
              <td><span class="badge bg-secondary"><?= ucfirst($p['type']) ?></span></td>
              <td class="text-muted small"><?= htmlspecialchars($p['type_paiement']) ?></td>
              <td class="fw-bold text-success"><?= number_format($p['montant'], 2) ?> MAD</td>
              <td class="text-muted small"><?= htmlspecialchars($p['reference_transaction'] ?: '—') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="p-3 text-end fw-bold border-top">
        Total affiché : <?= number_format($totalMontant, 2) ?> MAD
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
