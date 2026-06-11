<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>État véhicule #<?= $id ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
  <style>
    .fuel-display { display: flex; gap: 4px; }
    .fuel-seg { width: 36px; height: 22px; border-radius: 3px; border: 2px solid #e2e8f0; }
    .fuel-seg.filled[data-v="0"] { background: #dc2626; border-color: #dc2626; }
    .fuel-seg.filled[data-v="1"], .fuel-seg.filled[data-v="2"] { background: #f59e0b; border-color: #f59e0b; }
    .fuel-seg.filled[data-v="3"], .fuel-seg.filled[data-v="4"] { background: #f97316; border-color: #f97316; }
    .fuel-seg.filled[data-v="5"], .fuel-seg.filled[data-v="6"] { background: #22c55e; border-color: #22c55e; }
    .fuel-seg.filled[data-v="7"], .fuel-seg.filled[data-v="8"] { background: #16a34a; border-color: #16a34a; }
  </style>
</head>
<body>
<?php include __DIR__ . "/../../includes/navbar.php"; ?>
<?php include __DIR__ . "/../../includes/flash.php"; ?>

<div class="container py-4" style="max-width:700px">
  <div class="page-header">
    <div>
      <div class="text-muted small mb-1">
        <a href="index.php" class="text-decoration-none text-muted">États véhicules</a> / Fiche #<?= $id ?>
      </div>
      <h1 class="page-title">
        <?= $e['type'] === 'depart' ? '🚗 État au départ' : '🏁 État au retour' ?>
      </h1>
    </div>
    <div class="d-flex gap-2">
      <a href="/location/public/index.php?url=reservations/view&id=<?= $e['reservation_id'] ?>" class="btn btn-outline-secondary">← Réservation</a>
      <a href="index.php" class="btn btn-outline-secondary btn-sm">Liste</a>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between">
      <span>Informations générales</span>
      <span class="badge bg-<?= $e['type'] === 'depart' ? 'primary' : 'success' ?>"><?= ucfirst($e['type']) ?></span>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-sm-6">
          <div class="info-label">Réservation</div>
          <div class="fw-semibold">
            <a href="/location/public/index.php?url=reservations/view&id=<?= $e['reservation_id'] ?>"><?= htmlspecialchars($e['reference']) ?></a>
          </div>
        </div>
        <div class="col-sm-6">
          <div class="info-label">Véhicule</div>
          <div class="fw-semibold"><?= htmlspecialchars($e['numero'] . ' — ' . $e['marque'] . ' ' . $e['modele']) ?></div>
        </div>
        <div class="col-sm-6">
          <div class="info-label">Client</div>
          <div><?= htmlspecialchars($e['client_nom'] . ' ' . $e['client_prenom']) ?></div>
        </div>
        <div class="col-sm-6">
          <div class="info-label">Enregistré le</div>
          <div><?= date('d/m/Y H:i', strtotime($e['created_at'])) ?></div>
        </div>
        <?php if ($e['created_by_name']): ?>
        <div class="col-sm-6">
          <div class="info-label">Par</div>
          <div><?= htmlspecialchars($e['created_by_name']) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header">Condition du véhicule</div>
    <div class="card-body">
      <div class="row g-4">

        <!-- Carburant -->
        <div class="col-12">
          <div class="info-label mb-2">Niveau de carburant : <strong><?= $fuelLabels[$e['carburant']] ?? '—' ?></strong></div>
          <div class="fuel-display">
            <?php for ($i = 0; $i <= 8; $i++): ?>
            <div class="fuel-seg <?= $i <= (int)$e['carburant'] ? 'filled' : '' ?>" data-v="<?= $i ?>"></div>
            <?php endfor; ?>
          </div>
        </div>

        <!-- Km -->
        <div class="col-sm-4">
          <div class="info-label">Kilométrage</div>
          <div class="fw-semibold fs-5"><?= $e['km'] ? number_format($e['km']) . ' km' : '—' ?></div>
        </div>

        <!-- Propreté -->
        <div class="col-sm-4">
          <div class="info-label">Propreté</div>
          <span class="badge bg-<?= $propreteColors[$e['proprete']] ?? 'secondary' ?> fs-6">
            <?= ucfirst($e['proprete'] ?? '—') ?>
          </span>
        </div>

        <!-- Rayures -->
        <div class="col-sm-4">
          <div class="info-label">Rayures</div>
          <span class="badge <?= $e['rayures'] ? 'bg-danger' : 'bg-success' ?>">
            <?= $e['rayures'] ? 'Oui' : 'Non' ?>
          </span>
        </div>

        <!-- Dommages -->
        <?php if ($e['dommages']): ?>
        <div class="col-12">
          <div class="info-label">Dommages observés</div>
          <div class="alert alert-warning py-2 mb-0"><?= nl2br(htmlspecialchars($e['dommages'])) ?></div>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <?php if ($e['notes']): ?>
        <div class="col-12">
          <div class="info-label">Notes</div>
          <div class="text-muted"><?= nl2br(htmlspecialchars($e['notes'])) ?></div>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
