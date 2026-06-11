<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Clôturer location <?= htmlspecialchars($r['reference']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="/location/style.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . "/../../includes/navbar.php"; ?>

<div class="container py-4" style="max-width:650px">
  <div class="page-header">
    <div>
      <div class="text-muted small mb-1">
        <a href="/location/public/index.php?url=reservations/view&id=<?= $id ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($r['reference']) ?></a> / Clôturer
      </div>
      <h1 class="page-title">Retour du véhicule</h1>
    </div>
    <a href="/location/public/index.php?url=reservations/view&id=<?= $id ?>" class="btn btn-outline-secondary">← Retour</a>
  </div>

  <div class="alert alert-info mb-3">
    <strong>Durée écoulée : <?= $joursEcoules ?> jour(s)</strong><br>
    <small>Début : <?= date('d/m/Y H:i', strtotime($r['date_debut'])) ?> — Maintenant : <?= $now->format('d/m/Y H:i') ?></small>
  </div>

  <?php if ($enRetard): ?>
  <div class="alert alert-danger mb-3">
    <div class="d-flex align-items-center gap-2 mb-2">
      <span style="font-size:1.3rem">🔴</span>
      <strong>Retard de <?= $joursRetard ?> jour(s) !</strong>
    </div>
    <div class="row g-2">
      <div class="col-sm-4">
        <div class="bg-white rounded p-2 text-center">
          <div class="text-muted small">Retour prévu</div>
          <div class="fw-bold"><?= $finPrevue->format('d/m/Y') ?></div>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="bg-white rounded p-2 text-center">
          <div class="text-muted small">Jours de retard</div>
          <div class="fw-bold text-danger"><?= $joursRetard ?> jour(s)</div>
        </div>
      </div>
      <div class="col-sm-4">
        <div class="bg-white rounded p-2 text-center">
          <div class="text-muted small">Frais de retard</div>
          <div class="fw-bold text-danger"><?= number_format($fraisRetard, 2) ?> MAD</div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <form method="POST" class="row g-3">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="col-md-6">
          <label class="form-label fw-semibold">Kilométrage au retour</label>
          <input type="number" name="km_retour" class="form-control" min="<?= $r['km_depart'] ?: 0 ?>"
                 placeholder="Ex: <?= ($r['km_depart'] ?: 0) + 500 ?>" onchange="calcFinal()">
          <?php if ($r['km_depart']): ?>
            <div class="form-text">Km au départ : <?= number_format($r['km_depart']) ?> km</div>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Frais extra (MAD)</label>
          <input type="number" name="frais_extra" id="fraisExtra" class="form-control"
                 step="0.01" min="0" value="<?= $fraisExtraInitial ?>" onchange="calcFinal()"
                 placeholder="Retard, carburant, dégâts…">
          <?php if ($enRetard): ?>
            <div class="form-text text-danger">Inclus frais retard : <?= number_format($fraisRetard, 2) ?> MAD</div>
          <?php endif; ?>
        </div>

        <div class="col-12">
          <div class="card bg-light border-0">
            <div class="card-body py-2">
              <div class="d-flex justify-content-between py-1 border-bottom small">
                <span class="text-muted">Location de base (<?= $joursEcoules ?> j × <?= number_format($r['prix_jour'], 2) ?> MAD)</span>
                <span class="fw-semibold"><?= number_format($joursEcoules * $r['prix_jour'], 2) ?> MAD</span>
              </div>
              <?php if ($enRetard): ?>
              <div class="d-flex justify-content-between py-1 border-bottom small text-danger">
                <span>Frais retard (<?= $joursRetard ?> j × <?= number_format($r['prix_jour'], 2) ?> MAD)</span>
                <span class="fw-semibold"><?= number_format($fraisRetard, 2) ?> MAD</span>
              </div>
              <?php endif; ?>
              <div class="d-flex justify-content-between py-1 border-bottom small">
                <span class="text-muted">Frais extra / autres</span>
                <span class="fw-semibold" id="fraisDisplay"><?= number_format($fraisExtraInitial, 2) ?> MAD</span>
              </div>
              <div class="d-flex justify-content-between py-2 fw-bold">
                <span class="text-primary">TOTAL FINAL</span>
                <span class="text-primary fs-6" id="totalFinal"><?= number_format($joursEcoules * $r['prix_jour'] + $fraisExtraInitial, 2) ?> MAD</span>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold">Commentaire de clôture</label>
          <textarea name="commentaire" class="form-control" rows="3" placeholder="État du véhicule, remarques…"></textarea>
        </div>

        <div class="col-12 d-flex justify-content-between mt-2">
          <a href="/location/public/index.php?url=reservations/view&id=<?= $id ?>" class="btn btn-outline-secondary">Annuler</a>
          <button type="submit" class="btn btn-warning px-4 fw-semibold">✔ Confirmer le retour</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const joursEcoules = <?= $joursEcoules ?>;
const prixJour     = <?= (float)$r['prix_jour'] ?>;
function calcFinal() {
  const extra = parseFloat(document.getElementById('fraisExtra').value) || 0;
  const total = joursEcoules * prixJour + extra;
  document.getElementById('fraisDisplay').textContent = extra.toFixed(2) + ' MAD';
  document.getElementById('totalFinal').textContent   = total.toFixed(2) + ' MAD';
}
</script>
</body>
</html>
